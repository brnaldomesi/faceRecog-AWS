<?php

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
use App\Models\FacesetSharing;

use App\Utils\FaceSearch;
use App\Mail\Notify;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


class AwsAutoSearch extends Command
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:autosearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AWS Auto Search function.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $aws_rekognition_client;
    protected $aws_s3_client;
    protected $aws_s3_bucket;

    public function __construct()
    {
        parent::__construct();
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $case_list = array();
		
        // Checks all organizations for any ACTIVE cases that have Images that have not been searched for 30 days or more
        $images = Image::whereHas('cases', function ($query) {
            $query->where('status', 'ACTIVE');
        })
            ->where(function ($query) {
                $query->where('lastSearched', '<', Carbon::now()->subDays(30))
                    ->orWhereNull('lastSearched');
            })
            ->get();

        //var_dump('$images => '. count($images));
        //var_dump(count($images));

		if (count($images) > 0) 
		{
			foreach ($images as $image) {
				
				echo 'Processing image #' . $image['id'] . PHP_EOL;
				echo '   - Last searched: ' . $image['lastSearched'] . PHP_EOL;
				
				$file_path = '../storage/app/' . $image->file_path;
				$organ = $image->cases->organization;

				if (is_null($organ)) {
					continue;
				}
				
				$gender = $image->gender;
				$organ_id = $organ->id;

				// Search images only from faces that were updated after last search date of the image
				// - get the search result from the aws rekoginition.
				// - get the face_id list from the search results
				// - get the new searched faces from the database by using the aws_face_id list that the updated_date > lastsearched date.
				// - get the $new_searched_face_ids from the  the date with the real new searched faces
				// - if the searched face_id is in the new_searched_face_ids, then it would be added to the real new searched faces.
				// - add new row on the case_search table with the image_id and search result, and last_search date.
				// - and update the lastSearched date on the images table.

				// face search from the image on the face collections
				$key = 'storage/case/images/'. $image->filename_uploaded;
				
				$collection_id = '';
				if(isset($organ->aws_collection_male_id)){
					$collection_id = $organ->aws_collection_male_id;
					if($gender == 'FEMALE'){
						$collection_id = $organ->aws_collection_female_id;
					}
				}
				
				if($collection_id == ''){
					continue;
				}
				
				$result_new = $this->searchKeyOnSharedCollections($organ_id,$gender,$key,$collection_id);

				//var_dump('$result_new => ');
				//print_r($result_new);

				// Result 204 is empty search results
				if(isset($result_new['status']) && $result_new['status'] != 200){

					// There are no similar faces for this case image. Update lastSearched
					echo "   - No new similar faces were found for this case image" . PHP_EOL;
					
					$image->lastSearched = now();
					$image->save();
					
					continue;
				}

				// get the face_id list from the searched faces.
				$searched_faces = $result_new['data_list'];
				$face_id_list = [];
				
				foreach($searched_faces as $face){
					$face_id_list[] = $face['face_id'];
				}
				
				if(count($face_id_list) == 0){
					continue;
				}

				// get the lastSearchedDate.
				$last_searched_date = $image->lastSearched;
				
				if ($last_searched_date == NULL) {
					$last_searched_date = '1999-01-01 00:00:00';
				}
				
				//var_dump('face_id_list => ' . count($face_id_list));
				//print_r($face_id_list);
				
				// Get a list of similar Faces that have been added to the system AFTER the last time this case image was searched
				$searched_faces_db = Face::whereIn('aws_face_id',$face_id_list)->where('updated_at','>',$last_searched_date)->get();
				
				$new_searched_face_ids = [];
				
				foreach ($searched_faces_db as $face){
					if(isset($face['aws_face_id']))
						$new_searched_face_ids[] = $face['aws_face_id'];
				}
				
				// There were similar faces but no NEW ones added to the system since the last search.
				if(count($new_searched_face_ids) == 0){
					echo "   - Found 0 new similar faces for this case image" . PHP_EOL;
					
					// Set the lastSearched date to the current date so we can check it again in 30 days
					$image->lastSearched = now();
					$image->save();
					
					continue;
				} else {
					echo "   - Found " . count($new_searched_face_ids) . " new face matches for this case image" . PHP_EOL;
				}
				
				//var_dump('$new_searched_face_ids => '. count($new_searched_face_ids));
				//var_dump($new_searched_face_ids);

				// Count newly detected image for case we come across
				if (!isset($case_list[$image->caseId])) {
					$case_list[$image->caseId] = 1;
				} else {
					$case_list[$image->caseId]++;
				}

				// Update json result and image search date
				$image->lastSearched = now();
				$image->save();

				// save the new searched result to the case_searches table.
				if(count($new_searched_face_ids) > 0) {
					// check the new faces from the results.
					// handle ...
					$res_tmp = $this->filterSearchResultFromDate($result_new,$new_searched_face_ids);
					if($res_tmp == false){
						continue;
					}else{
						$result_new = $res_tmp;
					}

					//var_dump('final_searched_result => ');
					//var_dump($result_new);

					$search = CaseSearch::create([
						'organizationId' => $organ_id,
						'imageId' => $image->id,
						'searchedOn' => now(),
						'results' => $result_new
					]);
				}
			}

			// Organize mail data
			$user_list = array();
			foreach ($case_list as $key => $count) {
				$user_id = Cases::find($key)->userId;
				$user_list[$user_id][$key] = $count; // which case has $count images.
			}

			$mail_list = array();
			foreach ($user_list as $user_id => $cases) {
				$user = User::find($user_id);
				if (!is_null($user)) {
					$mail = array('to' => $user->email, 'name' => $user->name, 'cases' => array());
					foreach ($cases as $case_id => $count) {
						array_push($mail['cases'], ['id' => $case_id, 'name' => Cases::find($case_id)->caseNumber, 'count' => $count]);
					}
					array_push($mail_list, $mail);
				}
			}

			// Notify auto-search fact to user via mail
			//var_dump('$mail_list => ');
			//var_dump($mail_list);
			
			foreach ($mail_list as $mail) {
				$text = $mail['name'] . ", we have automatically re-scanned some of your suspect photos and found some new leads for you.";
				$text .= "<br>Log in and review them to see if they match your suspects.<br>";

				foreach ($mail['cases'] as $c) {
					$link = url('cases/' . $c['id']);
					$text .= "<br>Case " . $c['name'] . " has " . $c['count'] . " new search results.";
					$text .= "<br><a href='{$link}'>{$link}</a><br>";
				}
				$from = config('mail.username');
				$subject = "AFR Engine :: Your cases have new mugshots to review";

				try {
					Mail::to($mail['to']) //
					->bcc("sales@afrengine.com")
					->queue(new Notify($from, $subject, $text));
				} catch (\Exception $e) {}
			}
		}
		else
		{
			echo 'No images to process' . PHP_EOL;
		}
    }

    public function filterSearchResultFromDate($search_result,$faceids){
        if(!isset($search_result['data_list']) || count($search_result['data_list']) == 0){
            return false;
        }
        if(count($faceids) == 0){
            return false;
        }
        $data_list = $search_result['data_list'];

        $data_list_new = [];
        foreach ($data_list as $data){
            if(in_array($data['face_id'],$faceids)){
                $data_list_new[] = $data;
            }
        }

        $search_result['data_list'] = $data_list_new;

        return $search_result;
    }

    public function searchKeyOnSharedCollections($organ_id,$gender,$key,$collection_id){
        //var_dump('$key => '.$key);
        //var_dump('$collection_id => '. $collection_id);

        $search_res = $this->awsFaceSearch($key,$collection_id,0,100);
        //var_dump('$search_res => ');
        //var_dump($search_res);
        $collection_field =  'aws_collection_male_id';
        if($gender != 'MALE'){
            $collection_field =  'aws_collection_female_id';
        }

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


            if(isset($search_res['data_list']) && count($search_res['data_list']) > env('AWS_SEARCH_MAX_CNT')){
                $search_res['data_list'] = array_slice($search_res['data_list'], 0, env('AWS_SEARCH_MAX_CNT'));
            }

        }
        //var_dump('$search_res => ');
        //var_dump($search_res);

        return $search_res;
    }

    public function awsFaceSearch($key,$collection_id,$matchthreshold=null, $maxfaces=null){
        if($matchthreshold == null){
            $matchthreshold = (int)env('AWS_SEARCH_MIN_SIMILARITY');
        }
        if($maxfaces == null){
            $maxfaces = (int)env('AWS_SEARCH_MAX_CNT');
        }
        try {
            // Get the object.
            $result = $this->aws_s3_client->getObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key'    => $key
            ]);

            // Display the object in the browser.
            //header("Content-Type: {$result['ContentType']}");

            $bytes = $result['Body'];
			
            try {
                $results = $this->aws_rekognition_client->SearchFacesByImage([
                    "CollectionId"=> $collection_id,
                    "FaceMatchThreshold" => $matchthreshold,
                    "Image"=> [
                        "Bytes"=> $bytes
                    ],
                    'MaxFaces' => $maxfaces,
                ]);

                $faces_matched = $results['FaceMatches'];
                $matched_images = [];
                foreach($faces_matched as $face){
                    $tmp = str_replace(":","/",$face['Face']['ExternalImageId']);
                    $tmp = str_replace("https/","https:",$tmp);
                    if(substr($tmp,0,7) == 'storage'){
                        $tmp = env('AWS_S3_REAL_OBJECT_URL_DOMAIN'). $tmp;
                    }

                    $face_tmp = [];
                    $face_tmp['image'] = $tmp;
                    $face_tmp['face_id'] = $face['Face']['FaceId'];
                    $face_tmp['confidence'] = $face['Face']['Confidence'];
                    $face_tmp['similarity'] = $face['Similarity'];
                    $matched_images[] = $face_tmp;
                }

                $status = 200; $msg = '';
                if(count($matched_images) == 0){
                    $status = 204;
                    $msg = "Search Result Empty.";
                }
                return array('status'=>$status, 'msg' => $msg, 'data_list'=> $matched_images);

            } catch(RekognitionException $e){
                //var_dump($e->getMessage());
                return array('status'=>'faild','msg'=>'Search Failed');
            }

        } catch (S3Exception $e) {
            //var_dump($e->getMessage());
            echo $e->getMessage() . PHP_EOL;
        }
    }
}
