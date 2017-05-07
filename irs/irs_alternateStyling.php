<!--
IRS (Image Resize System) - Copyright 2017 Philipp Schwetschenau.
Licenced under the GPLv3.

This file is part of IRS (Image Resize System).

IRS is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

IRS is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with IRS.  If not, see <http://www.gnu.org/licenses/>.
-->
<!DOCTYPE html><html>

<?php
#error_reporting(E_ALL); ini_set('display_errors', TRUE);
?>

<head>
	<meta charset="utf-8">
	<title>Image Resize System</title>
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,700&subset=latin,latin-ext" rel="stylesheet" type="text/css">
	<link href="css/main.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php

	// CONFIGURATION:		
	// Source Directory:
	$dirX = "../images";
	// Target Directory:
	$dirY = "../tmb";

	// Width & Height ("null" for manual input via the user interface)
	$default_w = null;
	$default_h = null;

	// Prefix for target images
	$prefix = "tmb_";

	// Show transaction log on screen?
	$log = false;

	// Preserve aspect ratio?
	$ratio = true;
?>

<h2>Image Resize System</h2>

<?php
	// Init	
	$minValue = 1;
	$maxValue = 5000;
	
	// If height or width are empty -> other value is set to max (5000)	
	define("USE_CODE_VALUES", !is_null($default_w) || !is_null($default_h));
	
	$autoVal = array();
	$tbnwidth 	= !is_null($default_w)
		? $default_w
		: 	(
				isset($_POST['width'])  && intval($_POST['width'])  > 0
					? $_POST['width']
					: null
			);
							
	$tbnheight 	= !is_null($default_h)
		? $default_h
		:	(
				isset($_POST['height']) && intval($_POST['height']) > 0
					? $_POST['height']
					: null
			);
		
	if(is_null($tbnwidth)) {
		$tbnwidth = $maxValue;
		$autoVal[] = "width";
	}
	
	if(is_null($tbnheight)) {
		$tbnheight = $maxValue;
		$autoVal[] = "height";
	}
?>

<!-- Input form / user interface -->
<form action="irs_alternateStyling.php" method="post">
<table border="0">

<tr>
	<td>Source directory: </td>
	<td><?php echo $dirX; ?></td>
</tr>
<tr>
	<td>Target directory: </td>
	<td><?php echo $dirY; ?></td>
</tr>	
<tr>
	<td>Prefix: </td>
	<td><?php echo $prefix; ?></td>
</tr>

<tr id="colorized">
	<td>
		<input type ="text" name="width" placeholder="Width"
		value="<?php if(!in_array("width", $autoVal)) echo $tbnwidth; ?>"
		size="3"
		<?php if(USE_CODE_VALUES) echo "disabled"; ?> />
	</td>
	<td>px</td>
</tr>
<tr id="colorized">
	<td>
		<input type ="text" name="height" placeholder="Height"
		value="<?php if(!in_array("height", $autoVal)) echo $tbnheight; ?>" 
		size="3"
		<?php if(USE_CODE_VALUES) echo "disabled"; ?> />
	</td>
	<td>px</td>
</tr>

<!-- Show log checkbox in user interface -->
<tr>
	<td>Log</td>
	<td><input type = "checkbox" name="log"></td>
</tr>

</table>
<input type="submit" value="Go!" name = "ausfuehren" >
</form>

<?php

