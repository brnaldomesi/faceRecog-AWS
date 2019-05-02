<?php

namespace App\Utils;

use App\Models\Face as FaceModel;
use App\Models\Organization;
use App\Models\FacesetSharing;
use App\Models\Faceset;

use Illuminate\Support\Facades\Crypt;

use App\Utils\Facepp;
use App\Utils\FaceSetManage;
use Face;

define ('MIN_CONFIDENCE', 70);
define ('TOP_COUNT', 5);

class FaceSearch
{

  public function executeSearch($facepp, $params)
  {
    $noError = false;
    $isFaceDetected = '';

    while ($noError === false || !$isFaceDetected)
    {
        
      // Call Search API and pass the Faceset token and the # of requested results
      $searchResults = $facepp->execute('/search', $params);
      $noError = $searchResults;
      
      // Set isFaceDetected if there are matches in this faceSet so we can break the loop
      $isFaceDetected = isset(json_decode( $searchResults )->faces);
      $returnVal['isFaceDetected'] = $isFaceDetected;
      $returnVal['searchResults'] = $searchResults;

      $json = json_decode($searchResults);
      if(!is_null($json))
      {
        if (!$json->error_message = '') {
          $returnVal['searchResults'] = $searchResults;
          return $returnVal;
        }
      }
      // Slow it down to prevent Queries Per Second errors
      sleep(1);
    }
    return $returnVal;
  }

  public function getFaceArrayPerFaceset($params, $faceSet, $facepp, $log)
  {
    $faceSet->isModifying = 1;
    $faceSet->save();

    $faceSetId = $faceSet->id;
    $facesCount = FaceModel::where('facesetId', $faceSetId)->count();
    $searchResults = '';
    $noError = false;
    $isFaceDetected = '';
    
    fwrite($log,"Checking Faceset " . $faceSet->facesetToken ."\n\n");
    
    $return_result_count = min($facesCount, TOP_COUNT);

    $params['return_result_count'] = $return_result_count;
    $facesetToken = $faceSet->facesetToken;
    $params['faceset_token'] = $facesetToken;
    $searchedResults = $this->executeSearch($facepp, $params);
    $faceArrPerFaceset = [];

    if(!$searchedResults['isFaceDetected'])
    {
      fwrite($log,"No similar faces found in Faceset " . $facesetToken . "\n");
    }
    else 
    {
      $decodedResults = json_decode($searchedResults['searchResults']);
      if(count($decodedResults->faces) == 0)
      {
        $faceSet->isModifying = 0;
        $faceSet->save();
        return null;
      }

      $filteredResult_per_faceSet = $decodedResults->results;
      
      $originator = Organization::where('id',$faceSet->organizationId)->get();
      if($facesCount <= TOP_COUNT)
      {
        for($j=0; $j < $facesCount; $j++)
        {
          $confidence = $filteredResult_per_faceSet[$j]->confidence;
          if($confidence >= MIN_CONFIDENCE)
          {
            $faceToken = $filteredResult_per_faceSet[$j]->face_token;
            $face = FaceModel::where('faceToken', $faceToken)->first();

            if (!is_null($face)) {
              $face->increment('faceMatches');
              
              $face_info = [
                'faceToken'     => $face->faceToken,
                'savedPath'     => $face->savedPath,
                'facesetId'     => $face->facesetId,
                'identifiers'   => Crypt::decryptString($face->identifiers),
                'gender'        => $face->gender,
                'matches'       => $face->faceMatches,
                'confidence'    => $confidence,
                'organization'  => $originator[0]->name
              ];
          
              array_push($faceArrPerFaceset, $face_info);
            }

          }
          else
          {
            if($j == 0)
            {
              fwrite($log,"No similar faces found in Faceset " . $facesetToken . "\n");
              $faceArrPerFaceset = [];
            }
            break;
          }
        }
      }
      else
      {
        $returnedFacesCount = count($filteredResult_per_faceSet);
        $lastFaceConfidence = $filteredResult_per_faceSet[$returnedFacesCount-1]->confidence;
        $isBiggerThanConfidence = $lastFaceConfidence >= MIN_CONFIDENCE ? 1 : 0;
        if($isBiggerThanConfidence == 1)
        {
          $tempFaceset = Face::createAlbum($facesetToken . "-temp", []);
        }
        while($isBiggerThanConfidence == 1)
        {
          for($j=0; $j < $returnedFacesCount; $j++)
          {
            $faceToken = $filteredResult_per_faceSet[$j]->face_token;
            $face = FaceModel::where('faceToken', $faceToken)->first();
            $confidence = $filteredResult_per_faceSet[$j]->confidence;

            if (!is_null($face)) {
              $face->increment('faceMatches');
              
              $face_info = [
                'faceToken'     => $face->faceToken,
                'savedPath'     => $face->savedPath,
                'facesetId'     => $face->facesetId,
                'identifiers'   => Crypt::decryptString($face->identifiers),
                'gender'        => $face->gender,
                'matches'       => $face->faceMatches,
                'confidence'    => $confidence,
                'organization'  => $originator[0]->name
              ];
          
              array_push($faceArrPerFaceset, $face_info);
            }
          }
          $faceTokenArray = array_column($filteredResult_per_faceSet, "face_token");
          if(!empty($faceTokenArray))
          {
            Face::addIntoAlbum($tempFaceset->getId(), $faceTokenArray);
            Face::removeFaceFromAlbum($facesetToken, $faceTokenArray);
          }

          $searchedResults = $this->executeSearch($facepp, $params);
          if($searchedResults['isFaceDetected'])
          {
            $filteredResult_per_faceSet = json_decode($searchedResults['searchResults'])->results;
            $returnedFacesCount = count($filteredResult_per_faceSet);

            if($returnedFacesCount > 0)
            {
              $lastFaceConfidence = $filteredResult_per_faceSet[$returnedFacesCount-1]->confidence;
              $isBiggerThanConfidence = $lastFaceConfidence >= MIN_CONFIDENCE ? 1 : 0;
            }
            else
            {
              $isBiggerThanConfidence = 0;
            }
          }
          else
          {
            $returnedFacesCount = 0;
            $isBiggerThanConfidence = 0;
          }
        }

        if(isset($tempFaceset))
        {
          $tempFaceArray = Face::album($tempFaceset->getId())->getFaces();
          $tempFaceArray = array_chunk($tempFaceArray, 5);

          // Process the faces as long as we have at least one
          for ($j=0; $j < count($tempFaceArray); $j++) 
          {
            // Add the face into the new face set
            Face::addIntoAlbum($facesetToken, $tempFaceArray[$j]);
          }
          Face::removeAlbum($tempFaceset->getId());
        }

        for($j=0; $j < $returnedFacesCount; $j++)
        {
          $confidence = $filteredResult_per_faceSet[$j]->confidence;
          if($confidence >= MIN_CONFIDENCE)
          {
            $faceToken = $filteredResult_per_faceSet[$j]->face_token;
            $face = FaceModel::where('faceToken', $faceToken)->first();

            if (!is_null($face)) {
              $face->increment('faceMatches');
              
              $face_info = [
                'faceToken'     => $face->faceToken,
                'savedPath'     => $face->savedPath,
                'facesetId'     => $face->facesetId,
                'identifiers'   => Crypt::decryptString($face->identifiers),
                'gender'        => $face->gender,
                'matches'       => $face->faceMatches,
                'confidence'    => $confidence,
                'organization'  => $originator[0]->name
              ];
          
              array_push($faceArrPerFaceset, $face_info);
            }
          }
          else
          {
            break;
          }
        }
      }
    }

    $faceSet->isModifying = 0;
    $faceSet->save();
    return $faceArrPerFaceset;
  }

