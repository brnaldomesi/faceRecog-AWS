<?php
	
namespace App\Utils;
use Face;

class FaceSetManage
{
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
}