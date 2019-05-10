<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

use Intervention\Image\ImageManagerStatic as Image;

use luchaninov\CsvFileLoader;

use App\Models\Face as FaceModel;
use App\Models\User;
use App\Models\Faceset;
use App\Models\Organization;
use App\Models\FaceTmp;

use App\Utils\Facepp;
use App\Utils\FaceSearch;

// aws package.
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


use Auth;
use Face;
use Storage;

class PortraitsController extends Controller
{
    public $facepp;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
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
     * Perform a face search and return the results
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function search(Request $request) {
      
	  // Get the organizationID for the logged in user
      $organizationId = Auth::user()->organizationId;
      
      $result = FaceSearch::search($request->searchPortraitInput->getPathName(), $organizationId, null, 'MANUAL_SEARCH');
      return json_encode($result);
    }


    /**
     * Returns the available facesets for the organization
	 *
	 * NOTE: We will need to expand this to check what other organization face sets are accessible and then add them to this list
     *
     * @return void
     */
    public function index(Request $request)
    {
		// Get the organizationID for the logged in user
        $organizationId = Auth::user()->organizationId;
		
		// Get a list of facesets in the DB for the organization.
		// ** EXPAND THIS TO SEE WHAT OTHER ORGANIZATION'S ARE SHARING FACESETS WITH THIS USER'S ORGANIZATION AND ADD TO LIST
        $faceSets = Faceset::select('id')->where('organizationId', $organizationId)->get();
		
		// Return the list of facesets to the search screen
        return view('portraits.index')->with('faceSets', $faceSets);
    }

    /**
     * USER CLICKED ENROLL SCREEN
     *
     * @return void
     */
    public function create()
    {
      return view('portraits.create');
    }

     /**
     * Call face++ detect api
     *
     * @param  $filename : file path
     *
     * @return void
     */
    public function detectFace($params)
	{
		$errorReturnVal = false;

		while($errorReturnVal === false) 
		{
		  //$detectApiCallResult = $this->detectFace($filename);
		  
		  // Calls API to detect a face in the user uploaded image 
		  $detectApiCallResult = $this->facepp->execute('/detect', $params);
		  $errorReturnVal = $detectApiCallResult;
		  
		  $returnVal = json_decode($detectApiCallResult);
		  $isErrorDetected = isset($returnVal->error_message);
		}

		return json_decode($detectApiCallResult);
    }

	/**
     * Creates a new Faceset for the organization
     *
     * @param  $filename : file path
     *
     * @return void
     */
  public function createFaceSet($faceSetName, $faceIdArray, $organizationId) {
		ini_set('max_execution_time', 300);
		$noError = false;
      
		while($noError === false) 
		{
			// Insert new Faceset into DB with detected faces array
			$album = Face::createAlbum($faceSetName, $faceIdArray, ['tags' => $organizationId]);
			$noError = $album;
		}
		
		return $album->getId();
  }

 	public function addFacesintoFaceSet($facesetToken, $faceIdArray) {
    ini_set('max_execution_time', 300);
    $noError = false;
    while($noError === false) {
      $isAdded = Face::addIntoAlbum($facesetToken, $faceIdArray);
      $noError = $isAdded;
    }
   
  }

