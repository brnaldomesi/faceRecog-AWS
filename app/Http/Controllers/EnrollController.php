<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

use Auth;
use App\Models\Face;
use App\Models\Faceset;
use App\Models\Organization;
use App\Models\Arrestee;

use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Intervention\Image\ImageManagerStatic as Image;

class EnrollController extends Controller
{
    //
    public $rekognitionClient;
	
	function __construct()
	{
		parent::__construct();
		$this->rekognitionClient = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);
	}
	
	function __destruct()
	{
		unset($this->rekognitionClient);
    }
    
    public function index() {
        return view('enroll.index');
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
            return 'failed';
        }
    }

    public function enroll(Request $request) {
        $organizationId = Auth::user()->organizationId;
        $organization = Organization::where('id', $organizationId)->first();
        $organizationAccount = Organization::find($organizationId)->account;
        
        $res = new \stdClass;

        $name = '';
        $dob = '';
        $gender = '';

        $filename = '';
        $image_file = '';
        $file_type = '';

        // Get info from the request
        if($request->portraitInput) {
            // Enroll from storage          
            $name = ucwords(strtolower($request->fromstorage_name));
            $dob = $request->fromstorage_dob;
            $gender = $request->fromstorage_gender;

            // Get image filename
            $filename = $request->portraitInput->getClientOriginalName();
            
            // get the file type.
            $file_type_tmp = explode(".",$filename);
            $file_type = $file_type_tmp[count($file_type_tmp) -1];

            // Get image filecontent
            $file = $request->portraitInput->getPathName();
            
            // resize the image to a width of 300 and constrain aspect ratio (auto height)
            $img = Image::make($file)->orientate()->resize(480, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($file);

            $image_file = file_get_contents($file);
            
        } else {
            // Enroll from camera
            // Not being used
            $name = ucwords(strtolower($request->fromcamera_name));
            $dob = $request->fromcamera_dob;
            $gender = $request->fromcamera_gender;

            $filename = 'camera_snapshot.png';

            $img = $request->portraitCamera;
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $image_file = base64_decode($img);
            
            $file_type = 'png';
        }
        
        // Get facesetId.
        $faceset = FaceSet::where('organizationId','=', $organizationId)->where('gender','=',$gender)->first();
        $facesetId = 0;
        if(isset($faceset->organizationId) && $faceset->organizationId == $organizationId){
            $facesetId = $faceset->id;
        } else {
            // create new faceset;
            $faceset_token = md5(strtotime(date('Y-m-d H:i:s')).'manual_upload'. rand(0,9));
            $facesetId = Faceset::create([
                'facesetToken' => $faceset_token,
                'organizationId' => $organizationId,
                'gender' => $gender
            ])->id;
        }

        // Get organization's aws collection
        $male_collection_id  = '';
        $female_collection_id = '';

        if(isset($organization->aws_collection_male_id) && isset($organization->aws_collection_female_id)) {                        
            // male collection
            if($organization->aws_collection_male_id == '')
            {
                // create the aws_collection.
                $male_name = $organization->account . '_' . 'male';
                $male_collection_id  = $this->createAwsCollection($male_name);
                if($male_collection_id !== 'failed')
                {
                    // update the collection_id on the database.
                    $organization->update(['aws_collection_male_id'=>$male_collection_id]);
                }
            } 
            else 
            {
                $male_collection_id = $organization->aws_collection_male_id;
                
            }

            // female collection
            if($organization->aws_collection_female_id == '')
            {
                // create the aws_collection.
                $female_name = $organization->account . '_' . 'female';
                $female_collection_id  = $this->createAwsCollection($female_name);
                if($female_collection_id !== 'failed')
                {
                    // update the collection_id on the database.
                    $organization->update(['aws_collection_female_id'=>$female_collection_id]);
                }
            } 
            else 
            {
                $female_collection_id = $organization->aws_collection_female_id;
            }
        }

        if($male_collection_id == 'failed' || $female_collection_id == 'failed') {
            $res->status = 300;
            $res->msg = 'Error occured in the enrollment process.';
            echo json_encode( $res );
            return; 
        }
        $aws_collection_id = (strtoupper($gender) == 'MALE') ? $male_collection_id : $female_collection_id;

        // Manual upload of image.
        $new_face_token = md5(strtotime(date('Y-m-d H:i:s')). 'manual_upload');

        // s3 image upload.
        $keyname = 'storage/face/'. $organizationAccount .'/' . $new_face_token .'.'. $file_type;
        try {
            // Upload data.
            $result = $this->aws_s3_client->putObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname,
                'Body' => $image_file,
                'ACL' => 'public-read'
            ]);

            // Print the URL to the object.
            $s3_image_url_tmp = $result['ObjectURL'];
            $a = env('AWS_S3_UPLOAD_URL_DOMAIN');
            $b = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
            $s3_image_key = explode($a, $s3_image_url_tmp)[1];
            $s3_image_url = $b . explode($a, $s3_image_url_tmp)[1];
            
            // Image indexing for the aws rekognition. with the collection : $aws_collection_id
            $face_indexing_res = $this->awsFaceIndexing($this->aws_s3_bucket, $s3_image_key, $s3_image_url, $aws_collection_id);

            // Check indexing result
            if(isset($face_indexing_res['face_id']) && $face_indexing_res['face_id'] != '' && $face_indexing_res !== 'faild')
			{
                $aws_face_id = $face_indexing_res['face_id'];
            } else {
                // Remove image from S3 bucket
	           	$result = $this->aws_s3_client->deleteObject([
	                'Bucket' => $this->aws_s3_bucket,
	                'Key' => $keyname
                ]);
                
                $res->status = 300;
                $res->msg = 'No face found in the image.';
                echo json_encode( $res );
                return;     
            }

            // Get personId
            $personId = strtolower($organizationAccount . '_' . str_replace(' ', '', $name) . '_' . str_replace('/', '', $dob));

            // Check if personId exists in Arrestees table
            $arrestee = Arrestee::where('personId', '=', $personId)->where('organizationId', '=', $organizationId)->first();
                                
            // This Organization's Person does not already exist in the Arrestees table.  Let's create it.
            if (!$arrestee)
            {
                $arrestee = Arrestee::create([
                    'organizationId' => $organizationId,
                    'personId' => $personId,
                    'name' => Crypt::encryptString($name),
                    'dob' => Crypt::encryptString($dob),
                    'gender' => $gender
                ]);
            }

            // Store Face model
            $datenow = date('Y-m-d', strtotime(date('Y-m-d')));
            Face::create([
                'faceToken' => $new_face_token,
                'savedPath' => $s3_image_url,
                'facesetId' => $facesetId,
                'imageId' => '',
                'filename' => $filename,
                'personId' => $arrestee->id,
                'organizationId' => $organizationId,
                'identifiers' => Crypt::encryptString($name . ' (' . $dob . ')'),
                'gender' => $gender,
                'faceMatches' => 0,
                'aws_face_id' => $aws_face_id,
                'imagedate' => $datenow
            ]);
            
            // Increment number of faces enrolled in faceset --- this isn't reliable number though
            Faceset::where('id', $facesetId)->increment('faces');

        } catch (S3Exception $e) {
            $res->status = 300;
            $res->msg = $e->getMessage();
            echo json_encode( $res );
            return;
        }

        $res->status = 200;
        $res->msg = 'Photo has been enrolled successfully.';
        echo json_encode( $res );        
    }
}
