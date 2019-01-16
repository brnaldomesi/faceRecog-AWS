<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;

use App\Models\Face;
use App\Models\User;
use App\Models\Faceset;
use App\Models\Cases;
use App\Models\Image;
use App\Models\CaseSearch;
use App\Models\Organization;

use App\Http\Requests\CasesCreate;
use App\Http\Requests\CasesUpdate;

use App\Utils\UploadHandler;
use App\Utils\ImageResize;
use App\Utils\FaceSearch;
use App\Utils\Facepp;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Auth;
use Storage;

class TestController extends Controller
{
    public $rekognitionClient;
    public $s3client;
    public $s3_bucket;
    public $test_collection_id;
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	function __construct()
	{
		$this->middleware('auth');
		$this->rekognitionClient = new RekognitionClient([
            'region'    => 'us-west-2',
            'version'   => 'latest'
        ]);

        $this->s3client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-2'
        ]);
        
        $this->s3_bucket = 'afrengine-images';

        $this->test_collection_id = 'maricopacountyjail_male_1547611307';
        
	}

	function __destruct()
	{
		unset($this->rekognitionClient);
	}

	/**
	 * Returns the available facesets for the organization
	 *
	 * NOTE: We will need to expand this to check what other organization face sets are accessible and then add them to this list
	 *
	 * @return void
	 */
	public function index()
	{
        $this->handle_1();

        // getting the collection list.
        $collections = [];
        try{
            
            $results = $this->rekognitionClient->ListCollections([
                "MaxResults" => 100
            ]);
            
            if(isset($results['CollectionIds'])){
                $collections = $results['CollectionIds'];
            }
            
        }catch(Rekognition $e){
            echo $e->getMessage() . PHP_EOL;
            exit;
        }
        

        // getting the faces from the special collection.
        $faces = [];
        try {
            $results = $this->rekognitionClient->ListFaces([
                "CollectionId"=> $this->test_collection_id,
                "MaxResults"=> 100
                //"NextToken"=> "string"
            ]);
            
            //var_dump($results); exit;
            if(isset($results['Faces'])){
                $faces = $results['Faces'];
            }
        }catch (RekognitionException $e){
            echo $e->getMessage() . PHP_EOL;
            exit;
        }
        
        

        return view('test.index')->with('collections', $collections)->with('faces', $faces);
	}


    /* 
        create a collection 
    */
    public function createCollection(Request $request){
        $name = $request->input('name');
        $collection_id = $name. '_'.strtotime(date('y-m-d H:i:s'));
        $results = $this->rekognitionClient->CreateCollection([
            'CollectionId' => $collection_id
        ]);

        return response()->json($results);
    } 


    /* 
        indexing facing.
    */ 
    public function awstestFaceindexing(Request $request){
        $key = $request->input('key');

        $bucket = 'afrengine-images';
        if($key == ''){
            $key = 'storage/face/maricopacountyjail/MALE/00d826f9134f01668937143d2a0473ef/12929c30f0ec7a40c06afd647427a5b6.jpg';
        }
        
        $external_image_id = str_replace("/",":",$key);
        try {
            // Get the object.
            $result = $this->s3client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);
        
            // Display the object in the browser.
            //header("Content-Type: {$result['ContentType']}");
            
            $bytes = $result['Body']; 
            
            try{
                $results = $this->rekognitionClient->indexFaces([
                    "CollectionId"=> $this->test_collection_id,
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
                
            }catch(RekognitionException $e){
                echo $e->getMessage(). PHP_EOL;
                exit;
            }
            
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        
    }

    /* 
        Search Face form aws ReKognition.
    */ 
    public function awstestSearchface(Request $request){
        $bucket = 'afrengine-images';
        $key = 'storage/face/maricopacountyjail/MALE/00d826f9134f01668937143d2a0473ef/12929c30f0ec7a40c06afd647427a5b6.jpg';
        try {
            // Get the object.
            $result = $this->s3client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);
        
            // Display the object in the browser.
            //header("Content-Type: {$result['ContentType']}");
            
            $bytes = $result['Body']; 
            
            try {
                $results = $this->rekognitionClient->SearchFacesByImage([
                    "CollectionId"=> $this->test_collection_id,
                    "FaceMatchThreshold" => 0,
                    "Image"=> [ 
                        "Bytes"=> $bytes
                    ], 
                    'MaxFaces' => 100,
                ]);
                
                $faces_matched = $results['FaceMatches'];
                $matched_images = [];
                foreach($faces_matched as $face){
                    $tmp = str_replace(":","/",$face['Face']['ExternalImageId']);
                    $tmp = str_replace("https/","https:",$tmp);
                    $face_tmp = [];
                    $face_tmp['image'] = $tmp;
                    $face_tmp['similarity'] = $face['Similarity'];
                    $matched_images[] = $face_tmp;
                }

                return response()->json($matched_images);

            } catch(RekognitionException $e){
                echo 'faild';
                //echo $e->getMessage(). PHP_EOL;
            }

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }   
    }

    /*
        delete face from collection.
     */ 
    public function awstestDeleteFace(Request $request){
        $face_id = $request->input('face_id');
        try{    
            $results = $this->rekognitionClient->DeleteFaces([
                "CollectionId"=> $this->test_collection_id,
                "FaceIds"=> [ $face_id ]
            ]);
            return response()->json($results);
        }catch(ReKognitionException $e){
            echo $e->getMessage(). PHP_EOL;
        }
    }




    public function handle_1()
    {
        // -	Check the faces that has the empty aws_face_id field.
        // -	If one face is selected.
        // -	Check the Faceset_id, and gender of the faceset.
        // -	If gender is male and aws_collection_male_id is empty =>  
        // create the new collection on aws Rekognition.
        // -	FaceIndexing with the aws Rekognition api.
        // -	Update the db faces table/ aws_face_id

        // Organization::where('id',3)->update(['contactName'=>'Brian Marlow '. strtotime(date('Y-m-d H:i:s'))]);
        // return;

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