	/*
	*  Returns the current active Faceset for the specified Gender
	*  
	*  Handles the 500 face limit and creation of new faceset when
	*  capacity is reached.
	*/
	public function getActiveFaceset($gender) {

		$organizationId = Auth::user()->organizationId;
		$organizationName = Organization::find($organizationId)->name;			// Plain text name of account
		$organizationAccount = Organization::find($organizationId)->account;
		
		// Get our active Faceset for this Organization and Gender
		$faceset = Faceset::where('organizationId',$organizationId)
					->where('gender',$gender)
					->where('faces',"<", "500")
					->get();
					
		// No matches for this Org/Gender and less than 500.  Create a new one
		if($faceset->isEmpty()) 
		{
			$facesetIndex = Faceset::where('organizationId',$organizationId)
									->where('gender',$gender)
									->count() + 1;
			
			// Calls F++ and creates a new FaceSet for the organization
			$album = Face::createAlbum($organizationAccount . "-".$gender."-" . $facesetIndex, [], ['tags' => $organizationId]);
				
			$log = fopen("debug.txt","a");
			fwrite($log,$album->getId() ."\n");
			fclose($log);
			
			// Get the FaceSet token from F++
			$facesetToken = $album->getId();

			// Create the new Faceset into the database
			$facesetId = Faceset::create([
				  'facesetToken' => $facesetToken,
				  'organizationId' => $organizationId,
				  'gender' => $gender
			])->id;

			// Grab the newly created Faceset data
			$faceset = Faceset::where('organizationId',$organizationId)
							->where('gender',$gender)
							->where('faces',"<", "500")
							->get();
							
			// Create the directory for the new Faceset Token
			Storage::makeDirectory('face/'.$organizationAccount.'/'.$gender.'/'.$facesetToken, 0775, true); 
		}
					
		return $faceset;
	}
	/**
   * Stores detected faces into their gender sorted Facesets
   *
   * @param  $faceInfoArra : Detected faces info array
   *         $faceIdArray : Detected faces Id array
   * @return void
   */
  public function storeSortedFaces($faceArray){
      
		$log = fopen("debug.txt","a");
		fwrite($log,"Starting storeSortedFaces()\n");
		
		// Get the organizationID and name for the logged in user
		$organizationId = Auth::user()->organizationId;
		$organizationName = Organization::find($organizationId)->name;
		$organizationAccount = Organization::find($organizationId)->account;	// account name used for data storage, etc.
		
		// How many faces we have in our array to add to this faceset
		$totalCount = count($faceArray);

		fwrite($log,"Processing " . $totalCount . " faces\n");
		
		$processedFaces = [];
		
		// Process the faces as long as we have at least one
		if(count($faceArray) >= 1) 
		{
			for ($i=0; $i < $totalCount; $i++) 
			{
				fwrite($log,"Processing face " . $i . "\n");
				
				// Process the batch
				if ( ($i + 1) % 6 == 0)
				{
					FaceModel::insert($processedFaces);
					$processedFaces = [];
					fwrite($log,"Inserted batch of 5\n");
				}
				
				// Gender
				$activeFaceset = $this->getActiveFaceset($faceArray[$i]->gender);
				fwrite($log,$activeFaceset . "\n");
                fwrite($log,$faceArray[$i]->path . "\n");
				
				fwrite($log,"addFacesintoFaceSet::token=" . $activeFaceset[0]->facesetToken . "::faceArray[$i]->faceToken=" . $faceArray[$i]->faceToken . "\n");
				
				// Add the face into the new face set
				$this->addFacesintoFaceSet($activeFaceset[0]->facesetToken, $faceArray[$i]->faceToken);
				
				$oldPath = $faceArray[$i]->path;
				$dir = explode("storage/",$oldPath);
				$dir = $dir[1];
				$oldPath = "public/" . $dir;
				
				// Get the file extension
				$ext = explode("/", $oldPath);
				$ext = $ext[count($ext) - 1];
				
				$newPath = 'face/' . $organizationAccount . "/" . $faceArray[$i]->gender . "/" . $activeFaceset[0]->facesetToken . "/" . $ext;
		
				fwrite($log,"Old Path: " . $oldPath . "\n");
				fwrite($log,"New Path: " . 'public/'.$newPath . "\n");
				
				// Move the image out of temp storage and into the proper one based on Faceset
				Storage::move($oldPath,'public/'.$newPath);
				
				$newPath = url('/storage/' . $newPath);
		
				$processedFaces[] = array(
				  'faceToken' => $faceArray[$i]->faceToken,
				  'facesetId' => $activeFaceset[0]->id,
				  'imageId' => $faceArray[$i]->imageId,
				  'identifiers' => Crypt::encryptString($faceArray[$i]->identifiers),
				  'gender' => $faceArray[$i]->gender,
				  'savedPath' => $newPath
				);
			
				Faceset::where('id', $activeFaceset[0]->id)->increment('faces');
				
				// Sleep for a second to prevent Concurrency issues.  REMOVE WHEN USING PAID KEY
				//time_nanosleep(0, 500000000);
				sleep(1);
			}
			
			FaceModel::insert($processedFaces);
		}
		
		fclose($log);
  }	  
	  
	  
  /**
   * Create a faceset or add a face in existing faceset and store image on local
   *
   * @param  $faceInfoArra : Detected faces info array
   *         $faceIdArray : Detected faces Id array
   * @return void
   */
  public function createMultipleFacesAndStores($faceInfoArray, $faceIdArray){
      
		// Get the organizationID and name for the logged in user
		$organizationId = Auth::user()->organizationId;
		$organizationName = Organization::find($organizationId)->name;
	  
		//$active_facesetToken = Organization::find($organizationId)->active_facesetToken;

		// Count how many facesets already exist for the organization and increment it by 1 for the new one
		$facesetIndex = Faceset::where('organizationId', $organizationId)->count() + 1;
		
		// Auto create the name for the new faceset based on organization name
		$faceSetName = $organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex;
		
		// How many faces we have in our array to add to this faceset
		$totalCount = count($faceIdArray);

		// Break our faces into chunks of 5 for processing
		$faceIdArray = array_chunk($faceIdArray, 5);

		// Call API to create a new faceset and get the returned faceset token ID
		$facesetToken = $this->createFaceSet($faceSetName, $faceIdArray[0], $organizationId);

		// Process the faces as long as we have at least one
		if(count($faceIdArray) > 1) 
		{
			for ($i=1; $i < count($faceIdArray); $i++) 
			{
				// Add the face into the new face set
				$this->addFacesintoFaceSet($facesetToken, $faceIdArray[$i]);
			}  
		}
      
		// Insert the faceset into the DB
		$facesetId = Faceset::create([
			'facesetToken' => $facesetToken,
			'organizationId' => $organizationId
		])->id;

		$facesArray = [];

		// Parse through our faces and insert the data into the DB
		for ($i=0; $i < $totalCount; $i++) 
		{ 
			$facesArray[] = array(
			  'faceToken' => $faceInfoArray[$i]->faceId,
			  'facesetId' => $facesetId,
			  'imageId' => $faceInfoArray[$i]->imageId,
			  'identifiers' => Crypt::encryptString($faceInfoArray[$i]->identifiers),
			  'gender' => $faceInfoArray[$i]->gender,
			  'savedPath' => $faceInfoArray[$i]->path
			);
		}

	    FaceModel::insert($facesArray);
		
		// Set the active faceset token for the organization for the next upload.
		Organization::where('id', $organizationId)->update(['active_facesetToken' => $facesetToken]);
    }

    /**
     * Process an image manually or via CSV file
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function store(Request $request)
    {

		$organizationId = Auth::user()->organizationId;
		$organizationName = Organization::find($organizationId)->name;			// Plain text name of account
		$organizationAccount = Organization::find($organizationId)->account;	// account name used for data storage, etc.
			
		if($request->isCsv == true)
		{
		    // UPLOADING A CSV FILE, and insert the contents to the face_tmps table.

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
			
			//********************** REMOVE THIS WHEN DONE DEBUGGING ****************
			$res = new \stdClass;
			$res->status = 200;
			$res->msg = 'Uploaded successfully.';
			echo json_encode( $res );
		}
		else
		{ 
			//****** Image is a single image uploaded by the user *******//
            // image upload to the s3 storage and faceindexing the image to the aws rekognition.
			ini_set('max_execution_time', 300);
			
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
            $res->msg = 'Uploaded successfully.';
            echo json_encode( $res );
		}
  }

	public function retrieve_remote_file_size($url){
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);

		$data = curl_exec($ch);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

		curl_close($ch);
		return $size;
	}
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int      $id
     *
     * @return void
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function destroy($id)
    {
    }
}
