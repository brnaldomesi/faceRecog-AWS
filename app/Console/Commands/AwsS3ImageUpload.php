<?php
// command on cmd.
// php artisan make:command AwsS3ImageUpload

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

use App\Models\User;
use App\Models\Cases;
use App\Models\Image;
use App\Models\Face;
use App\Models\Faceset;
use App\Models\CaseSearch;
use App\Models\Organization;
use App\Models\FaceTmp;

use App\Utils\FaceSearch;
use App\Mail\Notify;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Log;


class AwsS3ImageUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:s3imageupload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It would be upload the image from the face_tmp table to the AWS S3 storage and add the real s3 image url to the faces table.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public $rekognitionClient;
    public $s3client;
    public $s3_bucket;

    public function __construct()
    {
        parent::__construct();

        $this->rekognitionClient = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);

        $this->s3client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION_NAME')
        ]);

        $this->s3_bucket = env('AWS_S3_BUCKET_NAME');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
         * check the faces_tmp table.
         * - get one image from faces_table.
         * - check the organizationId,
         * - generate new token for the image.
         * - upload the image from the original link to the s3 storage with the above token.
         * - get the real s3 image url.
         * - add the new row on the faces table.
         * - remove the row from face_tmps table.
         *
         * */


        for($i = 0; $i < 1; $i ++){
            $this->handle_one($i);
        }
    }

    public function handle_one($index){
        // getting the face_tmp one row
        $face_tmp = FaceTmp::first();

        if(isset($face_tmp->organizationId) && $face_tmp->organizationId != ''){
            $og_id = $face_tmp->organizationId;

            // check the faceset.
            $organization = Organization::where('id',$og_id)->first();
            if(!isset($organization->id) || $organization->id != $og_id){
                return;
            }

            // get the organization account name.
            $og_account_name = $organization->account;
            if($og_account_name == ''){
                return;
            }

            // image source url.
            $image_source =  $face_tmp->image_url;

            // generate new token for the image to upload on s3.
            $new_face_token = md5(strtotime(date('Y-m-d H:i:s')). $index);

            // get the file type.
            $file_type_tmp = explode(".",$image_source);
            $file_type = $file_type_tmp[count($file_type_tmp) -1];

            //upload image to s3 from the original image.
            $keyname = 'storage/face/'. $og_account_name .'/' . $new_face_token .'.'. $file_type;
            //Log::emergency($keyname); return;
            try {
                // Upload data.
                $result = $this->s3client ->putObject([
                    'Bucket' => $this->s3_bucket,
                    'Key'    => $keyname,
                    'Body'   => file_get_contents($image_source),
                    'ACL'    => 'public-read'
                ]);

                // Print the URL to the object.
                $s3_image_url_tmp = $result['ObjectURL'];
                $a = env('AWS_S3_UPLOAD_URL_DOMAIN');
                $b = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
                $s3_image_url = $b . explode($a, $s3_image_url_tmp)[1];

                // get the default facesetid from the organization and gender "MALE"
                $gender = "MALE";
                $faceset = FaceSet::where('organizationId','=', $og_id)->where('gender','=',$gender)->first();
                $facesetId = '';
                if(isset($faceset->organizationId) && $faceset->organizationId == $og_id){
                    $facesetId = $faceset->id;
                }else{
                    // create new faceset;
                    $faceset_token = md5(strtotime(date('Y-m-d H:i:s')). $index . rand(0,9));
                    $facesetId = Faceset::create([
                        'facesetToken' => $faceset_token,
                        'organizationId' => $og_id,
                        'gender' => $gender
                    ])->id;
                }

                // add the new row on the faces table.
                $imageId = '';
                $identifiers =  Crypt::encryptString($face_tmp->identifiers);
                $face_matches = 0;
                $aws_face_id = '';

                Face::create([
                    'faceToken' => $new_face_token,
                    'savedPath' => $s3_image_url,
                    'facesetId' => $facesetId,
                    'imageId' => $imageId,
                    'identifiers' => $identifiers,
                    'gender' => $gender,
                    'faceMatches' => $face_matches,
                    'aws_face_id' => $aws_face_id
                ]);


                // remove the current row from the face_tmps
                $face_tmp_id = $face_tmp->id;
                FaceTmp::where('id', '=', $face_tmp_id)->delete();



            } catch (S3Exception $e) {
                Log::emergency($e->getMessage() . PHP_EOL);

                // remove the row from face_table
                $face_tmp_id = $face_tmp->id;
                FaceTmp::where('id', '=', $face_tmp_id)->delete();
                reutrn;
            }

        }

        //Log::emergency($a);
    }

}
