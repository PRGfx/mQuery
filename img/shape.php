<?php
/**
 * Draws a simple shape according to parameters given in the url.
 * @version 0.9
 * @author zocka
 *
 * @param t Type of the shape, <rectangle|circle|arc|rounded>
 * @param f If the shape should be filled, <true|false>
 * @param size Size of the shape in px, <width,height>
 * @param w Weight of borders for not filled shapes in px
 * @param color Colorcode for the shape in rgb(a) or rrggbb(aa) format
 *
 * @param angle Angle for an arch in degrees
 * @param rotation Angle to rotate an arch
 *
 * @param radius Border radius for type 'rounded'. Given like [top-left,top-right,bottom-left,bottom-right], optional parameters like border-radius in css
 * 
 */
isset($_GET["size"])?$size = explode(',', $_GET["size"]):$size = array(250, 250);
list($width, $height) = $size;
isset($_GET["t"])?$type = $_GET["t"]:$type = "rounded";
if(isset($_GET["f"])){
	$filled = (strtolower($_GET["f"]) == "true");
}else
	$filled = true;
isset($_GET["w"])?$weight = $_GET["w"]:$weight = 10;
isset($_GET["color"])?$colorcode = $_GET["color"]:$colorcode = "f002";
isset($_GET["angle"])?$angle = $_GET["angle"]:$angle = 20;
isset($_GET["rotation"])?$rotation = $_GET["rotation"]:$rotation = 0;
isset($_GET["radius"])?$corners = $_GET["radius"]:$corners = "[30]";

// split colorcode
$color = array();
$colorlen = strlen($colorcode);
switch ($colorlen) {
	case 3:
	case 4:
		$color["r"] = hexdec($colorcode[0] . $colorcode[0]);
		$color["g"] = hexdec($colorcode[1] . $colorcode[1]);
		$color["b"] = hexdec($colorcode[2] . $colorcode[2]);
		$colorlen==3?$color["a"] = 255:$color["a"] = hexdec($colorcode[3] . $colorcode[3]);
		break;
	case 6:
	case 8:
		$color["r"] = hexdec($colorcode[0] . $colorcode[1]);
		$color["g"] = hexdec($colorcode[2] . $colorcode[3]);
		$color["b"] = hexdec($colorcode[4] . $colorcode[5]);
		$colorlen==6?$color["a"] = 255:$color["a"] = hexdec($colorcode[6] . $colorcode[7]);
		break;
	default:
		throw new Exception("Expecting colorcode to be 3(+1) or 6(+2) characters", 1);
		break;
}

// split corners
$c = json_decode($corners);
$corners = array();
switch (count($c)) {
	case 1:
		$corners[0] = $corners[1] = $corners[2] = $corners[3] = $c[0];
		break;
	case 2:
		$corners[0] = $corners[3] = $c[0];
		$corners[1] = $corners[2] = $c[1];
		break;
	case 3:
		$corners[0] = $c[0];
		$corners[1] = $corners[2] = $c[1];
		$corners[3] = $c[2];
		break;
	case 4:
		$corners[0] = $c[0];
		$corners[1] = $c[1];
		$corners[2] = $c[2];
		$corners[3] = $c[3];
		break;
}

// create image
$im = imagecreate($width, $height);
imagealphablending($im, false);
imagesavealpha($im, true);
$transparent = imagecolortransparent($im, imagecolorallocate($im, 0, 0, 0));
imagefill($im, 0, 0, $transparent);
list($r, $g, $b, $a) = array_values($color);
$color = imagecolorallocatealpha($im, $r, $g, $b, $a);
switch ($type) {
	case 'circle':
		imagefilledellipse($im, $width/2, $height/2, $width-1, $height-1, $color);
		if(!$filled){
			imagefilledellipse($im, $width/2, $height/2, $width-1-$weight, $height-1-$weight, $transparent);
		}
		break;
	
	case 'rectangle':
		imagefilledrectangle($im, 0, 0, $width, $height, $color);
		if(!$filled){
			imagefilledrectangle($im, $weight, $weight, $width-$weight, $height-$weight, $transparent);
		}
		break;
	
	case 'arc':
		imagefilledarc($im, $width/2, $height/2, $width-1, $height-1, -90+$rotation, $angle-90+$rotation, $color, IMG_ARC_PIE);
		if(!$filled){
			imagefilledellipse($im, $width/2, $height/2, $width-1-$weight, $height-1-$weight, $transparent);
		}
		break;
	
	case 'rounded':
		$c = array("tl", "tr", "bl", "br");
		foreach ($c as $k=>$corner) {
			if($corners[$k] > 0)
			{
				$$corner = imagecreate($corners[$k], $corners[$k]);
				imagealphablending($$corner, false);
				imagesavealpha($$corner, true);
				$trans = imagecolorallocatealpha($$corner, 0, 0, 0, 0);
				imagefill($$corner, 0, 0, $trans);
				imagecolortransparent($$corner, $trans);
				$col = imagecolorallocatealpha($$corner, $r, $g, $b, $a);
				imagefilledellipse($$corner, $corners[$k] * (($k+1)%2), $corners[$k] * abs(floor($k/2)-1), $corners[$k]*2, $corners[$k]*2, $col);
				imagecopy($im, $$corner, $width * (($k)%2) - ($corners[$k] * (($k)%2)), $height * floor($k/2) - $corners[$k] * floor($k/2), 0, 0, $corners[$k], $corners[$k]);
			}
		}
		imagefilltoborder($im, $width/2, $height/2, $col, $color);

		break;
}
if(isset($_GET["d"]))
	die;
header("Content-Type: image/png");
imagepng($im);
imagedestroy($im);
?>
