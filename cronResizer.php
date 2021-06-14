<?php
// EBID 2021-06-02
// ##################################################################
// # Configuration
// ##################################################################
define("IMG_ROOT_PATH",array(__DIR__.'/images/')); // this path will be searched for images
define("IMG_TYPE",array('jpg','gif','png')); // Files ending with *.gif, *.jpg ... will be converted
define("IMG_MAX_WIDTH",2000);
define("IMG_MAX_HEIGHT",2000);
define("IMG_MAX_DPI",150);
define("IMG_ORIG_PATH",'/.orig/'); // the original image will be copied to this path
define("IMG_CROP",false); // if you want to crop the image to IMG_MAX_WIDTH:IMG_MAX_HEIGHT set to true


// ##################################################################
// # Functions
// ##################################################################
// Iterates through all directories and looks for image files
function dirIterate() {
	$files = array();
	foreach(IMG_ROOT_PATH as $path) {
		$directory = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);
		$filter = new RecursiveCallbackFilterIterator($directory, 
			function ($current, $key, $iterator) {
				global $searchstring;
				$xx = $current->getFilename();
				if ($xx === '.' || $xx === '..' || $xx === '.orig') { return false; } // spezielle Verzeichnisse nicht
				if ($current->isDir()) { return true; } // alle anderen Verzeichnisse immer
				if(preg_match($searchstring,$xx,$matches) == 0) { return false; } // alle Dateien, die nicht die richtige Dateiendung haben ausschließen
				return true;
			});
		$iterator =  new RecursiveIteratorIterator($filter);
		foreach ($iterator as $itr) {
			$files[] = $itr->getPathname();
		}
	}
	return $files;
}

function checkFile($file) {
	$sizes = getSize($file);
	$newsizes = array ($sizes[0],$sizes[1]);
	$dpi = getDPI($file);
	$newdpi = array(IMG_MAX_DPI,IMG_MAX_DPI);
	$changed = false;
	$newstart = array(0,0,0,0);
	
	if(!IMG_CROP) { // kein Cropping vorgegeben
		if($sizes[0] > IMG_MAX_WIDTH) {
			$newsizes[0] = IMG_MAX_WIDTH;
			$newsizes[1] = IMG_MAX_WIDTH*$sizes[1]/$sizes[0];
			$changed = true;
		}
		if($sizes[1] > IMG_MAX_HEIGHT || $newsizes[1] > IMG_MAX_HEIGHT) {
			$newsizes[1] = IMG_MAX_HEIGHT;
			$newsizes[0] = IMG_MAX_HEIGHT*$sizes[0]/$sizes[1];
			$changed = true;
		}
	} else { // alle Bilder werden auf die Maximalmaße zugeschnitten
		if($sizes[0] > IMG_MAX_WIDTH && $sizes[1] > IMG_MAX_HEIGHT) {
			$newsizes[0] = IMG_MAX_WIDTH;
			$newsizes[1] = IMG_MAX_HEIGHT;
			$scX = $sizes[0]/IMG_MAX_WIDTH;
			$scY = $sizes[1]/IMG_MAX_HEIGHT;
			if($scX > $scY) {
				$oldsizes[0] = $scY*IMG_MAX_WIDTH;
				$oldsizes[1] = $sizes[1];
				$start[0] = 0.5*($sizes[0]-$oldsizes[0]);
				$start[1] = 0;
			} else {
				$oldsizes[0] = $sizes[0];
				$oldsizes[1] = $scX*IMG_MAX_HEIGHT;
				$start[0] = 0;
				$start[1] = 0.5*($sizes[1]-$oldsizes[1]);
			}
			$sizes = $oldsizes;
			$newstart = array(0,0,$start[0],$start[1]);
			$changed = true;
		}
	}
	
	$type = strtolower(substr($file,-4)); // DPI Ergebnisse stimmen derzeit nur für jpg => andere daher ausnehmen
	if($type == '.jpg') { 
		if ($dpi[0] > IMG_MAX_DPI || $dpi[1] > IMG_MAX_DPI) {
			$changed = true;
		}
	}
	
	if($changed) {
		reCalcFile($file, $sizes, $newsizes, $newstart, $newdpi);
	}
}

function getSize($file) {
	list($width, $height, $type, $attr) = getimagesize($file);
	return array($width, $height);
}

function getDPI($file){
    $a = fopen($file,'r');
    $string = fread($a,20);
    fclose($a);
    $data = bin2hex(substr($string,14,4));
    $x = substr($data,0,4);
    $y = substr($data,4,4);
    return array(hexdec($x),hexdec($y));
}

function getOrigFile($file) {
	$origpath = dirname($file).IMG_ORIG_PATH;
	$origfile = $origpath.basename($file);
	if(is_dir($origpath)) {
		return $origfile;
	} else {
		mkdir($origpath, 0775); // Erstelle das Verzeichnis .orig/ wenn es notwendig ist
		return $origfile;
	}
}

function reCalcFile($file, $sizes, $newsizes, $newstart, $newdpi) {
	global $counter;
	copy($file,getOrigFile($file)); // kopiert die Originaldatei auf .orig
	
	$type = strtolower(substr($file,-4)); // Datei in eine GD Ressource umwandeln
	switch($type) { 
		case '.gif':
			$imSrc = imagecreatefromgif($file);
			imageresolution($imSrc,IMG_MAX_DPI,IMG_MAX_DPI);
			break;
		case '.jpg':
			$imSrc = imagecreatefromjpeg($file);
			imageresolution($imSrc,IMG_MAX_DPI,IMG_MAX_DPI);
			break;
		case '.png':
			$imSrc = imagecreatefrompng($file);
			imageresolution($imSrc,IMG_MAX_DPI,IMG_MAX_DPI);
			break;
	}
	
	// resize image
	$imRes = imagecreatetruecolor($newsizes[0],$newsizes[1]);
	imagecopyresized($imRes,$imSrc,$newstart[0],$newstart[1],$newstart[2],$newstart[3],$newsizes[0],$newsizes[1],$sizes[0],$sizes[1]);
	
	// save image to file
	switch($type) { 
		case '.gif':
			imagegif($imRes,$file);
			$counter++;
			echo "$counter $file<br>\n";
			break;
		case '.jpg':
			imagejpeg($imRes,$file);
			$counter++;
			echo "$counter $file<br>\n";
			break;
		case '.png':
			imagepng($imRes,$file);
			$counter++;
			echo "$counter $file<br>\n";
			break;
	}
}


// ##################################################################
// # Main
// ##################################################################
$counter = 0;
$searchstring = '/(\.'.implode('$)|(\.',IMG_TYPE).'$)/i'; // Suchstring für die Bilddateien zusammenbauen
$files = dirIterate();
foreach ($files as $file) {
	checkFile($file);
}

echo "DONE by cronResizer<br>\n";
//EOF