  /**
   * Perform a face search by similarity score and return its result
   *
   * @param  \Illuminate\Http\Request $request
   * @param  string $response_type
   *        'MANUAL_SEARCH' : used in Search(Legacy) page which searches for manually uploaded file (LEGACY)
   *        'CASE_SEARCH' : used in Case page which searches for case image file
   * @param  bool $faceset_after
   *        null : performs search for all facesets for organization
   *        not null : performs search for facesets that was updated after $last_search_date 
   *
   * @return void
   */

  public static function searchBySimilarityScore($search_file_path, $organ_id, $gender, $response_type = 'CASE_SEARCH', $faceset_after = null) {
    // Increment our search count for the organization
    $self = new self;
    $log = fopen("debug.txt","a");
    fwrite($log,"Search started at " . date("h:i:sa") . " on gender " . $gender . " for " . $search_file_path ."\\nn");
    
    Organization::find($organ_id)->stat->increment('searches');

    ini_set('max_execution_time', 300);
    
    $facepp = new Facepp();
    $facepp->api_key = config('face.providers.face_plus_plus.api_key');
    $facepp->api_secret = config('face.providers.face_plus_plus.api_secret');

    $noError = false;
    
    // ******* FIX: Allow the user to select the minimum confidence level for filtering results
    // MIN_CONFIDENCE
    
    // Get a list of organization's that share data with this user's organization
    $organization = FacesetSharing::where([
        ['organization_requestor', $organ_id], ['status', 'ACTIVE']
    ])
    ->get()->pluck('organization_owner')->push($organ_id);

    $owner = FacesetSharing::where([
        ['organization_owner', $organ_id], ['status', 'ACTIVE']
    ])
    ->get()->pluck('organization_requestor');
    $organization = $organization->merge($owner);
    $organization = $organization->unique();

    // Build our array of FaceSets for Gender for all of the organization's that the user has access to
    $faceSets = Faceset::whereIn('organizationId', $organization)
        ->when(!is_null($gender), function ($query) use ($gender) {
            return $query->where('gender', $gender);
        })
        ->get();
    
    fwrite($log,"**FaceSets**\n".$faceSets ."\n\n");

    $params = array('image_file' => new \CURLFile(realpath($search_file_path)));

    $results = [];

    $modifyingList = [];
    $faceArray = [];

    for($i = 0; $i < count($faceSets); $i++)
    {
      if (!is_null($faceset_after) && $faceSets[$i]->updated_at < $faceset_after)
      {
        continue;
      }

      if($faceSets[$i]->isModifying == 1) 
      {
        $modifyingList[] = $faceSets[$i];
        continue;
      }

      $faceArrPerFaceset = $self->getFaceArrayPerFaceset($params, $faceSets[$i], $facepp, $log);

      if(is_null($faceArrPerFaceset))
      {
        unset($facepp);
        unset($self);
        fclose($log);
        return ['status' => 204, 'msg' => 'No faces were detected in the image'];
      }
      else
      {
        if(!empty($faceArrPerFaceset))
        {
          $faceArray[] = $faceArrPerFaceset;
        }
      }
    }

    for($i = 0; $i < count($modifyingList); $i++)
    {
      $timerFlag = $modifyingList[$i]->isModifying;
      $modifyingFacesetId = $modifyingList[$i]->id;
      $realTimeFaceset = Faceset::find($modifyingFacesetId);

      while($timerFlag == 1)
      {
        sleep(1);
        $modifyingFacesetId = $modifyingList[$i]->id;
        $realTimeFaceset = Faceset::find($modifyingFacesetId);
        $timerFlag = $realTimeFaceset->isModifying;
      }
      
      $faceArrPerFaceset = $self->getFaceArrayPerFaceset($params, $realTimeFaceset, $facepp, $log);
      if(is_null($faceArrPerFaceset))
      {
        unset($facepp);
        unset($self);
        fclose($log);
        return ['status' => 204, 'msg' => 'No faces were detected in the image'];
      }
      else
      {
        if(!empty($faceArrPerFaceset))
        {
          $faceArray[] = $faceArrPerFaceset;
        }
      }
    }
    unset($facepp);
    unset($self);
    fclose($log);
    return ['status' => 200, 'result' => $faceArray];
  }

