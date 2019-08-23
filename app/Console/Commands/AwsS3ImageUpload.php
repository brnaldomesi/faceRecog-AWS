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
use App\Models\Arrestee;
use App\Models\Photo;
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
    protected $description = 'It would upload the image from the face_tmp table to the AWS S3 storage and add the real s3 image url to the faces table.';

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

		$face_tmp = FaceTmp::orderBy('id','desc')->first();
		
		if (isset($face_tmp)) {
			
			if ($face_tmp->count() > 0) {

				for($i = 0; $i < 20; $i ++){
					$this->handle_one($i);
				}
			}
		}
		
		
    }

    public function handle_one($index){
		
        // getting the face_tmp one row
        $face_tmp = FaceTmp::orderBy('id','desc')->first();

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

			$downloadFailed = false;
			
			if ($og_account_name == 'maricopacountyjail__' || $og_account_name == 'pinalcountyjail')
			{
				// Proxy for importing scraped images
				$ch = curl_init($face_tmp->image_url);
				curl_setopt($ch, CURLOPT_PROXY, "23.83.87.120:80");
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, "afrengine:afrengineproxy");
				curl_setopt($ch, CURLOPT_BINARYTRANSFER,TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$im = curl_exec($ch);
				
				// Check if the Image URl was a redirect from the original URL
				// If it was, there was an error.  Delete the temp record
				$lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				
				if ($lastUrl == $face_tmp->image_url)
				{
				
					if (file_exists("temp.jpg")) {
						unlink("temp.jpg");
					}
					
					$fp = fopen("temp.jpg","x");
					fwrite($fp,$im);
					fclose($fp);
					curl_close($ch);
					
					// Load our image resource
					$im = imagecreatefromjpeg("temp.jpg");
					
					// Get our width/height
					$width = imagesx($im);
					$height = imagesy($im);
					
					// Crop the watermark
					$im2 = imagecrop($im,['x'=>0,'y'=>0,'width'=>$width,'height'=>$height-61]);
					
					// Save the cropped image
					imagejpeg($im2,"temp.jpg");
					
					// Load our newly cropped image into image_source
					$image_source = @file_get_contents("temp.jpg");

					// if failed to get image
					if($image_source === FALSE) {
						$downloadFailed = true;

						FaceTmp::find($face_tmp->id)->delete();
						
						// log error
						$logstr = "Failed to get image file from: " . $face_tmp->image_url;
						Log::emergency($logstr);
						$log = fopen("public/debug.txt","a");
						fwrite($log, $logstr . "\n");
						fclose($log);
					}
				} 
				else 
				{
					$downloadFailed = true;
					
					// Unable to download the image.  Delete it
					$face_tmp_id = $face_tmp->id;
					FaceTmp::where('id', '=', $face_tmp_id)->delete();
				}
			}
			else
			{
				// image source url.
				$image_source =  @file_get_contents($face_tmp->image_url);
				
				// if failed to get image
				if($image_source === FALSE) {
					$downloadFailed = true;

					FaceTmp::find($face_tmp->id)->delete();
					
					// log error
					$logstr = "Failed to get image file from: " . $face_tmp->image_url;
					Log::emergency($logstr);
					$log = fopen("public/debug.txt","a");
					fwrite($log, $logstr . "\n");
					fclose($log);
				}
			}

			if ($downloadFailed == false)
			{
				// generate new token for the image to upload on s3.
				$new_face_token = md5(strtotime(date('Y-m-d H:i:s')). $index);
				
				// get the file type.
				$file_type_tmp = explode(".",$face_tmp->image_url);
				$file_type = $file_type_tmp[count($file_type_tmp) -1];

				// Determine what type of Pose this image is and set the storage folder appropriately
				if ($face_tmp->pose == 'F') {
					$folder = 'storage/face/';
				} else {
					$folder = 'storage/other/';
				}

				//upload image to s3 from the original image.
				$keyname = $folder . $og_account_name .'/' . $new_face_token .'.'. $file_type;
				
				//Log::emergency($keyname); return;
				try 
				{
					// Upload data.
					$result = $this->s3client ->putObject([
						'Bucket' => $this->s3_bucket,
						'Key'    => $keyname,
						'Body'   => $image_source,
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

					// Check if personId exists in Arrestees table
					$arrestee = Arrestee::where('personId','=',$face_tmp->personId)->where('organizationId','=',$og_id)->first();
					
					// This Organization's Person does not already exist in the Arrestees table.  Let's create it.
					if (!$arrestee)
					{
						$arrestee = Arrestee::create([
							'organizationId' => $og_id,
							'personId' => $face_tmp->personId,
							'name' => Crypt::encryptString($face_tmp->fullname),
							'dob' => Crypt::encryptString($face_tmp->dob),
							'gender' => $gender
						])->id;
						
						$id = $arrestee;
						
						$log = fopen("public/debug.txt","a");
						fwrite($log, "-- Creating new Arrestee record [" . $id . "]\n");
						fclose($log);
					}
					else
					{
						$id = $arrestee->id;
					}
					
					// If image is Frontal pose, add it to the Faces table
					if ($face_tmp->pose == 'F')
					{
						// add the new row on the faces table.
						$imageId = '';
						$identifiers =  Crypt::encryptString($face_tmp->identifiers);
						$face_matches = 0;
						$aws_face_id = '';
						
						$imagedate = date('Y-m-d',strtotime($face_tmp->imagedate));
						
						Face::create([
							'faceToken' => $new_face_token,
							'savedPath' => $s3_image_url,
							'facesetId' => $facesetId,
							'imageId' => $imageId,
							'filename' => $face_tmp->filename,
							'personId' => $id,
							'organizationId' => $og_id,
							'identifiers' => $identifiers,
							'gender' => $gender,
							'faceMatches' => $face_matches,
							'aws_face_id' => $aws_face_id,
							'imagedate' => $imagedate
						]);
					}
					else
					{
						// Image is a profile/tattoo photo.  Add to Photos table and associate with existing Arrestee
						Photo::create([
							'arresteeId' => $id,
							'filename' => $face_tmp->filename,
							'poseType' => $face_tmp->pose,
							'savedPath' => $s3_image_url,
							'photoDate' => $face_tmp->imagedate
						]);
					}
					
					// remove the current row from the face_tmps
					$face_tmp_id = $face_tmp->id;
					FaceTmp::where('id', '=', $face_tmp_id)->delete();

				} catch (S3Exception $e) {
					Log::emergency($e->getMessage() . PHP_EOL);
					// remove the row from facetmp_table
					$face_tmp_id = $face_tmp->id;
					FaceTmp::where('id', '=', $face_tmp_id)->delete();

					return;
				}
			}
        }

        //Log::emergency($a);
    }

}
