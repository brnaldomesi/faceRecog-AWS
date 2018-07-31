<?php

namespace App\Utils;

use App\Models\Face as FaceModel;
use App\Models\Organization;
use App\Models\Faceset;

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
	 *
	 * @return void
	 */

	public static function search($search_file_path, $organ_id, $response_type = 'CASE_SEARCH') {

		// Increment our search count for the organization

		Organization::find($organ_id)->stat->increment('searches');

		ini_set('max_execution_time', 300);
		
		$facepp = new Facepp();
		$facepp->api_key = env('FACEPLUS_API_KEY');
        $facepp->api_secret = env('FACEPLUS_API_SECRET');

		$noError = false;
		
		// ******* FIX: Allow the user to select the minimum confidence level for filtering results
		// MIN_CONFIDENCE
		
		// Get our list of Facesets for the organization of the user logged in
		// 
		// **NOTE:
		// We will need to expand this so the search will also grab Facesets for other organizations that opted-in to sharing their Faceset data.

		$faceSets = Faceset::select('id', 'facesetToken')->where('organizationId', $organ_id)->get();

		$params = array('image_file' => new \CURLFile(realpath($search_file_path)));

		$results = [];
		
		// Loop through our facesets and find matches

		for ($i = 0; $i < count($faceSets); $i++) {

			$faceSetId = $faceSets[$i]->id;
			$facesCount = FaceModel::where('facesetId', $faceSetId)->count();
				
			//Set the limit of return_result_count
			// ******* FIX: Allow the user to select the max # of returns from the Search screen
		
			$return_result_count = $facesCount < TOP_COUNT ? $facesCount : TOP_COUNT;

			$params['return_result_count'] = $return_result_count;
			$params['faceset_token'] = $faceSets[$i]->facesetToken;

			while ($noError === false || !$isFaceDetected) {
				// $searchResults = Face::search($request->searchPortraitInput, $faceSets[$i]->facesetToken, ['return_result_count' => $return_result_count]);
					
				// Call Search API and pass the Faceset token and the # of requested results
				$searchResults = $facepp->execute('/search', $params);
				$noError = $searchResults;
			
				// Set isFaceDetected if there are matches in this faceSet so we can break the loop
				$isFaceDetected = isset(json_decode( $searchResults )->faces);
			}
		
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
							'faceToken'	=> $face->faceToken,
							'savedPath'	=> $face->savedPath,
							'facesetId'	=> $face->facesetId,
							'name'		=> $face->name,
							'dob'		=> $face->dob,
							'matches'	=> $face->faceMatches,
							'confidence' => $confidence 
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
		
			if ($response_type == 'CASE_SEARCH') {
				array_push($results, $resultPer_faceSet);
			}
			// $res['status'] = 200;
			// $res[$faceSetId] = $resultPer_faceSet;

		}
		
		if ($response_type == 'MANUAL_SEARCH') {
		//	array_push($results, ['status' => '200']);
		//	return $results;
		}
		
		return ['status' => 200, 'result' => $results];
	}
}