// Function: Bitmap processing
function imagecreatefrombmp($p_sFile) 
{ 
	$file    =    fopen($p_sFile,"rb"); 
	$read    =    fread($file,10); 
	while(!feof($file)&&($read<>"")) 
		$read    .=    fread($file,1024); 
	
	$temp    =    unpack("H*",$read); 
	$hex    =    $temp[1]; 
	$header    =    substr($hex,0,108); 
	
	if (substr($header,0,4)=="424d") 
	{ 
		$header_parts    =    str_split($header,2); 		
		$width            =    hexdec($header_parts[19].$header_parts[18]);
		$height            =    hexdec($header_parts[23].$header_parts[22]); 
		unset($header_parts); 
	} 
	
	$x                =    0; 
	$y                =    1; 
	$image            =    imagecreatetruecolor($width,$height); 
	$body            =    substr($hex,108); 
	$body_size        =    (strlen($body)/2); 
	$header_size    =    ($width*$height); 
	$usePadding        =    ($body_size>($header_size*3)+4); 
	
	for ($i=0;$i<$body_size;$i+=3) 
	{ 
		if ($x>=$width) 
		{ 
			if ($usePadding) 
				$i    +=    $width%4; 
			$x    =    0; 
			$y++; 
			if ($y>$height) 
				break; 
		} 
		
		$i_pos    =    $i*2; 
		$r        =    hexdec($body[$i_pos+4].$body[$i_pos+5]); 
		$g        =    hexdec($body[$i_pos+2].$body[$i_pos+3]); 
		$b        =    hexdec($body[$i_pos].$body[$i_pos+1]); 
		$color    =    imagecolorallocate($image,$r,$g,$b); 
		imagesetpixel($image,$x,$height-$y,$color); 
		$x++; 
	} 
	unset($body); 
	return $image; 
} 


// Function: Conversion & Storage
function convertImage($source, $dst, $width, $height, $quality, $log){
	// [0]=>width, [1]=>height, [2]=>type
	$imageSize = getimagesize($source);
	
	switch ($imageSize[2])
	{
    // 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP, 
	// 7 = TIFF (Intel), 8 = TIFF (Motorola)
    case 1: 
        $imageRessource = imagecreatefromgif($source);
        break;
    case 2:
        $imageRessource = imagecreatefromjpeg($source);
        break;
    case 3:
        $imageRessource = imagecreatefrompng($source);
        break;
	case 6:
		$imageRessource = imagecreatefrombmp($source);
		break;
    #case 7:
    #    $imageRessource = imagecreatefromtiff($source);
    #    break;
    #case 8:
    #    $imageRessource = imagecreatefromtiff($source);
    #    break;
    default:
        $type = 'unsupported';
		return false;
	}
	$imageFinal = imagecreatetruecolor($width, $height);
	$final = imagecopyresampled($imageFinal, $imageRessource, 0, 0, 0, 0, $width, $height, $imageSize[0], $imageSize[1]);
	imagejpeg($imageFinal, $dst, $quality);
}


