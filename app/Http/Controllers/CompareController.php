<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

use App\Models\User;
use App\Models\Compare;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Auth;
use Storage;

class CompareController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
	}
	
    public function index()
	{
		return view('compare.index');
	}
	
	public function compare(Request $request) 
	{
        // Get image files content
        $image1 = file_get_contents($request->portraitInput1->getPathName());
        $image2 = file_get_contents($request->portraitInput2->getPathName());
        $result = $this->awsFaceCompare($image1, $image2);
        
        $res = new \stdClass;
        if($result >= 0) {
            $res->status = 200;
            $res->msg = (int)$result; //similarity
        } else {
            $res->status = 300;
            $res->msg = -1; //failed
        }		
        echo json_encode( $res );
    }
    
    public function history() 
	{
		$res = new \stdClass;
		$res->status = 200;
        $res->msg = '93%';
        echo json_encode( $res );
    }
    
    public function save(Request $request) 
    {
        $res = new \stdClass;

        // Get image filename, ext, filecontent
        $filename1 = $request->portraitInput1->getClientOriginalName();
        $file_type_tmp1 = explode(".", $filename1);
        $file_type1 = $file_type_tmp1[count($file_type_tmp1) -1];
        $image_file1 = file_get_contents($request->portraitInput1->getPathName());

        $filename2 = $request->portraitInput2->getClientOriginalName();
        $file_type_tmp2 = explode(".", $filename2);
        $file_type2 = $file_type_tmp2[count($file_type_tmp2) -1];
        $image_file2 = file_get_contents($request->portraitInput2->getPathName());

        // Generate object name
        $objectname1 = md5(strtotime(date('Y-m-d H:i:s')). Auth::user()->email . '1' . rand(0,9));
        $objectname2 = md5(strtotime(date('Y-m-d H:i:s')). Auth::user()->email . '2' . rand(0,9));

        // s3 image upload.
        $keyname1 = 'storage/facecompare/'. $objectname1 .'.'. $file_type1;
        $keyname2 = 'storage/facecompare/'. $objectname2 .'.'. $file_type2;
        try {
            // Upload data.
            $result1 = $this->aws_s3_client->putObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname1,
                'Body' => $image_file1,
                'ACL' => 'public-read'
            ]);
            
            $result2 = $this->aws_s3_client->putObject([
                'Bucket' => $this->aws_s3_bucket,
                'Key' => $keyname2,
                'Body' => $image_file2,
                'ACL' => 'public-read'
            ]);

            // Print the URL to the object.
            $s3_image_url_tmp1 = $result1['ObjectURL'];
            $s3_image_url_tmp2 = $result2['ObjectURL'];
            $a = env('AWS_S3_UPLOAD_URL_DOMAIN');
            $b = env('AWS_S3_REAL_OBJECT_URL_DOMAIN');
            $s3_image_url1 = $b . explode($a, $s3_image_url_tmp1)[1];
            $s3_image_url2 = $b . explode($a, $s3_image_url_tmp2)[1];
            
            Compare::create([
                'imageUrl1' => $s3_image_url1,
                'imageUrl2' => $s3_image_url2,
                'similarity' => $request->similarity
            ]);

            $res->status = 200;
            $res->msg = 'Compare result has been saved successfully';

        } catch (S3Exception $e) {
            $res->status = 300;
            $res->msg = $e->getMessage();
        }

        echo json_encode( $res );

    }
}
