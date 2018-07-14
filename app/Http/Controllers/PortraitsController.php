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
     * Search and show the result.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function search(Request $request) {
      
      $organizationId = Auth::user()->organizationId;
      //$portraitData = $request->portraitData;
      ini_set('max_execution_time', 300);
      $noError = false;
      //Call face++ detect api
      $faceSets = Faceset::select('id', 'facesetToken')->where('organizationId', $organizationId)->get();
      $res = [];
      $filename = $request->searchPortraitInput->getPathName();
      $params['image_file'] = new \CURLFile($filename);

      for($i = 0; $i < count($faceSets); $i++) {
        $faceSetId = $faceSets[$i]->id;
        $facesCount = FaceModel::where('facesetId', $faceSetId)->count();
        //Set the limit of return_result_count
        $return_result_count = $facesCount < 5 ? $facesCount : 5;

        $params['return_result_count'] = $return_result_count;
        $params['faceset_token'] = $faceSets[$i]->facesetToken;


        while($noError === false || !$isFaceDetected) {
          // $searchResults = Face::search($request->searchPortraitInput, $faceSets[$i]->facesetToken, ['return_result_count' => $return_result_count]);
          $searchResults = $this->facepp->execute('/search', $params);
          $noError = $searchResults;
          $isFaceDetected = isset(json_decode( $searchResults )->faces);
        }
        $noError = false;
        $searchResults = json_decode( $searchResults );
        
        if(count($searchResults->faces) == 0) {
          $res['status'] = 201;
          $res['msg'] = 'This image is not portrait. Please select portrait.';
          echo json_encode( $res );
          return;
        }

        $filteredCount_per_faceSet = count($searchResults->results);
        $filteredResult_per_faceSet = $searchResults->results;   
        $resultPer_faceSet = [];

        for($j = 0; $j < $filteredCount_per_faceSet; $j++) {
          $faceToken = $filteredResult_per_faceSet[$j]->face_token;
          $confidence = $filteredResult_per_faceSet[$j]->confidence;
          if($confidence > 70) { //If face is matched
            FaceModel::where('faceToken', $faceToken)->increment('faceMatches');
            $savedPath = FaceModel::where('faceToken', $faceToken)->value('savedPath');
            
            $name = FaceModel::where('faceToken', $faceToken)->value('name');
            $dob = FaceModel::where('faceToken', $faceToken)->value('dob');
            $resultPer_faceSet[] = array_merge((array)$filteredResult_per_faceSet[$j], ['savedPath' => $savedPath, 'name' => $name, 'dob' => $dob]);
          }
        }
        $res['status'] = 200;
        $res[$faceSetId] = $resultPer_faceSet;
      }
      Organization::find($organizationId)->stat->increment('searches');
      echo json_encode( $res );
    }


    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organizationId;
        $faceSets = Faceset::select('id')->where('organizationId', $organizationId)->get();
        return view('portraits.index')->with('faceSets', $faceSets);
    }

    /**
     * Show the form for creating a new resource.
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
    public function detectFace($params){
      $errorReturnVal = false;
      while($errorReturnVal === false) {

          //$detectApiCallResult = $this->detectFace($filename);
          $detectApiCallResult = $this->facepp->execute('/detect', $params);
          $errorReturnVal = $detectApiCallResult;
          $returnVal = json_decode($detectApiCallResult);
          $isErrorDetected = isset($returnVal->error_message);
      }

      return json_decode($detectApiCallResult);
    }


    public function createFaceSet($faceSetName, $faceIdArray, $organizationId) {
      ini_set('max_execution_time', 300);
      $noError = false;
      while($noError === false) {
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
      
      $organizationId = Auth::user()->organizationId;
      $organizationName = Organization::find($organizationId)->name;
      //$active_facesetToken = Organization::find($organizationId)->active_facesetToken;
      $facesetIndex = Faceset::where('organizationId', $organizationId)->count() + 1;
      $faceSetName = $organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex;
      $totalCount = count($faceIdArray);

      $faceIdArray = array_chunk($faceIdArray, 5);

      $facesetToken = $this->createFaceSet($faceSetName, $faceIdArray[0], $organizationId);

      if(count($faceIdArray) > 1) {
        for ($i=1; $i < count($faceIdArray); $i++) {
          $this->addFacesintoFaceSet($facesetToken, $faceIdArray[$i]);
        }  
      }
      
      $facesetId = Faceset::create([
          'facesetToken' => $facesetToken,
          'organizationId' => $organizationId
      ])->id;

      $facesArray = [];

      for ($i=0; $i < $totalCount; $i++) { 
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
      Organization::where('id', $organizationId)->update(['active_facesetToken' => $facesetToken]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function store(Request $request)
    {
      if($request->isCsv == true){ //Case CSV
        
        $header = true;
        $faceInfoArray = [];
        $faceIdArray = [];
        $organizationId = Auth::user()->organizationId;
        $organizationName = Organization::find($organizationId)->name;
        $facesetIndex = Faceset::where('organizationId', $organizationId)->count() + 1;
        $handle = fopen($request->csv->getPathName(), "r");

        ini_set('auto_detect_line_endings', true);
        $handle = fopen($request->csv->getPathName(), "r");

        while ($csvLine = fgetcsv($handle, 1024)) {
          if ($header) {
            $header = false;
          } else {
            if(count($csvLine) == 3) {
              $params = [];
              $params['image_url'] = $csvLine[0];
              $detectApiCallResult = $this->facepp->execute('/detect', $params);
              $detectApiCallResult = json_decode($detectApiCallResult);
              if(isset($detectApiCallResult->error_message)) {
                continue;
              }

              if(count($detectApiCallResult->faces) > 0) {
                $imageId =  $detectApiCallResult->image_id;
                $faceId =  $detectApiCallResult->faces[0]->face_token;
                $detectedFaceItem = new \stdClass;
                $detectedFaceItem->imageId = $imageId;
                $detectedFaceItem->faceId = $faceId;
                $detectedFaceItem->name = $csvLine[1];
                $detectedFaceItem->dobDate = date('Y-m-d', 0);
                
                $url = $csvLine[0];
                $path = 'public/' . $organizationName . "-" . $organizationId . "/faceset-" . $facesetIndex . '/' . $faceId . '.png';
                $faceInfoArray[] = $detectedFaceItem;
                $faceIdArray[] = $faceId;
                $contents = file_get_contents($url);
                Storage::put($path, $contents);
                $path = url('/') . '/storage/' . $path;
                $detectedFaceItem->path = $path;
              }
              else {
                $errorUrlList[] = $csvLine[0];
              }
            }
          }
        }
        fclose($handle);
        $this->createMultipleFacesAndStores($faceInfoArray, $faceIdArray);
        $res = new \stdClass;
        $res->status = 200;
        $res->msg = 'Uploaded successfully.';
        echo json_encode( $res );
       
      }
      else{ //Case Upload file from local disk
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
          $res->msg = 'Please select a portrait';
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
        if(is_null($active_facesetToken)) { // No faceset is created
          while($noError === false) {
            $facesetIndex++;
            $album = Face::createAlbum($organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex, [
              $faceId
            ], ['tags' => $organizationId]);
            $noError = $album;
            if($noError === false)
            {
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
        else{
          $facesetId = Faceset::where('facesetToken', $active_facesetToken)->value('id');

          while($noError === false) {
            $isAdded = Face::addIntoAlbum($active_facesetToken, [$faceId]);
            $noError = $isAdded;
          }
          $noError = false;

          if(is_null($isAdded)) { //Faceset reaches limit so create new faceset
            while($noError === false) {
              $facesetIndex++;
              $newAlbum = Face::createAlbum($organizationName . "-" . $organizationId . "-faceset-" . $facesetIndex, [
                $faceId
              ], ['tags' => $organizationId]);
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
