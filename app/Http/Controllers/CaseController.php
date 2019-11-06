<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use App\Models\Face as FaceModel;
use App\Models\Arrestee;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Faceset;
use App\Models\FacesetSharing;
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

use Intervention\Image\ImageManagerStatic as EditImage;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Auth;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use Storage;

class CaseController extends Controller
{
	public $facepp;
	
	protected $aws_rekognition_client;
    protected $aws_s3_client;
    protected $aws_s3_bucket;
	
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	function __construct()
	{
        parent::__construct();
		$this->middleware('auth');
		
		$this->aws_rekognition_client = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);

        $this->aws_s3_client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION_NAME')
        ]);

        $this->aws_s3_bucket = env('AWS_S3_BUCKET_NAME');
	}

	function __destruct()
	{
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
		if (Auth::user()->permission->isAdmin()) 
		{
			$cases = Cases::where('cases.organizationId','=',Auth::user()->organizationId)
						->leftJoin('users','cases.userId','=','users.id')
						->select('cases.*','users.name')
						->orderBy('cases.created_at', 'asc')
						->get();
		}
		else
		{
			$cases = Auth::user()->cases()
				->orderBy('created_at', 'asc')
				->get();
		}
		
		return view('cases.index')->with('cases', $cases);
	}

	/**
	 * USER CLICKED ENROLL SCREEN
	 *
	 * @return void
	 */
	public function createForm()
	{
		return view('cases.create');
	}


	public function cases(Cases $cases)
	{
	    return view('cases.cases', [
			'cases' => $cases,
			'search_history' => $cases->caseSearches()->with('image')->orderBy('created_at', 'asc')->get()
		]);
	}

	/**
	 * Creates new case
	 *
	 * @return void
	 */

	public function create(CasesCreate $request)
	{
		$user = Auth::user();

		$cases = new Cases;
		$cases->organizationId = $user->organizationId;
		$cases->userId = $user->id;
		$cases->status = 'ACTIVE';
		$cases->caseNumber = $request->caseNumber;
		$cases->type = $request->type;
		$cases->save();
		
		// Insert this case creation into the UserLog table
		UserLog::create([
		  'userId' => $user->id,
          'event' => 'Created case ' . $cases->caseNumber,
		  'ip' => $request->ip()
		]);

		return redirect()->route('cases.id.show', $cases);
	}

	public function update(CasesUpdate $request, Cases $cases)
	{
		$cases->caseNumber = $request->caseNumber;
		$cases->type = $request->type;
		
		if (!is_null($request->status)) {
			$cases->status = $request->status;
		}
		$cases->dispo = $request->dispo;
		$cases->save();
		
	    return redirect()->back()->with('isCaseUpdated', true);
	}

	public function delete(Cases $cases)
	{
		// delete face from aws rekognition collection
		$organization = Organization::where('id','=',$cases->organizationId)->first();
		$aws_collection_id = $organization->aws_collection_cases_id;

		$images = Image::where('caseId', '=', $cases->id)->get();
		$del_faces = [];
		foreach($images as $image) {
			$del_faces[] = $image->aws_face_id;
		}			

		if(count($del_faces) > 0) {
			try {
				$aws_result = $this->aws_rekognition_client->deleteFaces([
					'CollectionId' => $aws_collection_id,
					'FaceIds' => $del_faces
				]);
				Log::info('Deleted case image from collection :: ' . $image->aws_face_id);
			} catch(RekognitionException $e) {
				Log::info('Failed to delete face :: ' . $e->getMessage());
			}
		}

		// delete cases record from database
		$cases->delete();
		return redirect()->route('cases.show')->with('isCaseDeleted', true);
	}

    // Start upload button on the cases detail page..
	public function addImage(Request $request, Cases $cases)
	{		
		Log::info('Uploading new case image');
		
        // s3 image upload get the image url. on the "cases" directory.
        // s3 bucket/storage/case/images, and s3 bucket/storage/case/thumbnails
        // aws rekognition : image indexing. and get the aws_face_id for this image.
        // add new row on the images table.

        $file = null;

        if ($cases->status != 'ACTIVE') {
			return abort(403);
		}

		if ($request->hasFile('files')) {
			$file = count($request->file('files')) > 0 ? ($request->file('files'))[0] : null;
		}

		if ($file == null || !$file->isValid()) {
			return abort(500);
		}

        // check organiztion collection
        $organization_id = $cases->organizationId;
        $organization = Organization::where('id','=',$organization_id)->first();
        if(!isset($organization->aws_collection_cases_id)){
            return abort(500);
        }
        if($organization->aws_collection_cases_id == ''){
            // create a new collection for aws rekognition.
            $aws_collection_id = $this->createAwsCollection($organization->account . '_cases');
            $organization->aws_collection_cases_id = $aws_collection_id;
            $organization->save();
        }

        $aws_collection_id = $organization->aws_collection_cases_id;

		$name_client = $file->getClientOriginalName();
		
		// Get image filecontent
        $newfile = $file->getPathName();

		// resize the image to a width of 480 and constrain aspect ratio (auto height)
        $img = EditImage::make($newfile)->orientate();
		
		if ($img->width() > 800) {
			$img->resize(800, null, function ($constraint) {
				$constraint->aspectRatio();
			});
		}
        
		$img->save($newfile);
			
		$ext = $file->extension();
		if ($ext == 'jpeg') {
			$ext = "jpg";
		}
		
		$name_upload = str_random(15) . "." . $ext;

		// save image to the s3 directory : storage/case/images/ : and get the s3 image url. *****
        $keyname_origin = 'storage/case/images/' . $name_upload;
        try {
            // Upload data.
            $result = $this->aws_s3_client->putObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname_origin,
                'Body' => file_get_contents($newfile),
                'ACL' => 'public-read'
            ]);
			
