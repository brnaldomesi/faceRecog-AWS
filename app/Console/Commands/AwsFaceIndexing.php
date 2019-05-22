<?php
// command on cmd.
// php artisan make:command AwsFaceIndexing


namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\User;
use App\Models\Cases;
use App\Models\Image;
use App\Models\Face;
use App\Models\Faceset;
use App\Models\CaseSearch;
use App\Models\Organization;

use App\Utils\FaceSearch;
use App\Mail\Notify;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Log;



class AwsFaceIndexing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:faceindexing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It would do FaceIndexing on the AWS Rekognition.';

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
        // -	Check the faces that has the empty aws_face_id field.
        // -	If one face is selected.
        // -	Check the Faceset_id, and gender of the faceset.
        // -	If gender is male and aws_collection_male_id is empty =>  
        // -    create the new collection on aws Rekognition.
        // -	FaceIndexing with the aws Rekognition api.
        // -	Update the db faces table/ aws_face_id

        // **** - update the real gender and update facesetID from the response.

//        Organization::where('id',3)->update(['contactName'=>'Brian Marlow '. strtotime(date('Y-m-d H:i:s'))]);
//        return;

        for($i = 0; $i < 20; $i ++){
            $this->handle_one($i);
        }

    }

    public function handle_one($index){
		
        $face = Face::where('aws_face_id', '')->latest()->first();
		
        if(isset($face->facesetId)){
            $facesetId = $face->facesetId;
            $gender = $face->gender;
	
            $faceset = Faceset::where('id',$facesetId)->first();
            if(isset($faceset->gender) && $faceset->gender == $gender){
                $organization_id = $faceset->organizationId;

                $organization = Organization::where('id', $organization_id)->first();
                if(isset($organization->aws_collection_male_id) && isset($organization->aws_collection_female_id)){

                    // check the collection id.
                    $male_collection_id  = '';
                    $female_collection_id = '';
                    if($organization->aws_collection_male_id == '' || $organization->aws_collection_female_id == ''){
                        // create the aws_collection.
                        $male_name = $organization->account . '_' . 'male';
                        $female_name = $organization->account . '_' . 'female';
                        echo $male_name;
                        $male_collection_id  = $this->createAwsCollection($male_name);
                        $female_collection_id = $this->createAwsCollection($female_name);

                        if($male_collection_id !== 'faild' && $female_collection_id !== 'faild'){
                            // update the collection_id on the database.
                            Organization::where('id',$organization_id)->update(['aws_collection_male_id'=>$male_collection_id,'aws_collection_female_id'=>$female_collection_id]);
                        }
                    }else{
                        $male_collection_id = $organization->aws_collection_male_id;
                        $female_collection_id = $organization->aws_collection_female_id;
                    }

                    if($male_collection_id != '' && $female_collection_id != ''){
                        // face indexing function.
                        $collection_id = $male_collection_id;
                        if($gender == 'FEMALE'){
                            $collection_id = $female_collection_id;
                        }
                        $external_image_url = $face->savedPath;
                        $aws_bucket = $this->s3_bucket;
                        $img_key = explode($this->s3_bucket.'/', $face->savedPath)[1];
	
                        $logstr = "\nStarted faceindexing S3_Object saved at: " . $external_image_url;
                        $log = fopen("public/debug_index.txt","a");
                        fwrite($log, $logstr);
                        fclose($log);

                        // face indexing by using aws rekoginition.
                        $indexed_face = $this->awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id);
                        //Log::emergency('$indexed_face_id] =>' . $indexed_face['face_id']);

                        if(isset($indexed_face['face_id']) && $indexed_face['face_id'] != '' && $indexed_face !== 'faild'){

                            // checking the gender response from the aws rekognition and
                            // update the facesetId and gender on the faces table.
                            $aws_gender = strtoupper($indexed_face['gender']);
                            $aws_gender_confidence = $indexed_face['gender_confidence'];
                            //Log::emergency('aws rekognition face indexing: Gender Confidence => '. $aws_gender_confidence);
                            $aws_face_id = $indexed_face['face_id'];

                            var_dump('Compare Gender between aws and original => '. $aws_gender . ' : '. $gender);
                            if(strtoupper($aws_gender) != strtoupper($gender) && $aws_gender != '' && $aws_gender != NULL && $aws_gender_confidence > 50){
                                // check the $aws_gender_confidence

                                // get the faceset id from the aws_gender and organization_id.
                                $faceset_new = Faceset::where('organizationId','=',$organization_id)->where('gender','=',strtoupper($aws_gender))->first();
                                $faceset_new_id = 0;
                                if(isset($faceset_new->id)){
                                    $faceset_new_id = $faceset_new->id;
                                }else{
                                    // create new faceset_new
                                    $faceset_token = md5(strtotime(date('Y-m-d H:i:s')). $index);
                                    $faceset_new_id = Faceset::create([
                                        'facesetToken' => $faceset_token,
                                        'organizationId' => $organization_id,
                                        'gender' => $aws_gender
                                    ])->id;
                                }

                                // update the faceset_id, and gender on the faces table.
                                Face::where('id',$face->id)->update(['facesetId'=>$faceset_new_id,'gender'=>strtoupper($aws_gender)]);


                                // remove this face from rekognition.
                                try{
                                    $results1 = $this->rekognitionClient->DeleteFaces([
                                        "CollectionId"=> $collection_id,
                                        "FaceIds"=> [ $aws_face_id ]
                                    ]);
                                }catch(ReKognitionException $e){
                                    echo $e->getMessage(). PHP_EOL;
                                }

                                // and  add the right face indexing again with the right gender.
                                // face indexing by using aws rekoginition.
                                $collection_id = $male_collection_id;
                                if(strtoupper($aws_gender) == 'FEMALE'){
                                    $collection_id = $female_collection_id;
                                }
                                var_dump('$aws_gender => ' . $aws_gender);
                                var_dump($collection_id);
                                var_dump($aws_face_id);
                                $indexed_face1 = $this->awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id);
                                if(isset($indexed_face1['face_id'])&& $indexed_face1['face_id'] != '' && $indexed_face1 !== 'faild'){
                                    $aws_face_id = $indexed_face1['face_id'];
                                    var_dump($aws_face_id);
                                }
                            }

                            // save the aws_face_id on  faces table.
                            Face::where('id',$face->id)->update(['aws_face_id'=>$aws_face_id]);
                            Faceset::where('id', $facesetId)->increment('faces');
                        }else{
                            //log
                            $logstr = "--------Failed";
                            $log = fopen("public/debug_index.txt","a");
                            fwrite($log, $logstr);
                            fclose($log);

                            // set the aws_face_id is not used.
                            Face::where('id',$face->id)->update(['aws_face_id'=>'false']);
                        }

                    }

                }
            }
        }
    }

    public function createAwsCollection($collection_name){
        $collection_id = $collection_name. '_'.strtotime(date('y-m-d H:i:s'));
        try{
            $results = $this->rekognitionClient->CreateCollection([
                'CollectionId' => $collection_id
            ]);
            return $collection_id;
        }catch(RekognitionException $e){
            Log::emergency($e->getMessage());
            return 'faild';
        }
    }

    public function awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id){
        $bucket = $aws_bucket;
        $key = $img_key;
        $external_image_id = str_replace("/",":",$external_image_url);
        try {
            // Get the object.
            $result = $this->s3client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);
        
            // Display the object in the browser.
            $bytes = $result['Body']; 

            try{
                $results = $this->rekognitionClient->indexFaces([
                    "CollectionId"=> $collection_id,
                    "DetectionAttributes"=> [ "ALL" ],
                    "ExternalImageId"=> $external_image_id,
                    "Image"=> [
                        "Bytes"=> $bytes
                    ],
                    "MaxFaces"=> 1,
                    "QualityFilter"=> "AUTO"
                ]);

                $face_id = '';
                $gender = "";
                $gender_confidence = 0;
                if(isset($results['FaceRecords']) && count($results['FaceRecords']) > 0){
                    $face_id = $results['FaceRecords'][0]['Face']['FaceId'];
                    $gender = $results['FaceRecords'][0]['FaceDetail']['Gender']['Value'];
                    $gender_confidence = $results['FaceRecords'][0]['FaceDetail']['Gender']['Confidence'];
                }
                $res = array('face_id' => $face_id, 'gender' => strtoupper($gender), 'gender_confidence'=>$gender_confidence);

                return $res;

            }catch(RekognitionException $e){
				
				Log::emergency($e->getMessage());
				Log::emergency($img_key);
				
                return 'faild';
            }

        } catch (S3Exception $e) {
            return 'faild';
        }
    }
}
