<?php

namespace App\Utils;

use App\Models\Face as FaceModel;
use App\Models\Organization;
use App\Models\FacesetSharing;
use App\Models\Faceset;

use Illuminate\Support\Facades\Crypt;

use App\Utils\Facepp;

define ('MIN_CONFIDENCE', 65);
define ('TOP_COUNT', 5);

class FaceSearch
{

	/**
	 * Perform a face search and return its result
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  string $response_type
	 *				'MANUAL_SEARCH' : used in Search(Legacy) page which searches for manually uploaded file (LEGACY)
	 *				'CASE_SEARCH' : used in Case page which searches for case image file
	 * @param  bool $faceset_after
	 * 				null : performs search for all facesets for organization
	 * 				not null : performs search for facesets that was updated after $last_search_date 
	 *
	 * @return void
	 */

	public static function search($search_file_path, $organ_id, $gender, $response_type = 'CASE_SEARCH', $faceset_after = null) {

		// Increment our search count for the organization
		$log = fopen("debug.txt","a");
		
		fwrite($log,"Search started on gender " . $gender . " for " . $search_file_path ."\\nn");
		
		Organization::find($organ_id)->stat->increment('searches');

		ini_set('max_execution_time', 300);
		
		$facepp = new Facepp();
		$facepp->api_key = config('face.providers.face_plus_plus.api_key');
        $facepp->api_secret = config('face.providers.face_plus_plus.api_secret');

		$noError = false;
		
		// ******* FIX: Allow the user to select the minimum confidence level for filtering results
		// MIN_CONFIDENCE
		
		// Get our list of Facesets for the organization of the user logged in
		// 
		// **NOTE:
		// We will need to expand this so the search will also grab Facesets for other organizations that opted-in to sharing their Faceset data.

		$organization = FacesetSharing::where([
				['organization_owner', $organ_id], ['status', 'ACTIVE']
			])
			->get()->pluck('organization_requestor')->push($organ_id);

		//$faceSets = Faceset::whereIn('organizationId', $organization)
		//	->when(!is_null($gender), function ($query, $gender) {
		//		return $query->where('gender', $gender);
		//	})
		//	->get();
			
		$faceSets = Faceset::where('organizationId',$organization)
		->where('gender',$gender)
		->get();
		
		fwrite($log,"**FaceSets**\n".$faceSets ."\n\n");

		$params = array('image_file' => new \CURLFile(realpath($search_file_path)));

		$results = [];
		
		// Loop through our facesets and find matches
		for ($i = 0; $i < count($faceSets); $i++) {
			if (!is_null($faceset_after) && $faceSets[$i]->updated_at < $faceset_after) {
				continue;
			}

			$faceSetId = $faceSets[$i]->id;
			$facesCount = FaceModel::where('facesetId', $faceSetId)->count();
			$searchResults = '';
			$noError = false;
			$isFaceDetected = '';
			
			fwrite($log,"Checking Faceset " . $faceSets[$i]->facesetToken ."\n\n");
			
			$return_result_count = min($facesCount, TOP_COUNT);

			$params['return_result_count'] = $return_result_count;
			$params['faceset_token'] = $faceSets[$i]->facesetToken;

			while ($noError === false || !$isFaceDetected) {
				// $searchResults = Face::search($request->searchPortraitInput, $faceSets[$i]->facesetToken, ['return_result_count' => $return_result_count]);
					
				// Call Search API and pass the Faceset token and the # of requested results
				$searchResults = $facepp->execute('/search', $params);
				$noError = $searchResults;
				
				// Set isFaceDetected if there are matches in this faceSet so we can break the loop
				$isFaceDetected = isset(json_decode( $searchResults )->faces);
				
				$json = json_decode($searchResults);
				
				if (!$json->error_message = '') {
					break;
				}
				sleep(1);
			}
			
			fwrite($log,"***Results***\n\n" . $searchResults ."\n");
			
			if ($isFaceDetected)
			{
				$noError = false;
				$searchResults = json_decode( $searchResults );
					
				// API did not detect a face in the uploaded image.  Either no face or low quality image.
				if (count($searchResults->faces) == 0) {
					return ['status' => 204, 'msg' => 'No faces were detected in the image'];
				}

				// ??
				$filteredCount_per_faceSet = count($searchResults->results);
				$filteredResult_per_faceSet = $searchResults->results;   
				$resultPer_faceSet = [];
			
				for ($j = 0; $j < $filteredCount_per_faceSet; $j++) {
					$faceToken = $filteredResult_per_faceSet[$j]->face_token;
					$confidence = $filteredResult_per_faceSet[$j]->confidence;
				
					// If detected face is >= the minimum confidence level, get the face information from the DB
					if ($confidence >= MIN_CONFIDENCE) {
							
						// Get our Face object
						
						$face = FaceModel::where('faceToken', $faceToken)->first();

						if (!is_null($face)) {
							$face->increment('faceMatches');
							$face_info = [
								'faceToken'	  => $face->faceToken,
								'savedPath'	  => $face->savedPath,
								'facesetId'	  => $face->facesetId,
								'identifiers' => Crypt::decryptString($face->identifiers),
								'gender'	  => $face->gender,
								'matches'	  => $face->faceMatches,
								'confidence'  => $confidence 
							];
					
							//$resultPer_faceSet[] = array_merge((array)$filteredResult_per_faceSet[$j], ['savedPath' => $savedPath, 'name' => $name, 'dob' => $dob, 'confidence' => $confidence]);
							
							if ($response_type == 'MANUAL_SEARCH') {
								array_push($results, $face_info);
							} else if ($response_type == 'CASE_SEARCH') {
								array_push($resultPer_faceSet, $face_info);
							}
						}
					}
				}
			
				if ($response_type == 'CASE_SEARCH' && count($resultPer_faceSet) > 0) {
					array_push($results, $resultPer_faceSet);
				}
				// $res['status'] = 200;
				// $res[$faceSetId] = $resultPer_faceSet;
			}
		}
		
		fclose($log);
		
		if ($response_type == 'MANUAL_SEARCH') {
		//	array_push($results, ['status' => '200']);
		//	return $results;
		}
		
		return ['status' => 200, 'result' => $results];
	}
	
}
