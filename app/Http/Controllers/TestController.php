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
        parent::__construct();
		$this->middleware('auth');
		$this->rekognitionClient = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);

        $this->s3client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION_NAME')
        ]);
        
        $this->s3_bucket = env('AWS_S3_BUCKET_NAME');
        $this->test_collection_id = 'maricopacountyjail_male_1547784083';
        
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
        //$this->handle_one(0);
        //$this->putimages3();
        // getting the collection list.
        $collections = [];
        try{
            
            $results = $this->rekognitionClient->ListCollections([
                "MaxResults" => 10
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
                "MaxResults"=> 10
                //"NextToken"=> "string"
            ]);
            
            //var_dump($results); exit;
            if(isset($results['Faces'])){
                $faces = $results['Faces'];
            }
        }catch (RekognitionException $e){
            echo $e->getMessage() . PHP_EOL;
            //exit;
        }

        return view('test.index')->with('collections', $collections)->with('faces', $faces);
	}

	//
    public function putimages3(){
	    $keyname = '1.jpg';
	    $image_source = 'https://arre.st/Jails/AZJails.info/images2/LEON-BOONE-T225179.jpg';
        $result = $this->s3client ->putObject([
            'Bucket' => $this->s3_bucket,
            'Key'    => $keyname,
            'Body'   => file_get_contents($image_source),
            'ACL'    => 'public-read'
        ]);
        $url = $result['ObjectURL'];
        //change the url.
        // https://afrengine-storage.s3.us-gov-west-1.amazonaws.com/ =>
        // https://s3-us-gov-west-1.amazonaws.com/afrengine-storage/
        $a = env('AWS_S3_UPLOAD_URL_DOMAIN');
        $b = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
        $url = $b . explode($a, $url)[1];
        var_dump($url); exit;
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

        $bucket = $this->s3_bucket;
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
        $bucket = $this->s3_bucket;
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
                    "FaceMatchThreshold" => 60,
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




    public function handle_one($index){
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

                        // face indexing by using aws rekoginition.
                        $indexed_face = $this->awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id);
                        Log::emergency('$indexed_face_id] =>'.$indexed_face['face_id']);

                        if(isset($indexed_face['face_id']) && $indexed_face['face_id'] != 0 && $indexed_face !== 'faild'){

                            // checking the gender response from the aws rekognition and
                            // update the facesetId and gender on the faces table.
                            $aws_gender = $indexed_face['gender'];
                            $aws_gender_confidence = $indexed_face['gender_confidence'];
                            Log::emergency('aws rekognition face indexing: Gender Confidence => '. $aws_gender_confidence);
                            $aws_face_id = $indexed_face['face_id'];

                            if($aws_gender != $gender){
                                // check the $aws_gender_confidence

                                // get the faceset id from the aws_gender and organization_id.
                                $faceset_new = Faceset::where('organizationId','=',$organization_id)->where('gender','=',$aws_gender)->first();
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
                                Face::where('id',$face->id)->update(['facesetId'=>$faceset_new_id,'gender'=>$aws_gender]);


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
                                if($aws_gender == 'FEMALE'){
                                    $collection_id = $female_collection_id;
                                }
                                $indexed_face1 = $this->awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id);
                                if(isset($indexed_face1['face_id'])&& $indexed_face1['face_id'] != 0 && $indexed_face1 !== 'faild'){
                                    $aws_face_id = $indexed_face1['face_id'];
                                }
                            }

                            // save the aws_face_id on  faces table.
                            Face::where('id',$face->id)->update(['aws_face_id'=>$aws_face_id]);

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
        var_dump($aws_bucket . '=>'. $img_key. '=>'.$external_image_url. '=>'.$collection_id); exit;
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
                    //"DetectionAttributes"=> [ "DEFAULT" ],
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
                    $gender = $result['FaceRecords'][0]['FaceDetail']['Gender']['Value'];
                    $gender_confidence = $result['FaceRecords'][0]['FaceDetail']['Gender']['Confidence'];
                }
                $res = array('face_id' => $face_id, 'gender' => $gender, 'gender_confidence'=>$gender_confidence);

                return $res;

            }catch(Rekognition $e){
                return 'faild';
            }

        } catch (S3Exception $e) {
            return 'faild';
        }
    }
    
	

}
