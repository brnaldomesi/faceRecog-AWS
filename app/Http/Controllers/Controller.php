<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;

use Aws\CommandPool;
use Aws\CommandInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $site_baseurl;
    protected $aws_rekognition_client;
    protected $aws_s3_client;
    protected $aws_s3_bucket;
    protected $aws_s3_case_image_key_header;
    protected $aws_search_max_cnt;
    protected $aws_search_min_similarity;
	protected $search_res;
	protected $status;
	protected $msg;

    function __construct()
    {
        $this->site_baseurl = config('app.url');

        $this->aws_rekognition_client = new RekognitionClient([
            'region'    => env('AWS_REGION_NAME'),
            'version'   => 'latest'
        ]);

        $this->aws_s3_client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION_NAME')
        ]);

        $this->aws_s3_bucket = env('AWS_S3_BUCKET_NAME');

        $this->aws_s3_case_image_key_header = 'storage/case/images/';

        $this->aws_search_max_cnt = env('AWS_SEARCH_MAX_CNT');
        $this->aws_search_min_similarity = env('AWS_SEARCH_MIN_SIMILARITY');

        view()->share('base_url', $this->site_baseurl);
    }

    public function createAwsCollection($collection_name){
        $collection_id = $collection_name. '_'.strtotime(date('y-m-d H:i:s'));
        try{
            $results = $this->aws_rekognition_client->CreateCollection([
                'CollectionId' => $collection_id
            ]);
            return $collection_id;
        }catch(RekognitionException $e){
            Log::emergency($e->getMessage());
            echo $e->getMessage();
            return 'faild';
        }
    }

    public function awsFaceIndexing($aws_bucket, $img_key,$external_image_url,$collection_id){
        $bucket = $aws_bucket;
        $key = $img_key;
        $external_image_id = str_replace("/",":",$external_image_url);
        
		try {
            // Get the object.
            $result = $this->aws_s3_client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);
            // Display the object in the browser.
            $bytes = $result['Body'];

            try{
                $results = $this->aws_rekognition_client->indexFaces([
                    "CollectionId"=> $collection_id,
                    "DetectionAttributes"=> [ "ALL" ],
                    "ExternalImageId"=> $external_image_id,
                    "Image"=> [
                        "Bytes"=> $bytes
                    ],
                    "MaxFaces"=> 1,
                    "QualityFilter"=> "HIGH"
                ]);

                $face_id = '';
                $gender = "";
                $gender_confidence = 0;
				
                if(isset($results['FaceRecords']) && count($results['FaceRecords']) > 0){
                    $face_id = $results['FaceRecords'][0]['Face']['FaceId'];
                    $gender = $results['FaceRecords'][0]['FaceDetail']['Gender']['Value'];
                    $gender_confidence = $results['FaceRecords'][0]['FaceDetail']['Gender']['Confidence'];
					Log::emergency($results);
                } else {
					
					if(count($results['UnindexedFaces']) > 0) {
						// A face was found, but it was not a high enough quality to index
						Log::emergency("Image not indexed because: {$results['UnindexedFaces'][0]['Reasons'][0]}");
						
						switch($results['UnindexedFaces'][0]['Reasons'][0]) {
							case 'LOW_BRIGHTNESS':
								$msg = 'The image is too dark';
								break;
							case 'LOW_SHARPNESS':
								$msg = 'The image is too blurry';
								break;
							default:
								$msg = 'Unable to detect a usable face';
								break;
						}
						
						return array('status' => 204, 'msg' => $msg);
						
					} else {
						// No faces were found in the image.  Dump the results for debugging
						Log::emergency("No face found in the uploaded image");
						return array('status' => 204, 'msg' => 'Unable to detect a face');
					}
				}
				
                $res = array('face_id' => $face_id, 'gender' => strtoupper($gender), 'gender_confidence'=>$gender_confidence);

                return $res;

            }catch(RekognitionException $e){
                return 'faild';
            }

        } catch (S3Exception $e) {
            return 'faild';
        }
    }

	// Performs case image searches against the provided collections and returns the results
	public function awsFaceSearchAsync($key,$collections,$matchthreshold=null,$maxfaces=null)
	{
		// Our master search result list.
		$this->search_res = [];
		
		// Set our threshold and max faces variables
		if($matchthreshold == null) {
            $matchthreshold = (int)$this->aws_search_min_similarity;
        }
		
        if($maxfaces == null){
            $maxfaces = (int)$this->aws_search_max_cnt;
        }
		
		try {
			
			// Create an iterator for the $collections array
			$obj = new \ArrayObject( $collections );
			$it = $obj->getIterator();
			
			// Build our CommandPool
			$commandGenerator = function () use ($collections,$matchthreshold,$key) {
				
				Log::emergency("Key {$key}");
				
				// Parse through the collections array
				foreach( $collections as $collection_id)
				{	
					if ($collection_id == '') {
						continue;
					}
					
					Log::emergency("Adding collection ID {$collection_id}");
					
					// Add the searchFacesByImage command into the command pool
					yield $this->aws_rekognition_client->getCommand('SearchFacesByImage',[
						"CollectionId"=> $collection_id,
						"FaceMatchThreshold" => (int)$matchthreshold,
						"Image"=> [
							'S3Object' => [
								'Bucket' => $this->aws_s3_bucket,
								'Name'	 => $key,
							],
						],
						"MaxFaces" => (int)$this->aws_search_max_cnt,
					]);
				}
			};
			
			// Generate our command pool based off of our interator.
			$commands = $commandGenerator($it);
			
			// Run our CommandPool
			$pool = new CommandPool($this->aws_rekognition_client, $commands, [
				// Max commands per second so we don't hit our AWS limits
				'concurrency' => 20, 
				
				// Runs this code BEFORE it starts the command
				'before'	=> function (CommandInterface $cmd, $iterKey) {
					Log::emergency("Running search # {$iterKey}");
				},
				
				// Runs when there was a rejection for this promise
				'rejected' => function(
					AwsException $reason,
					$iterKey,
					PromiseInterface $aggregatePromise
				) {
					Log::emergency("Failed {$iterKey}: {$reason}");
				},
				// Runs this once the promise has been returned
				'fulfilled'	=> function(
					ResultInterface $result, // The response from the command
					$iterKey, // What iteration in the array this was
					PromiseInterface $aggregatePromise // Might be the aggregate of all search results
				) {
					Log::emergency("Ran search # {$iterKey}");
					
					// Merge the results into one array
					$faces_matched = $result['FaceMatches'];
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
						$face_tmp['confidence'] = $face['Face']['Confidence'];
						$face_tmp['similarity'] = $face['Similarity'];
						$matched_images[] = $face_tmp;
						$this->status = 200;
						$this->msg = "Success";
					}
					
					//Log::emergency("#230: " . json_encode($matched_images));
					
					// Merge these results with the master array
					$this->search_res = array_merge($this->search_res,$matched_images);
				},
			]);
				
			// Run the promise
			$promise = $pool->promise();
			
			// Runs when the promises are all completed
			$promise->then(function() {
			
				Log::emergency("Promises are completed");
				
				// Sort the list by similarity
				usort($this->search_res,function($first,$second){
					return $first['similarity'] < $second['similarity'];
				});

				// Cap our results based on the max count variable
				if(isset($this->search_res) && count($this->search_res) > $this->aws_search_max_cnt) {
					$this->search_res = array_slice($this->search_res, 0, (int)$this->aws_search_max_cnt);
				}
				
				// If no matches were found, 
				if(count($this->search_res) == 0) {
					$this->status = 204;
					$this->msg = "Search Result Empty";
				}
			
			});		
			
			// Tell PHP to wait for all promises to come back before continuing.
			$promise->wait();
			
			// After the promises have all finished, return our results
			return array('status'=>$this->status, 'msg' => $this->msg, 'data_list'=> $this->search_res);
			
		} catch(RekognitionException $e){
			
		}
	}

    public function awsFaceSearch($key,$collection_id,$matchthreshold=null, $maxfaces=null){
        if($matchthreshold == null){
            $matchthreshold = (int)$this->aws_search_min_similarity;
        }
        if($maxfaces == null){
            $maxfaces = (int)$this->aws_search_max_cnt;
        }

        try {
            // Get the object.
            //$result = $this->aws_s3_client->getObject([
            //    'Bucket' => $this->aws_s3_bucket,
            //    'Key'    => $key
            //]);

            // Display the object in the browser.
            //header("Content-Type: {$result['ContentType']}");

            //$bytes = $result['Body'];

            try {
                $results = $this->aws_rekognition_client->SearchFacesByImage([
                    "CollectionId"=> $collection_id,
                    "FaceMatchThreshold" => $matchthreshold,
                    "Image"=> [
                        //"Bytes"=> $bytes
						'S3Object' => [
							'Bucket' => $this->aws_s3_bucket,
							'Name'	 => $key,
						],
                    ],
                    'MaxFaces' => $maxfaces,
                ]);

                
                $faces_matched = $results['FaceMatches'];
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
                return array('status'=>'faild','msg'=>'Search Failed');
            }

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function awsFaceCompare($image1, $image2){
        try{
            $results = $this->aws_rekognition_client->compareFaces([
                "SimilarityThreshold" => (float)$this->aws_search_min_similarity,
                "SourceImage" => [
                    'Bytes' => $image1,
                ],
                "TargetImage" => [
                    'Bytes' => $image2,
                ],
            ]);

            $similarity = 0;
            if(isset($results['FaceMatches']) && count($results['FaceMatches']) > 0){
                $similarity = $results['FaceMatches'][0]['Similarity'];
            }
            
            return $similarity;

        } catch(InvalidParameterException $e) {
            return -1;
        } catch(RekognitionException $e) {
            return -2;
        }
    }
}
