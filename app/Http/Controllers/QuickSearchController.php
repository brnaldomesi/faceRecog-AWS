<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Compare;
use App\Models\FacesetSharing;
use App\Models\QuickSearch;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Intervention\Image\ImageManagerStatic as Image;

use Auth;
use Storage;

date_default_timezone_set('America/Los_Angeles');

class QuickSearchController extends Controller
{
	
	public $rekognitionClient;
	
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
		
		$this->rekognitionClient = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);
	}
	
    public function index()
	{
		if (Auth::user()->permission->isSuperAdmin())
		{
			$quicksearches = QuickSearch::join('users','users.id','=','quicksearch_history.userid')
				->select('users.name','quicksearch_history.*')
				->get();
		}
		else if (Auth::user()->permission->isAdmin())
		{
			$quicksearches = QuickSearch::where('quicksearch_history.organizationId','=',Auth::user()->organizationId)
				->join('users','users.id','=','quicksearch_history.userid')
				->select('users.name','quicksearch_history.*')
				->get();
		}
		else
		{
			$quicksearches = QuickSearch::where('userid','=',Auth::user()->id)->orderBy('created_at', 'desc')->get();
		}
		
		return view('quicksearch.index', [
			'quicksearch_history' => $quicksearches
		]);
	}
	
	public function search(Request $request)
	{
		$organizationId = Auth::user()->organizationId;
		$organization = Organization::where('id', $organizationId)->first();
        $organizationAccount = Organization::find($organizationId)->account;
		
		// Process the uploaded image and perform the Rekognition search
		try 
		{
			// Get image filename
			$filename = $request->portraitInput1->getClientOriginalName();
			$gender = $request->gender;
			
			// get the file type.
			$file_type_tmp = explode(".",$filename);
			$file_type = $file_type_tmp[count($file_type_tmp) -1];
			
			if ($file_type == 'jpeg') {
				$file_type = "jpg";
			}
			
			$name_upload = str_random(15) . "." . $file_type;

			$key = 'storage/search/images/' . $name_upload;

			// Get image data into bytes for S3 upload
			$image_file = file_get_contents($request->portraitInput1->getPathName());

			// Upload image to S3
			$result = $this->aws_s3_client->putObject([
				'Bucket' => $this->aws_s3_bucket,
				'Key' => $key,
				'Body' => $image_file,
				'ACL' => 'public-read'
			]);

			// Set our user's Collection based on the Gender provided
			$collection_id = $organization->aws_collection_male_id;
			$collection_field =  'aws_collection_male_id';
			
			if($gender != 'MALE'){
				$collection_id = $organization->aws_collection_female_id;
				$collection_field =  'aws_collection_female_id';
			}
			
			// Get a list of Organization's the User shares with
			$organizations = FacesetSharing::where([
                ['organization_requestor', $organizationId], ['status', 'ACTIVE']
            ])->get()->pluck('organization_owner');
			
			$owner = FacesetSharing::where([
				['organization_owner', $organizationId], ['status', 'ACTIVE']
			])->get()->pluck('organization_requestor');
			
			$organizations = $organizations->merge($owner);
			$organizations = $organizations->unique();
			$collection_ids = Organization::whereIn('id', $organizations)->get()->pluck($collection_field);
			
			// Merge the user's collection with any collection ID's they are sharing with
			$collection_ids = $collection_ids->merge($collection_id);
			
			// Run our Async Face Search using the uploaded image
			$search_res = $this->awsFaceSearchAsync($key,$collection_ids);
			
			if (isset($search_res['status']) && $search_res['status'] != 204) 
			{
				// Insert search results into the quicksearch_history table
				$search = QuickSearch::create([
				  'userid' => Auth::user()->id,
				  'organizationId' => $organizationId,
				  'reference' => $request->reference,
				  'filename' => $name_upload,
				  'results' => $search_res['data_list']
				]);
				
				// Insert this quick search into the UserLog table
				UserLog::create([
				  'userId' => Auth::user()->id,
				  'event' => 'Quick Search #' . $search->id,
				  'ip' => $request->ip()
				]);
				
				// Return our formatted JSON response
				return response()->json(array('status'=> $search_res['status'], 'msg'=> $search_res['msg'], 'data_list'=>$search_res['data_list']));
				
			} 
			else 
			{
				return json_encode(array('status' => 204, 'msg' => "No matches found."));
			}

		} catch(RekognitionException $e){

			Log::emergency($e->getMessage());
			
			return json_encode(array('status' => 'faild', 'msg' => "We encountered an error performing this search."));
        }
		
		
	}
	
	public function faceCleanSearch($collection_id,$image_file)
	{
		// Query the User's Organization's Collection
		$search_results = $this->rekognitionClient->SearchFacesByImage([
			"CollectionId"=> $collection_id, 
			"FaceMatchThreshold" => (float)env('AWS_SEARCH_MIN_SIMILARITY'),
			"Image"=> [ 
				"Bytes"=> $image_file
			], 
			'MaxFaces' => (int)env('AWS_SEARCH_MAX_CNT'),
		]);
		
		$faces_matched = $search_results['FaceMatches'];
		$matched_images = [];
		
		foreach($faces_matched as $face)
		{
			$tmp = str_replace(":","/",$face['Face']['ExternalImageId']);
				
			$tmp = str_replace("https/","https:",$tmp);
			if(substr($tmp,0,7) == 'storage'){
				$tmp = env('AWS_S3_REAL_OBJECT_URL_DOMAIN'). $tmp;
			}

			$face_tmp = [];
			$face_tmp['image'] = $tmp;
			$face_tmp['face_id'] = $face['Face']['FaceId'];
			$face_tmp['similarity'] = $face['Similarity'];
			$matched_images[] = $face_tmp;
		}
		
		return $matched_images;
	}

	public function history(Request $request)
	{
		
		if (is_null($request->history)) {
			return response('Incorrect parameter', 400);
		}

		$quicksearchHistory = QuickSearch::find($request->history);
		
		Log::emergency($quicksearchHistory['results']);
		
		if (is_null($quicksearchHistory)) {
			return response('Incorrect parameter', 400);
		}

		$res = new \stdClass;
		$res->status = 200;
		$res->msg = "Success";
		$res->data_list = $quicksearchHistory['results'];

		return response()->json($res);
	}
	
	public function getDetailFaceInfo(Request $request){
	    if(is_null($request->aws_face_id)){
	        return response('Incorrect parameter', 400);
        }
        $face = FaceModel::where('aws_face_id','=',$request->aws_face_id)->first();
	    if(is_null($face)){
	        return response('Incorrect parameter',400);
        }

        $face->identifiers = Crypt::decryptString($face->identifiers);

	    $faceset = Faceset::find($face->facesetId);
	    if(!isset($faceset->organizationId) || $faceset->organizationId == ''){
	        return response('Incorrect Parameter', 400);
        }

        $organ = Organization::find($faceset->organizationId);
	    $organ_name = '';
	    if(isset($organ->name)){
	        $organ_name = $organ->name;
        }
		
		// This Face is not currently associated with a Person. Probably a legacy photo prior to the update
		if($face->personId != '') {
			// Grab all 'other' photos for this person
			$photos = Photo::where('arresteeId','=',$face->personId)->orderBy('photoDate','desc')->orderBy('poseType','desc')->get();
			if ($photos) {
				$face->galleryCount = $photos->count();
			}
		}		

        $face->organ_name = $organ_name;

        return response()->json($face);

    }

	// Loads a gallery of photos for a specific person
	public function getPersonGallery(Request $request) {
		if (is_null($request->aws_face_id)){
			return response('Incorrect parameter',400);
		}
		
		$face = FaceModel::where('aws_face_id','=',$request->aws_face_id)->first();
		if(is_null($face)){
			return response('Incorrect parameter',400);
		}
		
		// This Face is not currently associated with a Person. Probably a legacy photo prior to the update
		if($face->personId == '') 
		{
			return response()->json('');
		} else 
		{
			// Grab all 'other' photos for this person
			$photos = Photo::where('arresteeId','=',$face->personId)->orderBy('poseType','desc')->orderBy('photoDate','desc')->get();
			return response()->json($photos);
		}
		
		return response('Success',200);
	}	
	
}
