<?php
	
namespace App\Utils;

class ImageResize
{
	public static function work($orig_file_path, $width, $height, $save_file_path)
	{
		$source = NULL;
		$finfo = finfo_open(FILEINFO_MIME_TYPE);

		switch (finfo_file($finfo, $orig_file_path)) {
		case "image/png":
			$source = imagecreatefrompng($orig_file_path);
			break;
		case "image/jpeg":
			$source = imagecreatefromjpeg($orig_file_path);
			break;
		case "image/gif":
			$source = imagecreatefromgif($orig_file_path);
			break;
		default:
			return;
		}

		finfo_close($finfo);
		list($src_w, $src_h) = getimagesize($orig_file_path);
		$tw = $src_w;
		$th = $src_h;

		if ($width == 0 && $height == 0) {
			$width = $src_w;
			$height = $src_h;
		} else if ($height == 0) {
			$height = $width / $src_w * $src_h;
		} else if ($width == 0) {
			$width = $height * $src_w / $src_h;
		}

		if ($width > 0 && $height > 0) {
			$ratio = $width / $height;
			$th = $src_w / $ratio;
			$tw = $src_h * $ratio;

			if ($th > $src_h) {
				$th = $src_h;
			} else {
				$tw = $src_w;
			}
		}

		$thumb = imagecreatetruecolor($width, $height);
		imagecopyresized($thumb, $source,
						0, 0,
						($src_w - $tw) / 2, ($src_h - $th) / 2,
						$width, $height,
						$tw, $th);
		imagejpeg($thumb, $save_file_path);
		imagedestroy($thumb);
	}
}