// Start processing on user input
if(isset($_POST["ausfuehren"]))
{
	isset($_POST["log"]) ? $log=true : $log=false;
	
	// Init total of processed images
	$sumFiles = 0;
	$sumEditedFiles = 0;
	$sumNewFiles = 0;
	$sumResizedFiles = 0;
	$sumDeletedFiles= 0;
	
	// Both values set to maxValue = no values handed over
	if(count($autoVal) == 2) 
		echo "<font color=\"red\">No values entered</font><br>";
		
	elseif($tbnwidth < $minValue || $tbnwidth > $maxValue || $tbnheight < $minValue || $tbnheight > $maxValue) // check value range
		echo "<font color=\"red\">Value range between " . $minValue . " and " . $maxValue . "</font><br>";
		
	else {
		// Output only if one value is present
		if(count($autoVal) > 0)
			echo "<font color=\"green\">(Automatic " . (in_array("width", $autoVal) ? "Width" : "Height") . ")</font><br>";
		
		if($log){
			echo "<br>Processed images:<br>";
			echo "<table>";
		}
		
		// Valid target directory?
		if (!is_dir ( $dirY ))
		{	
			echo "<font color=\"red\">destination directory error</font>";
			return;
		}

		// Valid source directory?
		if (!is_dir ( $dirX ))
		{	
			echo "<font color=\"red\">source directory error</font>";
			return;
		}
				
		// Open source directory
		if ( $handle = opendir($dirX) )
		{
			// Read the images
			while (($file = readdir($handle)) !== false )
			{
				// Valid file & supported image type?
				if(
					is_file($dirX.'/'.$file) 
					&& @getimagesize($dirX.'/'.$file)
				)
				{
					// Source image size, type and aspect ratio
					$imageSize = getimagesize($dirX.'/'.$file);
					$imageRatio = $imageSize[1]/$imageSize[0];
				
					// Target directory and target image size
					$tbn = $dirY . "/" . $prefix . $file;
					if(is_file($tbn)){
						$tbnsize = getimagesize($tbn);
					}
					
					$tbnwidthcurrent = $tbnwidth;
					$tbnheightcurrent = $tbnheight;
					
					// Preserve aspect ratio
					$imageRatio = $imageSize[0]/$imageSize[1];
					$tbnRatio = $tbnwidth/$tbnheight;
					if($ratio){
						if ($tbnRatio > $imageRatio) {
						   $tbnwidthcurrent = round($tbnheight*$imageRatio, 0, PHP_ROUND_HALF_DOWN);
						   $tbnheightcurrent = $tbnheight;				   
						} else {
						   $tbnheightcurrent = round($tbnwidth/$imageRatio, 0, PHP_ROUND_HALF_DOWN);
						   $tbnwidthcurrent = $tbnwidth;
						}
					}

				
					// Target image exists?
					if(is_file($tbn)){
						if(is_numeric($tbnheight) && is_numeric($tbnwidth))
							$new = false;
						
						// Target image matches expected size?
						if(
							($tbnsize[0] == $tbnwidthcurrent) 
							&& ($tbnsize[1] == $tbnheightcurrent)
						) $update = false;
						
						// Create target image with expected size
						else {
							convertImage($dirX . "/" . $file, $tbn, 
								$tbnwidthcurrent, $tbnheightcurrent, 100, $log);
							$sumEditedFiles++;
							if(is_numeric($tbnheight) && is_numeric($tbnwidth)) 
							{
								$update = true;
								$sumResizedFiles++;
							}
						}
					}
					// Create target image
					else {
						convertImage($dirX . "/" . $file, $tbn, $tbnwidthcurrent, $tbnheightcurrent, 100, $log);	
						$sumEditedFiles++;
						if(is_numeric($tbnheight) && is_numeric($tbnwidth)) 
						{
							$new = true;
							$sumNewFiles++;
						}
						}							
					
					// On valid input -> Log output
					if($log && is_numeric($tbnheight) && is_numeric($tbnwidth)){
						echo "<tr>";
						echo "<td>" . "..." . substr($file, -30) . "</td>";
						echo "<td>" . "..." . substr(basename($tbn), -30) . "</td>";
						switch ($imageSize[2]){
							// 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP
							case 1: 
								$type = "GIF";
								break;
							case 2:
								$type = "JPG";
								break;
							case 3:
								$type = "PNG";
								break;
							case 6:
								$type = "BMP";
								break;
						}
						echo "<td>" . $tbnwidthcurrent . "x" . $tbnheightcurrent . " (" . $type . ") </td>";
						if($new) 
							echo "<td><b>new</b></td>";
						else echo "<td>found</td>";
						if(!$new) {
							if($update) 
								echo "<td><b>size updated</b></td>";
							else 
								echo "<td>size ok</td>";
						}
					}
				$sumFiles++;
				}
				echo "</tr>";
			}
			closedir($handle);
		}
		
		// Find & delete already existing target images that have no source image
		// Open the target directory
		if ( $handle = opendir($dirY) )
		{
			// Read the target images
			while (($file = readdir($handle)) !== false )
			{
				// Valid file & supported image type?
				if(	is_file($dirY . '/' . $file)
					&& @getimagesize($dirY . '/' . $file))
				{
					// Target image exists?
					if(!is_file($dirX . '/' . substr($file, strlen($prefix))))
					{
						// Delete target image
						unlink($dirY . '/' . $file);
						$sumFiles++;
						$sumEditedFiles++;
						$sumDeletedFiles++;
						
						// Log output
						if($log){
							echo "<tr>";
							echo "<td>" . substr($file, -30) . "</td>";
							echo "<td>" . substr(substr($file, strlen($prefix)), -30) . "</td>";
							echo "<td></td>";
							echo "<td><b><font color=\"red\">deleted</font></b></td>";
							echo "<td></td>";
						}
					}
				}
			}
			closedir($handle);
		}
	}
	echo "</table>";
	echo "(" . $sumFiles . " Images compared, " . $sumEditedFiles . " Images processed.)";
	if($log) 
		echo 	"<br>(" . $sumNewFiles . " new, " . 
				$sumResizedFiles . " size changed and " .
				$sumDeletedFiles . " deleted thumbnails.)"; 
}
?>

</body>
</html>