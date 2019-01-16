<?php

namespace App\Jobs;

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



class AwsFaceIndexing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $rekognitionClient;
    public $s3client;
    public $s3_bucket;


    public function __construct()
    {
        $this->rekognitionClient = new RekognitionClient([
            'region'    => 'us-west-2',
            'version'   => 'latest'
        ]);

        $this->s3client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-2'
        ]);

        $this->s3_bucket = 'afrengine-images';
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // -	Check the faces that has the empty aws_face_id field.
        // -	If one face is selected.
        // -	Check the Faceset_id, and gender of the faceset.
        // -	If gender is male and aws_collection_male_id is empty =>  
        // create the new collection on aws Rekognition.
        // -	FaceIndexing with the aws Rekognition api.
        // -	Update the db faces table/ aws_face_id

        Organization::where('id',3)->update(['contactName'=>'Brian Marlow '. strtotime(date('Y-m-d H:i:s'))]);
        return;

        $face = Face::where('aws_face_id', '')->first();
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
                        $face_id = $this->awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id);
                        if($face_id !== '' && $face_id !== 'faild'){
                            // save the aws_face_id on  faces table.
                            Face::where('id',$face->id)->update(['aws_face_id'=>$face_id]);
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
            
            $results = $this->rekognitionClient->indexFaces([
                "CollectionId"=> $collection_id,
                //"DetectionAttributes"=> [ "DEFAULT" ],
                "ExternalImageId"=> $external_image_id,
                "Image"=> [ 
                    "Bytes"=> $bytes
                ],
                "MaxFaces"=> 1,
                "QualityFilter"=> "AUTO"
            ]);
            $face_id = '';
            if(isset($results['FaceRecords']) && count($results['FaceRecords']) > 0){
                $face_id = $results['FaceRecords'][0]['Face']['FaceId'];
            }
            return $face_id;
        } catch (S3Exception $e) {
            return 'faild';
        }
    }
}
