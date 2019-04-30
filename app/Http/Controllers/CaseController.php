<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use App\Models\Face as FaceModel;
use App\Models\User;
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
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	function __construct()
	{
        parent::__construct();
		$this->middleware('auth');
		$this->facepp = new Facepp();
		$this->facepp->api_key = config('face.providers.face_plus_plus.api_key');
		$this->facepp->api_secret = config('face.providers.face_plus_plus.api_secret');
	}

	function __destruct()
	{
		unset($this->facepp);
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
		$cases = Auth::user()->cases()
			->orderBy('created_at', 'asc')
			->get();
		
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

		return redirect()->route('cases.id.show', $cases);
	}

	public function update(CasesUpdate $request, Cases $cases)
	{
		$cases->caseNumber = $request->caseNumber;
		$cases->type = $request->type;
		if (!is_null($request->status)) {
			$cases->status = $request->status;
		}
		if ($request->status == 'CLOSED') {
			$cases->dispo = $request->dispo;
		}
		$cases->save();

		return redirect()->route('cases.id.show', $cases);
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
		$name_upload = str_random(15) . "." . $file->guessClientExtension();

		// save image to the s3 directory : storage/case/images/ : and get the s3 image url. *****
        $keyname_origin = 'storage/case/images/' . $name_upload;
        try {
            // Upload data.
            $result = $this->aws_s3_client->putObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname_origin,
                'Body' => file_get_contents($file),
                'ACL' => 'public-read'
            ]);

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
	                    'name'    => $name_client,
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
                    'name'    => $name_client,
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
		if (!is_null($request->delete)) {
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
			$image->delete();
		}

		$result = $cases->images->map(function ($item, $key) {
			return [
				env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/images/'.$item->filename_uploaded, //asset($item->file_url),
                env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/thumbnails/'.$item->filename_uploaded,//asset($item->thumbnail_url),
				$item->filename,
				$item->lastSearched,
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

        if(!isset($organization->aws_collection_male_id)){
            return response('Incorrect parameter', 400);
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
                ['organization_owner', $organ_id], ['status', 'ACTIVE']
            ])
            ->get()->pluck('organization_requestor');
        $owner = FacesetSharing::where([
                ['organization_requestor', $organ_id], ['status', 'ACTIVE']
            ])
            ->get()->pluck('organization_owner');
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

        $face->organ_name = $organ_name;


        return response()->json($face);

    }


}
