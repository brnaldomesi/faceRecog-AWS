<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

use luchaninov\CsvFileLoader;

use App\Models\Face as FaceModel;
use App\Models\User;
use App\Models\Faceset;
use App\Models\Organization;

use App\Utils\Facepp;
use App\Utils\FaceSearch;

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
        $this->middleware('auth');
        $this->facepp = new Facepp();
        $this->facepp->api_key = env('FACEPLUS_API_KEY');
        $this->facepp->api_secret = env('FACEPLUS_API_SECRET');
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
      
      $result = FaceSearch::search($request->searchPortraitInput->getPathName(), $organizationId, 'MANUAL_SEARCH');
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
	*  Handles the 2000 face limit and creation of new faceset when
	*  capacity is reached.
	*/
	public function getActiveFaceset($gender) {

		$organizationId = Auth::user()->organizationId;
		$organizationName = Organization::find($organizationId)->name;			// Plain text name of account
		$organizationAccount = Organization::find($organizationId)->account;
		
		// Get our active Faceset for this Organization and Gender
		$faceset = Faceset::where('organizationId',$organizationId)
					->where('gender',$gender)
					->where('faces',"<", "2000")
					->get();
					
		// No matches for this Org/Gender and less than 2000.  Create a new one
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
							->where('faces',"<", "2000")
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
		{ // UPLOADING A CSV FILE
        
			$log = fopen("debug.txt","a");
			
			$header = true; // CSV file has a header line
			$faceInfoArray = [];
			$faceIdArray = [];
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
					
					// Checks to make sure there is at least one row of data before we process.
					if(count($csvLine) > 1 && ($csvLine[2] == 'M' || $csvLine[2] == 'F')) 
					{
						fwrite($log,"Importing mugshot [Line " . $lineCount . " @ " . date("h:i:sa") . "]...\n");
						
						$params = [];

						// Grabs the URL for the image out of the CSV row
						$params['image_url'] = $csvLine[0];
						
						fwrite($log,$params['image_url'] . "\n");

						// Check the file size of the mugshot image to ensure it exists.
						$ch = curl_init($csvLine[0]);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
						curl_setopt($ch, CURLOPT_HEADER, TRUE);
						curl_setopt($ch, CURLOPT_NOBODY, TRUE);
						$data = curl_exec($ch);
						$imgSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

						curl_close($ch);
					
						if ($imgSize > 0)
						{
							// Call API face detect and pass the image URL
							$detectApiCallResult = $this->facepp->execute('/detect', $params);
							$detectApiCallResult = json_decode($detectApiCallResult);
							
							// Get the file extension
							$ext = explode(".", $params['image_url']);
							$ext = $ext[count($ext) - 1];
							
							if(isset($detectApiCallResult->error_message)) 
							{
								fwrite($log,$detectApiCallResult->error_message . "\n");
								
								continue;
							}

							// The API found a face in the image.  Let's process it
							if(count($detectApiCallResult->faces) > 0) 
							{
								// Unique ID for the uploaded image
								$imageId =  $detectApiCallResult->image_id;
						
								// Face Token for the detected face
								$faceToken =  $detectApiCallResult->faces[0]->face_token;
								
								$detectedFaceItem = new \stdClass;
								$detectedFaceItem->imageId = $imageId;
								$detectedFaceItem->faceToken = $faceToken;
								$detectedFaceItem->identifiers = $csvLine[1];
								$detectedFaceItem->gender = $csvLine[2];
								
								// Store the image to the file system until it is processed and assigned
								// a Faceset
								$path = 'face/' . $organizationAccount . "/" . $csvLine[2] . "/" . $faceToken . "." . $ext;
								
								fwrite($log,"Face Token " . $faceToken . "\n");
								fwrite($log,"Storing at " . $path . "\n");
								
								$faceArray[] = $detectedFaceItem;
								
								// Store the image on the server
								Storage::put('public/' . $path, file_get_contents($csvLine[0]));
								$path = url('/storage/' . $path);

								$detectedFaceItem->path = $path;
								
								fwrite($log,"Face detected.  Storing as " . $faceToken . "\n");
							}
							else 
							{
								// Log to file that no face was found
								$errorUrlList[] = $csvLine[0];
								fwrite($log,"No face detected\n");
							}
						}
						else
						{
							// Image did not exist at remote URL.  Log to file
							fwrite($log,"Image URL was invalid.  Skipping...\n");
						}
					}
					else
					{
						fwrite($log,"Row [" . $lineCount . "] gender was invalid. Skipping...\n");
					}
				}
				
				// Sleep for a half second to prevent Concurrency issues.  REMOVE WHEN USING PAID KEY
				time_nanosleep(0, 500000000);
			}

			// Close the CSV file
			fclose($handle);
			
			// close the log file
			fclose($log);
			
			// Process the images and assign Facesets
			$this->storeSortedFaces($faceArray);
			$res = new \stdClass;
			$res->status = 200;
			$res->msg = 'Uploaded successfully.';
			echo json_encode( $res );
		}
		else
		{ 
			//****** Image is a single image uploaded by the user *******//
			ini_set('max_execution_time', 300);
			
			$res = new \stdClass;

			// Get image filename
			$filename = $request->portraitInput->getPathName();
        
			//Detect face
			$params['image_file'] = new \CURLFile($filename);
			$detectApiCallResult = $this->facepp->execute('/detect', $params);
			$detectApiCallResult = json_decode($detectApiCallResult);

			if(isset($detectApiCallResult->error_message)) {
			  $res->status = 300;
			  $res->msg = $detectApiCallResult->error_message;
			  
			  echo json_encode( $res );
			  return;
			}

			// No face was detected in the image they tried to manually add
			if(count($detectApiCallResult->faces) == 0) {
			  $res->status = 201;
			  $res->msg = 'No faces were detected in this image';
			  echo json_encode( $res );
			  return;
			}

			// Unique ID for the uploaded image
			$imageId =  $detectApiCallResult->image_id;
			
			// Face token for the detected face
			$faceToken =  $detectApiCallResult->faces[0]->face_token;
			
			// Get the active FaceSet Token
			$activeFaceset = $this->getActiveFaceset($request->gender);
			$active_facesetToken = $activeFaceset[0]->facesetToken;

			$originalFileName = $request->portraitInput->getClientOriginalName();
			$ext = explode(".", $originalFileName);
			$ext = $ext[count($ext) - 1];

			$facesetIndex = $activeFaceset[0]->id;

			$path = 'face/' . $organizationAccount . "/" . $request->gender . "/" . $active_facesetToken . "/";
								
			$filename = $faceToken . "." . $ext;
			$request->portraitInput->storeAs('public/' . $path, $filename);
			$path = url('/storage/' . $path . $filename);

			// Manual upload of image.
				
			FaceModel::create([
			  'faceToken' => $faceToken,
			  'facesetId' => $activeFaceset[0]->id,
			  'imageId' => $imageId,
			  'identifiers' => Crypt::encryptString($request->identifiers),
			  'gender' => $request->gender,
			  'savedPath' => $path
			]);
			
			// Increment our faces
			Faceset::where('id', $activeFaceset[0]->id)->increment('faces');

			Face::addIntoAlbum($active_facesetToken,$faceToken);
			
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