  /**
   * Perform a face search and return its result
   *
   * @param  \Illuminate\Http\Request $request
   * @param  string $response_type
   *        'MANUAL_SEARCH' : used in Search(Legacy) page which searches for manually uploaded file (LEGACY)
   *        'CASE_SEARCH' : used in Case page which searches for case image file
   * @param  bool $faceset_after
   *        null : performs search for all facesets for organization
   *        not null : performs search for facesets that was updated after $last_search_date 
   *
   * @return void
   */

  public static function search($search_file_path, $organ_id, $gender, $response_type = 'CASE_SEARCH', $faceset_after = null)
  {
    // Increment our search count for the organization
    $log = fopen("debug.txt","a");
    fwrite($log,"Search started at " . date("h:i:sa") . " on gender " . $gender . " for " . $search_file_path ."\\nn");
    
    Organization::find($organ_id)->stat->increment('searches');

    ini_set('max_execution_time', 300);

    $facepp = new Facepp();
    $facepp->api_key = config('face.providers.face_plus_plus.api_key');
    $facepp->api_secret = config('face.providers.face_plus_plus.api_secret');

    $noError = false;
    
    // ******* FIX: Allow the user to select the minimum confidence level for filtering results
    // MIN_CONFIDENCE
    
    // Get a list of organization's that share data with this user's organization
    $organization = FacesetSharing::where([
        ['organization_requestor', $organ_id], ['status', 'ACTIVE']
      ])
      ->get()->pluck('organization_owner')->push($organ_id);

    $owner = FacesetSharing::where([
        ['organization_owner', $organ_id], ['status', 'ACTIVE']
      ])
      ->get()->pluck('organization_requestor');

    $organization = $organization->merge($owner);
    $organization = $organization->unique();

    // Build our array of FaceSets for Gender for all of the organization's that the user has access to
    $faceSets = Faceset::whereIn('organizationId', $organization)
        ->when(!is_null($gender), function ($query) use ($gender) {
            return $query->where('gender', $gender);
        })
        ->get();
    
    fwrite($log,"**FaceSets**\n".$faceSets ."\n\n");

    $params = array('image_file' => new \CURLFile(realpath($search_file_path)));

    $results = [];
    // Loop through our facesets and find matches
    for ($i = 0; $i < count($faceSets); $i++) 
    {
	  
      if (!is_null($faceset_after) && $faceSets[$i]->updated_at < $faceset_after) {
        continue;
      }

      $faceSetId = $faceSets[$i]->id;
      $facesCount = FaceModel::where('facesetId', $faceSetId)->count();
      $searchResults = '';
      $noError = false;
      $isFaceDetected = '';

      fwrite($log,"Checking Faceset " . $faceSets[$i]->facesetToken ." [" . date("h:i:sa") . "]\n\n");
      
      $return_result_count = min($facesCount, TOP_COUNT);

      $params['return_result_count'] = $return_result_count;
      $params['faceset_token'] = $faceSets[$i]->facesetToken;

      // Put us in a loop to search this each Faceset until we get Faces or F++ gives us an error response
      while ($noError === false || !$isFaceDetected) {
        // Call Search API and pass the Faceset token and the # of requested results
        $searchResults = $facepp->execute('/search', $params);
        $noError = $searchResults;
        
        // Set isFaceDetected if there are matches in this faceSet so we can break the loop
        $isFaceDetected = isset(json_decode( $searchResults )->faces);
        
        $json = json_decode($searchResults);
        
        if(!is_null($json))
        {
          if (!$json->error_message = '') {
            break;
          }
        }
        // Slow it down to prevent Queries Per Second errors
        //sleep(1);
		time_nanosleep(0, 400000000);
		fwrite($log,"FPP->Search...\n");
      }
      
      fwrite($log,"***Results***\n\n" . $searchResults ."\n");
      
      // Found similar faces in this Faceset.  Process them
      if ($isFaceDetected)
      {
        $noError = false;
        $searchResults = json_decode( $searchResults );
          
        // API did not detect a face in the uploaded image.  Either no face or low quality image.
        if (count($searchResults->faces) == 0) {
          unset($facepp);
          fclose($log);
          return ['status' => 204, 'msg' => 'No faces were detected in the image'];
        }

        // ??
        $filteredCount_per_faceSet = count($searchResults->results);
        $filteredResult_per_faceSet = $searchResults->results;   
        $resultPer_faceSet = [];
      
        // Get the name of the Organization this face belongs to
        $originator = Organization::where('id',$faceSets[$i]->organizationId)->get();
        fwrite($log,"Organization for this faceset is " . $faceSets[$i]->organizationId ."\n");
      
		$hitCount = 0;
	  
        for ($j = 0; $j < $filteredCount_per_faceSet; $j++) {
          $faceToken = $filteredResult_per_faceSet[$j]->face_token;
          $confidence = $filteredResult_per_faceSet[$j]->confidence;
        
          // If detected face is >= the minimum confidence level, get the face information from the DB
          if ($confidence >= MIN_CONFIDENCE) {
              
			$hitCount++;
			
            // Get our Face object
            $face = FaceModel::where('faceToken', $faceToken)->first();
               
            if (!is_null($face)) {
              $face->increment('faceMatches');
              
              $face_info = [
                'faceToken'     => $face->faceToken,
                'savedPath'     => $face->savedPath,
                'facesetId'     => $face->facesetId,
                'identifiers'   => Crypt::decryptString($face->identifiers),
                'gender'        => $face->gender,
                'matches'       => $face->faceMatches,
                'confidence'    => $confidence,
                'organization'  => $originator[0]->name
              ];
          
              if ($response_type == 'MANUAL_SEARCH') {
                array_push($results, $face_info);
              } else if ($response_type == 'CASE_SEARCH') {
                array_push($resultPer_faceSet, $face_info);
              }
            }
          }
        }

		fwrite($log,"Completed at [" . date("h:i:sa") . "]. Faceset had " . $hitCount . " hits above " . MIN_CONFIDENCE . "%\n");
		
		$hitCount = 0;
      
        if ($response_type == 'CASE_SEARCH' && count($resultPer_faceSet) > 0) {
          array_push($results, $resultPer_faceSet);
        }
        // $res['status'] = 200;
        // $res[$faceSetId] = $resultPer_faceSet;
      }
      else
      {
        // There were no similar faces in this Faceset.  Log this in the debug file
        fwrite($log,"No similar faces found in Faceset " . $faceSets[$i]->facesetToken . "\n");
      }
    }
	
	fwrite($log,"Search completed at " . date("h:i:sa") . "\n");
    fclose($log);
    
    if ($response_type == 'MANUAL_SEARCH') {
    //  array_push($results, ['status' => '200']);
    //  return $results;
    }
    unset($facepp);
    return ['status' => 200, 'result' => $results];
  }
  
}
