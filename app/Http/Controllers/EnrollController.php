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
        
    }
}
