<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

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
                    "QualityFilter"=> "AUTO"
                ]);

                $face_id = '';
                $gender = "";
                $gender_confidence = 0;
                if(isset($results['FaceRecords']) && count($results['FaceRecords']) > 0){
                    $face_id = $results['FaceRecords'][0]['Face']['FaceId'];
                    $gender = $results['FaceRecords'][0]['FaceDetail']['Gender']['Value'];
                    $gender_confidence = $results['FaceRecords'][0]['FaceDetail']['Gender']['Confidence'];
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


    public function awsFaceSearch($key,$collection_id,$matchthreshold=null, $maxfaces=null){
        if($matchthreshold == null){
            $matchthreshold = (int)$this->aws_search_min_similarity;
        }
        if($maxfaces == null){
            $maxfaces = (int)$this->aws_search_max_cnt;
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
                return array('status'=>'faild','msg'=>'Search Failed');
            }

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}
