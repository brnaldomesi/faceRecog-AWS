<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

use App\Models\Face as FaceModel;
use App\Models\User;
use App\Models\Faceset;
use App\Models\Organization;
use App\Models\FaceTmp;

use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Auth;

class FaceController extends Controller
{
    //
    public function index()
	{
        $organizations = Organization::orderBy('created_at','asc')->get();
		return view('faces.index', compact('organizations'));
    }

    public function importCSV(Request $request)
    {
        $organizationId = $request->organizationCSV;
		
        $log = fopen("debug.txt","a");
        
        $header = true; // CSV file has a header line
        $faceInfoArray = [];
        $faceIdArray = [];
        $faceArray = [];
        
        $lineCount = 0;

        // Open the CSV file
        $handle = fopen($request->csv->getPathName(), "r");

        ini_set('auto_detect_line_endings', true);
        
        // Parse through the CSV file
        while ($csvLine = fgetcsv($handle, 1024)) 
        {
            if ($header) 
            {
                // Set the header to false and skip the top row
                $header = false;
            } 
            else 
            {
                $lineCount++;
                
                if (count($csvLine) > 1) 
                {	
                    // Checks to make sure there is at least one row of data before we process.
                    if ($csvLine[2] == '') {
                        $gender = 'MALE';
                    } else {
                        $gender = ($csvLine[2] == "M") ? "MALE" : "FEMALE";
                    }
                
                    fwrite($log,"Importing mugshot [Line " . $lineCount . " @ " . date("h:i:sa") . "]...\n");
                    
                    $imgUrl = $csvLine[0];
                    $identifiers = $csvLine[1];
                    fwrite($log,"Importing info : face_tmps => " . $organizationId . " @ " . $imgUrl. "@" .$identifiers . "@" . $gender . "\n");
                    // insert new row to the face_tmps table.

                    $facetmp_id = FaceTmp::create([
                        'organizationId'=> $organizationId,
                        'image_url' => $imgUrl,
                        'identifiers' => $identifiers,
                        'gender' => $gender
                    ])->id;

                }
                else
                {
                    // Line was blank
                    fwrite($log,"Row [" . $lineCount . "] was invalid. Skipping...\n");
                }
            }

        }

        // Close the CSV file
        fclose($handle);
        
        // close the log file
        fclose($log);
        
        $res = new \stdClass;
        $res->status = 200;
        $res->msg = 'CSV file has been imported successfully.';
        echo json_encode( $res );
    }

    public function enrollPhoto(Request $request)
    {
        $organizationId = $request->organizationPhoto;
		$organizationAccount = Organization::find($organizationId)->account;	// account name used for data storage, etc.
				
        //****** Image is a single image uploaded by the user *******//
        // image upload to the s3 storage and faceindexing the image to the aws rekognition.
        //ini_set('max_execution_time', 300);
        
        $res = new \stdClass;

        $gender = $request->gender;
        $identifiers = $request->identifiers;

        // Get image filename
        $filename = $request->portraitInput->getClientOriginalName();
        // get the file type.
        $file_type_tmp = explode(".",$filename);
        $file_type = $file_type_tmp[count($file_type_tmp) -1];

        // Get image filecontent
        $file = $request->portraitInput->getPathName();
        $image_file = file_get_contents($file);

        // Manual upload of image.
        $new_face_token = md5(strtotime(date('Y-m-d H:i:s')). 'manual_upload');
        
        $faceset = FaceSet::where('organizationId','=', $organizationId)->where('gender','=',$gender)->first();
        
        // getting the facesetId.
        $facesetId = 0;
        if(isset($faceset->organizationId) && $faceset->organizationId == $organizationId){
            $facesetId = $faceset->id;
        }else{
            // create new faceset;
            $faceset_token = md5(strtotime(date('Y-m-d H:i:s')).'manual_upload'. rand(0,9));
            $facesetId = Faceset::create([
                'facesetToken' => $faceset_token,
                'organizationId' => $organizationId,
                'gender' => $gender
            ])->id;
        }

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
            $s3_image_url = $b . explode($a, $s3_image_url_tmp)[1];

            FaceModel::create([
                'faceToken' => $new_face_token,
                'facesetId' => $facesetId,
                'imageId' => '',
                'identifiers' => Crypt::encryptString($identifiers),
                'gender' => $gender,
                'savedPath' => $s3_image_url,
                'aws_face_id' => ''
            ]);

            // Increment our faces
            Faceset::where('id', $facesetId)->increment('faces');
        }catch (S3Exception $e) {
            $res->status = 300;
            $res->msg = $e->getMessage();
            echo json_encode( $res );
            return;
        }

        $res->status = 200;
        $res->msg = 'Photo has been enrolled successfully.';
        echo json_encode( $res );
    }

    public function searchImage(Request $request)
    {
        $face = FaceModel::where('faceToken', '=', $request->faceToken)->first();
        $res = new \stdClass;
        if(isset($face)) {
            $res->status = 200;
            $res->msg = $face->savedPath;
        } else {
            $res->status = 300;
            $res->msg = 'No image available';
        }
        echo json_encode( $res );
    }

    public function removeFace(Request $request)
    {        
        $face = FaceModel::where('faceToken', '=', $request->faceToken)->first();
        $res = new \stdClass;

        if(!isset($face)) {
            $res->status = 300;
            $res->msg = 'No face found';
            echo json_encode( $res );
            return;
        }

        $strHeader = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
        $keyname = str_replace($strHeader, '', $face->savedPath);
        
        try {
            $s3_result = $this->aws_s3_client->deleteObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname,
            ]);
        } catch (S3Exception $e) {
            $res->status = 300;
            $res->msg = $e->getMessage();
            echo json_encode( $res );
        }
        
        if(isset($face->aws_face_id)) {
            $org = $face->faceset->organization;
            $aws_collection_id = ($face->gender == "MALE") ? $org->aws_collection_male_id : $org->aws_collection_female_id;
            try {
                $aws_result = $this->aws_rekognition_client->deleteFaces([
                    'CollectionId' => $aws_collection_id,
                    'FaceIds' => [
                        $face->aws_face_id
                    ]
                ]);
            } catch(RekognitionException $e) {
                $res->status = 300;
                $res->msg = $e->getMessage();
                echo json_encode( $res );
                return;
            }
        }
        $face->delete();
        $res->status = 200;
        $res->msg = 'The face has been removed successfully.';
        echo json_encode( $res );
    }
}
