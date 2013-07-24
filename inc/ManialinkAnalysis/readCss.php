<?php
/**
 * Reads out a css formatted file, given as string or file path. It will return
 * every style attribute and the associated value as an array for every ident.
 * It will not check for valid css attributes or values!
 * Every ident will return it´s associated configurations. Multiple idents
 * per definition will create multiple array keys!
 *
 * @author Pepe R
 * @version 0.7
 * @param $file
 *     Can either be a valid path to a .css or .php file containing
 *     css-formatted text or a string with css-formatted style information
 * @return Will return an array such as [ident] = array("modifier"=>"value");
 * @throws Exception when $file was interpreted as path and could not be found 
 *     or could not be accessed.
 */
function readCss($file)
{
	if (substr($file, -4) == ".php" 
			|| substr($file, -4) == ".css" || substr($file, -5) == ".mlss") {
		if(!file_exists($file))
			throw new Exception("The file '$file' could not be found!", 1);
		if(!$file = file_get_contents($file))
			throw new Exception("The file '$file' could not be accessed!", 1);
	}
	$file = preg_replace('/\/\*(.*?)\*\//s', '', $file);
	preg_match_all('/(.*?) ?\{(.*?)\}/s', $file, $styles, PREG_SET_ORDER);
	$result = array();
	foreach ($styles as $style) {
		$classes = explode(',', $style[1]);
		foreach ($classes as $class) {
			$class = trim($class);
			$settings = explode(';', trim($style[2]));
			$values = array();
			foreach ($settings as $setting) {
				if(empty($setting))
					continue;
				$setting = trim($setting);
				$s = explode(':', $setting, 2);
				$values[$s[0]] = trim($s[1]);
			}
			$result[$class] = $values;
		}
	}
	$tags = array();
	$classes = array();
	$ids = array();
	foreach ($result as $key => $value) {
		if(substr($key, 0, 1) == "#")
			$ids[$key] = $value;
		else if(substr($key, 0, 1) == ".")
			$classes[$key] = $value;
		else
			$tags[$key] = $value;
	}
	ksort($tags);
	ksort($classes);
	ksort($ids);
	$result = $tags + $classes + $ids;
	return $result;
}
?>