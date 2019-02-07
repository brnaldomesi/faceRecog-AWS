<?php
	
	ini_set('display_errors','On');
	
	require '../../vendor/autoload.php';

	use Aws\Exception\AwsException;
	
	$client = new Aws\Rekognition\RekognitionClient([
		'version' => 'latest',
		'region' => 'us-west-2',
		'credentials' => [
		'key'	=> 'AKIAI5PDZAD73RSJ7LCA',
		'secret'	=> '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',
		]
	]);

	$result = $client->listCollections([]);
	
	if (isset($_FILES['searchImg']['name']))
	{
		//$organization = $_POST['organization'];
		//$deviceid = $_POST['deviceid'];
		
		$myFile = "uploadPhoto.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
//		fwrite($fh, "cname: " . $cname . "\n");
//		fwrite($fh, "DeviceID: " . $deviceid . "\n");
		
		$file = basename($_FILES['searchImg']['name']);
		$ext = pathinfo($file, PATHINFO_EXTENSION);
	
		// Get the width and height of the image
		list($img_width,$img_height) = getimagesize($_FILES['searchImg']['tmp_name']);	
		
		fwrite($fh, "Found a file: " . $file . "\n");
		fwrite($fh, "Dimensions: " . $img_width . "/" . $img_height . "\n");
		
		$src = imagecreatefromjpeg($_FILES['searchImg']['tmp_name']);
		$tmp = imagecreatetruecolor((int)$img_width,(int)$img_height);
		imagecopyresampled($tmp,$src,0,0,0,0,(int)$img_width,(int)$img_height,(int)$img_width,(int)$img_height);
		
		$location = 'searchimage.jpg';
		
		// Full server path to the folder

		imagejpeg($tmp,$location,99);
		imagedestroy($tmp);
		imagedestroy($src);
		
		$data = file_get_contents($location);
		$base64 = 'data:image/jpeg;base64,' . base64_encode($data);
	
		// Perform the face search
		$searchResult = $client->searchFacesByImage([
			'CollectionId' => 'maricopacountyjail_male_1548469684',
			'FaceMatchThreshold' => 70,
			'Image' => [
				'Bytes' => $data,
			],
			'MaxFaces' => 10
		]);
	
		
		
		$matches = $searchResult->get('FaceMatches');
		fwrite($fh, json_encode($matches) . "\n");
		fclose($fh);
		
		$json_output = json_encode($matches);
		echo $json_output;
	}
	else
	{
		// No image found. abort
		$json_output = '{"Search": "Failed"}';
		echo $json_output;
	}
?>