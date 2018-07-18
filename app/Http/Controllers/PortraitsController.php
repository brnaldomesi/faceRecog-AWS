<?php
use luchaninov\CsvFileLoader;
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use App\Facepp;
use Illuminate\Http\Request;
use Face;
use Auth;
use App\Face as FaceModel;
use App\Faceset;
use App\Organization;
use Illuminate\Support\Facades\Input;
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
      
	  //$portraitData = $request->portraitData;
      
	  ini_set('max_execution_time', 300);
      $noError = false;
	  
	  // ******* FIX: Allow the user to select the minimum confidence level for filtering results
	  $minConfidence = 65;
	  
      // Get our list of Facesets for the organization of the user logged in
	  // 
	  // **NOTE:
	  // We will need to expand this so the search will also grab Facesets for other organizations that opted-in to sharing their Faceset data.
      $faceSets = Faceset::select('id', 'facesetToken')->where('organizationId', $organizationId)->get();
      $res = [];
	  
	  // Get the path of the uploaded image
      $filename = $request->searchPortraitInput->getPathName();
      $params['image_file'] = new \CURLFile($filename);

	  $results = [];
	  
	  // Loop through our facesets and find matches
      for($i = 0; $i < count($faceSets); $i++) {
        $faceSetId = $faceSets[$i]->id;
        $facesCount = FaceModel::where('facesetId', $faceSetId)->count();
        
		//Set the limit of return_result_count
		// ******* FIX: Allow the user to select the max # of returns from the Search screen
		$maxResults = 5;
		
        $return_result_count = $facesCount < $maxResults ? $facesCount : $maxResults;

        $params['return_result_count'] = $return_result_count;
        $params['faceset_token'] = $faceSets[$i]->facesetToken;

        while($noError === false || !$isFaceDetected) {
          // $searchResults = Face::search($request->searchPortraitInput, $faceSets[$i]->facesetToken, ['return_result_count' => $return_result_count]);
          
		  // Call Search API and pass the Faceset token and the # of requested results
		  $searchResults = $this->facepp->execute('/search', $params);
          $noError = $searchResults;
		  
		  // Set isFaceDetected if there are matches in this faceSet so we can break the loop
          $isFaceDetected = isset(json_decode( $searchResults )->faces);
        }
		
        $noError = false;
        $searchResults = json_decode( $searchResults );
        
		// API did not detect a face in the uploaded image.  Either no face or low quality image.
        if(count($searchResults->faces) == 0) {
          $res['status'] = 201;
          $res['msg'] = 'No faces were detected in the image';
          echo json_encode( $res );
          return;
        }

		// ??
        $filteredCount_per_faceSet = count($searchResults->results);
        $filteredResult_per_faceSet = $searchResults->results;   
        $resultPer_faceSet = [];
		
		

        for($j = 0; $j < $filteredCount_per_faceSet; $j++) {
          $faceToken = $filteredResult_per_faceSet[$j]->face_token;
		  $confidence = $filteredResult_per_faceSet[$j]->confidence;
		  
		  // If detected face is >= the minimum confidence level, get the face information from the DB
          if($confidence >= $minConfidence) {
            
			// Set our Face object
			FaceModel::where('faceToken', $faceToken)->increment('faceMatches');
			
			// Get the path to the face image
            $savedPath = FaceModel::where('faceToken', $faceToken)->value('savedPath');
            
			// Set the name and DOB for the face
            $name = FaceModel::where('faceToken', $faceToken)->value('name');
            $dob = FaceModel::where('faceToken', $faceToken)->value('dob');

			
            //$resultPer_faceSet[] = array_merge((array)$filteredResult_per_faceSet[$j], ['savedPath' => $savedPath, 'name' => $name, 'dob' => $dob, 'confidence' => $confidence]);
			array_push($results, ['savedPath' => $savedPath, 'name' => $name, 'dob' => $dob, 'confidence' => $confidence]);
			
          }
        }
		
        //$res['status'] = 200;
        //$res[$faceSetId] = $resultPer_faceSet;

      }
	  
	  array_push($results, ['status' => '200']);
	  
	  // Increment our search count for the organization
      Organization::find($organizationId)->stat->increment('searches');
      //echo json_encode( $res );
	  echo json_encode( $results );
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
							$url = $csvLine[0];
							$path = 'public/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex . '/' . $faceId . '.png';
							$faceInfoArray[] = $detectedFaceItem;
							$faceIdArray[] = $faceId;
							
							$contents = file_get_contents($url);
							Storage::put($path, $contents);
							
							$path = url('/') . '/storage/' . $path;
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

				$path = $request->portraitInput->storeAs('public/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex, $faceId . "." . $ext);
				$path = url('/') . '/storage/' . $path;

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

				$path = $request->portraitInput->storeAs('public/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex, $faceId . "." . $ext);
				$path = url('/') . '/storage/' . $path;

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
