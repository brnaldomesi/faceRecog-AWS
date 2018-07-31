<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

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
		// **NOTE: NEED TO ENCRYPT THE NAME AND DOB
		for ($i=0; $i < $totalCount; $i++) 
		{ 
			$facesArray[] = array(
			  'faceToken' => $faceInfoArray[$i]->faceId,
			  'facesetId' => $facesetId,
			  'imageId' => $faceInfoArray[$i]->imageId,
			  'name' => $faceInfoArray[$i]->name,
			  'dob' => $faceInfoArray[$i]->dobDate,
			  'savedPath' => $faceInfoArray[$i]->path
			);
		}

	    FaceModel::insert($facesArray);
		
		// Set the active faceset token for the organization for the next upload.
		Organization::where('id', $organizationId)->update(['active_facesetToken' => $facesetToken]);
    }

    /**
     * Process a CSV file and mass add new images
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function store(Request $request)
    {
		if($request->isCsv == true)
		{ //Case CSV
        
			$header = true;
			$faceInfoArray = [];
			$faceIdArray = [];
			$organizationId = Auth::user()->organizationId;
			$organizationName = Organization::find($organizationId)->name;
			$facesetIndex = Faceset::where('organizationId', $organizationId)->count() + 1;
			$handle = fopen($request->csv->getPathName(), "r");

			ini_set('auto_detect_line_endings', true);
			
			$handle = fopen($request->csv->getPathName(), "r");

			while ($csvLine = fgetcsv($handle, 1024)) 
			{
				if ($header) 
				{
					$header = false;
				} 
				else 
				{
					// Checks to make sure we have 3 fields for this row of data (image, name, dob).  Change this if we ever add more information for mass uploads
					if(count($csvLine) == 3) 
					{
						$params = [];

						// Grabs the URL for the image out of the CSV row
						$params['image_url'] = $csvLine[0];
						
						// Call API face detect and pass the image URL
						$detectApiCallResult = $this->facepp->execute('/detect', $params);
						$detectApiCallResult = json_decode($detectApiCallResult);
						
						if(isset($detectApiCallResult->error_message)) 
						{
							continue;
						}

						// The API found a face in the image.  Let's process it
						if(count($detectApiCallResult->faces) > 0) 
						{
							// Gets the image id from the API
							$imageId =  $detectApiCallResult->image_id;
					
							// Gets the assigned face token from the API
							$faceId =  $detectApiCallResult->faces[0]->face_token;
							
							$detectedFaceItem = new \stdClass;
							$detectedFaceItem->imageId = $imageId;
							$detectedFaceItem->faceId = $faceId;
							$detectedFaceItem->name = $csvLine[1];
							$detectedFaceItem->dobDate = date('Y-m-d', 0);
					
							// Set the local path to store the image on our server
							$path = 'face/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex . '/' . $faceId . ".png";
							$faceInfoArray[] = $detectedFaceItem;
							$faceIdArray[] = $faceId;
							Storage::put('public/' . $path, file_get_contents($csvLine[0]));
							$path = url('/storage/' . $path);

							$detectedFaceItem->path = $path;
						}
						else 
						{
							$errorUrlList[] = $csvLine[0];
						}
					}
				}
			}
		
			fclose($handle);
			
			// Store the face info in the DB
			$this->createMultipleFacesAndStores($faceInfoArray, $faceIdArray);
			$res = new \stdClass;
			$res->status = 200;
			$res->msg = 'Uploaded successfully.';
			echo json_encode( $res );
		}
		else
		{ 
			// Image is a single image uploaded by the user
			ini_set('max_execution_time', 300);
			
			$res = new \stdClass;

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

			if(count($detectApiCallResult->faces) == 0) { //Uploaded image isn't portrait.
			  $res->status = 201;
			  $res->msg = 'No faces were detected';
			  echo json_encode( $res );
			  return;
			}

			$imageId =  $detectApiCallResult->image_id;
			$faceId =  $detectApiCallResult->faces[0]->face_token;
			
			$organizationId = Auth::user()->organizationId;
			$organizationName = Organization::find($organizationId)->name;
			$noError = false;

			$active_facesetToken = Organization::find($organizationId)->active_facesetToken;
			$dobTime = strtotime($request->dob);
			$dobDate = date('Y-m-d',$dobTime);
			
			$originalFileName = $request->portraitInput->getClientOriginalName();
			$ext = explode(".", $originalFileName);
			$ext = $ext[count($ext) - 1];

			$facesetIndex = Faceset::where('organizationId', $organizationId)->count();
        
			if(is_null($active_facesetToken)) 
			{ // No faceset is created
				while($noError === false) 
				{
					$facesetIndex++;
					
					$album = Face::createAlbum($organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex, [$faceId], ['tags' => $organizationId]);
					$noError = $album;
					
					if($noError === false) {
						$facesetIndex--;
					}
				}
          
				$noError = false;

				$facesetToken = $album->getId();

				$facesetId = Faceset::create([
				  'facesetToken' => $facesetToken,
				  'organizationId' => $organizationId
				])->id;

				$path = 'face/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex;
				$filename = $faceId . "." . $ext;
				$request->portraitInput->storeAs('public/' . $path, $filename);
				$path = url('/storage/' . $path . "/" . $filename);

				FaceModel::create([
				  'faceToken' => $faceId,
				  'facesetId' => $facesetId,
				  'imageId' => $imageId,
				  'name' => $request->name,
				  'dob' => $dobDate,
				  'savedPath' => $path
				]);

				Organization::where('id', $organizationId)->update(['active_facesetToken' => $facesetToken]);
			}
			else
			{
				$facesetId = Faceset::where('facesetToken', $active_facesetToken)->value('id');

				while($noError === false) {
					$isAdded = Face::addIntoAlbum($active_facesetToken, [$faceId]);
					$noError = $isAdded;
				}
				
				$noError = false;

				if(is_null($isAdded)) 
				{ 
					//Faceset reaches limit so create new faceset
					while($noError === false) 
					{
						$facesetIndex++;
						
						$newAlbum = Face::createAlbum($organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex, [$faceId], ['tags' => $organizationId]);
						$noError = $newAlbum;
						
						if($noError === false)
						{
							$facesetIndex--;
						}
					}
            
					$noError = false;
            
					$facesetId = Faceset::create([
						'facesetToken' => $active_facesetToken,
						'organizationId' => $organizationId
					])->id;
					
					Organization::where('id', $organizationId)->update(['active_facesetToken' => $newAlbum->getId()]);
				}

				$path = 'face/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex;
				$filename = $faceId . "." . $ext;
				$request->portraitInput->storeAs('public/' . $path, $filename);
				$path = url('/storage/' . $path . "/" . $filename);

				FaceModel::create([
				  'faceToken' => $faceId,
				  'facesetId' => $facesetId,
				  'imageId' => $imageId,
				  'name' => $request->name,
				  'dob' => $dobDate,
				  'savedPath' => $path
				]);
			}

			$res->status = 200;
			$res->msg = 'Uploaded successfully.';
			echo json_encode( $res );
		}
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