/*			
			// Successfully added image. Let's perform a case search to see
			// if this suspect matches suspect photos in other cases
			
			$collection_id = $organization->aws_collection_cases_id;
			if(isset($collection_id))
			{ 
				$threshold = env('AWS_CASESEARCH_MIN_SIMILARITY');
				
				// Search all cases for the User's Organization first
				$search_res = $this->awsFaceSearch($keyname_origin,$collection_id,(int)$threshold);
				
				Log::info("Users CaseID = " . $cases->id);
				
				// Parse through the results and remove any matches for images in this same case
				if(isset($search_res['data_list']) && count($search_res['data_list']) > 0 && $search_res['data_list'] != null)
				{
					foreach($search_res['data_list'] as $key => &$match)
					{
						Log::info("Checking Face " . $match['face_id']);
						
						// Grab the Image data for this match
						$img = Image::where('aws_face_id','=',$match['face_id'])->first();
						
						if(isset($img))
						{
							Log::info("Face belongs to case " . $img->caseId);
						
							// If the Image->CaseID matches the current CaseID, remove it
							if ($img->caseId == $cases->id) 
							{
								Log::info("Removing " . $match['face_id'] . " due to same case");
								unset($search_res['data_list'][$key]);
							}
						}
					}
				}
				
				// Build our list of organization's this User has sharing permissions with
				$organizations = FacesetSharing::where([
						['organization_requestor', $organization_id], ['status', 'ACTIVE']
					])
				->get()->pluck('organization_owner');
				
				$owner = FacesetSharing::where([
					['organization_owner', $organization_id], ['status', 'ACTIVE']
					])
				->get()->pluck('organization_requestor');
        
				$organizations = $organizations->merge($owner);
				$organizations = $organizations->unique();
				$collection_ids = Organization::whereIn('id', $organizations)->get()->pluck('aws_collection_cases_id');
				
				// if there is the shared collections, search collections.
				if(count($collection_ids) > 0){
					foreach ($collection_ids as $collection_id_tmp){
					
						if ($collection_id_tmp == '') {
							continue;
						}
				
						// Perform a search on each collection
						$search_res_tmp = $this->awsFaceSearch($keyname_origin, $collection_id_tmp,(int)$threshold);
                
						if($search_res_tmp['status'] !== 200){
							continue;
						}
						
						$search_res['data_list'] = array_merge($search_res['data_list'],$search_res_tmp['data_list']);
                
						if($search_res['status'] != 200){
							$search_res['status'] = 200;
						}
					}
					
					if(isset($search_res['data_list']) && count($search_res['data_list']) > 0 && $search_res['data_list'] != null)
					{
						usort($search_res['data_list'],function($first,$second){
							return $first['similarity'] < $second['similarity'];
						});
					}

					if(isset($search_res['data_list']) && count($search_res['data_list']) > $this->aws_search_max_cnt)
					{
						$search_res['data_list'] = array_slice($search_res['data_list'], 0, $this->aws_search_max_cnt);
					}
				}
				
				Log::info($search_res);
			}
*/

            // Print the URL to the object.
            $s3_image_url_tmp = $result['ObjectURL'];
            $a = env('AWS_S3_UPLOAD_URL_DOMAIN');
            $b = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
            $s3_image_key = explode($a, $s3_image_url_tmp)[1];
            $s3_image_url = $b . explode($a, $s3_image_url_tmp)[1];

            // response result initialization
            $response_result = [];

            // image indexing for the aws rekognition. with the collection : $aws_collection_id
            $face_indexing_res = $this->awsFaceIndexing($this->aws_s3_bucket, $s3_image_key,$s3_image_url,$aws_collection_id);

            //  
            if(isset($face_indexing_res['face_id']) && $face_indexing_res['face_id'] != '') {
	            // save the thumbnail image.
	            //$thumb_image = ImageResize::work1($file, 256, 0);
	            $keyname_thumbnail = 'storage/case/thumbnails/'. $name_upload;

	            try{
	                // upload thumbnails
	                $result1 = $this->aws_s3_client->putObject([
	                    'Bucket' => $this->aws_s3_bucket,
	                    'Key' => $keyname_thumbnail,
	                    'Body' => file_get_contents($file),
	                    'ACL' => 'public-read'
	                ]);
	                $s3_thumb_url_tmp = $result1['ObjectURL'];

	                // get aws_face_id
	                $aws_face_id = $face_indexing_res['face_id'];

	                // images table save part
	                $image = new Image;
	                $image->caseId = $cases->id;
	                $image->filename = $name_client;
	                $image->filename_uploaded = $name_upload;
	                $image->gender = $request->gender;
	                $image->uploaded = now();
	                $image->lastSearched = null;

	                // save the aws_face_id on images table.
	                $image->aws_face_id = $aws_face_id;
	                $image->save();

	                $response_result = array(
		            	'status'  => 'success',
	                    'imgSrc'  => $s3_thumb_url_tmp,
	                    'msg' 	  => 'No face found in the image!'
	                );
					

	            } catch (S3Exception $e) {
	                echo $e->getMessage() . PHP_EOL;
	                return abort(500);
	            }

	        } else {
	        	// face indexing does not work.
				Log::info('No face found in image');

                // Remove origin image from S3 bucket
	           	$result = $this->aws_s3_client->deleteObject([
	                'Bucket' => $this->aws_s3_bucket,
	                'Key' => $keyname_origin
	            ]);

	           	// Read image path, convert to base64 encoding
				$imageData = base64_encode(file_get_contents($file));

				// Format the image SRC:  data:{mime};base64,{data};
				$imgSrc = 'data: '. $file->getClientMimeType() . ';base64,' . $imageData;

	            $response_result = array(
	            	'status'  => 'error',
                    'imgSrc'  => $imgSrc,
                    'msg' 	  => 'No face found in the image!'
                );
	        }
			
			return response()->json(['files' => [$response_result]]);

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            return abort(500);
        }
	}

	public function imagelist(Request $request, Cases $cases)
	{
		if (!is_null($request->delete)) 
		{
			$image = Image::find($request->delete);
			$this->authorize('delete', $image);

			if (true) {
				if (Storage::exists($image->file_path)) {
					Storage::delete($image->file_path);
				}
				if (Storage::exists($image->thumbnail_path)) {
					Storage::delete($image->thumbnail_path);
				}
			}

			// Delete the Face from the Case Collection
			if(isset($image->aws_face_id)) {
				
				$organization = Organization::where('id','=',$cases->organizationId)->first();
				
				$aws_collection_id = $organization->aws_collection_cases_id;
				
				$del_faces = [];
				$del_faces[] = $image->aws_face_id;
				
				try {
					$aws_result = $this->aws_rekognition_client->deleteFaces([
						'CollectionId' => $aws_collection_id,
						'FaceIds' => $del_faces
					]);
					
					Log::info('Deleted ' . $image->aws_face_id);
				} catch(RekognitionException $e) {
					Log::info('Failed to delete face :: ' . $e->getMessage());
				}
			}
			
			$image->delete();
			
		}

		$result = $cases->images->map(function ($item, $key) {
			
			if (!is_null($item->lastSearched)) {
				$date = date_create($item->lastSearched);
				$date = date_format($date,"m/d/Y H:i:s");
			} else {
				$date = "";
			}
			
			return [
				env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/images/'.$item->filename_uploaded, //asset($item->file_url),
                env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/thumbnails/'.$item->filename_uploaded,//asset($item->thumbnail_url),
				$item->gender,
				$date,
				$item->id
			];
		});
		return response()->json(['data' => $result]);
	}



	public function search(Request $request)
	{
	
		if (is_null($request->image)) {
			return response('Incorrect parameter', 400);
		}
		$image = Image::find($request->image);
		if (is_null($image)) {
			return response('Incorrect parameter', 400);
		}

		$gender = $image->gender;
        $organ_id = Auth::user()->organizationId;
        $organization = Organization::find($organ_id);

		// Make sure our collections have been created before performing a search. It was a bug before.
        if($organization->aws_collection_male_id == '' || $organization->aws_collection_female_id == '')
		{
			$male_name = $organization->account . '_' . 'male';
			$female_name = $organization->account . '_' . 'female';
			
			if ($organization->aws_collection_male_id == '') {
				$male_collection_id = $this->createAwsCollection($male_name);
				Organization::where('id',$organ_id)->update(['aws_collection_male_id'=>$male_collection_id]);
			}
			
			if ($organization->aws_collection_female_id == '') {
				$female_collection_id = $this->createAwsCollection($female_name);
				Organization::where('id',$organ_id)->update(['aws_collection_female_id'=>$female_collection_id]);
			}
			
			$organization = Organization::find($organ_id);
        }
		
        $collection_id = $organization->aws_collection_male_id;
        $collection_field =  'aws_collection_male_id';
        
		if($gender != 'MALE'){
            $collection_id = $organization->aws_collection_female_id;
            $collection_field =  'aws_collection_female_id';
        }

        // face_search from aws rekognition.
        $key = $this->aws_s3_case_image_key_header. $image->filename_uploaded;
        $search_res = $this->awsFaceSearch($key,$collection_id);

        // check the shared organization.
        // if this og has shared organization, then it search with the shared organization also.
        $organizations = FacesetSharing::where([
                ['organization_requestor', $organ_id], ['status', 'ACTIVE']
            ])
        ->get()->pluck('organization_owner');
        $owner = FacesetSharing::where([
                ['organization_owner', $organ_id], ['status', 'ACTIVE']
            ])
        ->get()->pluck('organization_requestor');
        $organizations = $organizations->merge($owner);
        $organizations = $organizations->unique();
        $collection_ids = Organization::whereIn('id', $organizations)->get()->pluck($collection_field);

		// if there is the shared collections, search collections.
        if(count($collection_ids) > 0){
			
            foreach ($collection_ids as $collection_id_tmp){
				

				if ($collection_id_tmp == '') {
					continue;
				}
				
                $search_res_tmp = $this->awsFaceSearch($key, $collection_id_tmp);
                if($search_res_tmp['status'] !== 200){
                    continue;
                }
                $search_res['data_list'] = array_merge($search_res['data_list'],$search_res_tmp['data_list']);
                if($search_res['status'] != 200){
                    $search_res['status'] = 200;
                }
            }

            // rearrange data_list according to the similarity
            if(isset($search_res['data_list']) && count($search_res['data_list']) > 0 && $search_res['data_list'] != null){
                usort($search_res['data_list'],function($first,$second){
                    return $first['similarity'] < $second['similarity'];
                });
            }


            if(isset($search_res['data_list']) && count($search_res['data_list']) > $this->aws_search_max_cnt){
                $search_res['data_list'] = array_slice($search_res['data_list'], 0, $this->aws_search_max_cnt);
            }

        }

        // save the last searched date on the images table.
        $image->lastSearched = now();
		$image->save();

		if(isset($search_res['status']) && $search_res['status'] != 'faild'){
            $search = CaseSearch::create([
                'organizationId' => $organ_id,
                'imageId' => $image->id,
                'searchedOn' => now(),
                'results' => $search_res
            ]);
			
			// Insert this search into the UserLog table
			UserLog::create([
				'userId' => Auth::user()->id,
				'event' => 'Case Search #' . $search->id,
				'ip' => $request->ip()
			]);
		
            return response()->json(
                array_merge($search_res, [
                        'time' => date('Y-m-d h:m:s'),
                        'history_no' => $search->id
                    ]
                )
            );
        }else{
		    return response()->json(array('status'=>'faild','msg'=>'Search result faild.'));
		}
		

	}

	public function searchHistory(Request $request)
	{
		if (is_null($request->history)) {
			return response('Incorrect parameter', 400);
		}
		$search = CaseSearch::find($request->history);
		if (is_null($search)) {
			return response('Incorrect parameter', 400);
		}

		$result = $search['results'];

		if ($result['status'] == 204) {
		//	return response($result, 204);
		}
		
		return response()->json($result);
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
