<?php
namespace ManialinkAnalysis;

require_once('inc/ManialinkAnalysis/ManialinkElement.php');
require_once('inc/ManiaQuery/ManiascriptHandler.php');

class ManialinkAnalizer
{

	/**
	 * The manialink code passed to the class
	 */
	private $mlData;

	private $script = "";

	private $mQueryScript;

	private $scriptFunctions = array();

	private $stylesheets = array();

	private $scriptFiles = array();

	private $Maniascript;

	private $ManiaQuery;

	public $generateShapes = true;

	public function __construct($mlData) {
		$this->Maniascript = new \ManiaQuery\ManiascriptHandler($this);
		$this->mlData = $mlData;
		// style tags
		preg_match_all('/<style(.*?)href=(\\"|\\\')(.*?)\\2(.*?)(\\/>|<\\/style>)/', $this->mlData, $stylesheets, PREG_SET_ORDER);
		foreach ($stylesheets as $key => $value) {
			$this->stylesheets[] = $value[3];
			$this->mlData = str_replace($value[0], "", $this->mlData);
		}
		// script sources
		preg_match_all('/<script(.*?)src=(\\"|\\\')(.*?)\\2(.*?)(\\/>|<\\/script>)/', $this->mlData, $scriptFiles, PREG_SET_ORDER);
		foreach ($scriptFiles as $key => $value) {
			$this->scriptFiles[] = $value[3];
			$this->mlData = str_replace($value[0], "", $this->mlData);
		}
		// ManiaQueryScript
		preg_match_all("/<mquery>(.*)<\\/mquery>/Uis", $this->mlData, $mqscripts);
		$this->mQueryScript = implode("\n", $mqscripts[1]);
		$this->mlData = preg_replace("/<mquery>(.*)<\/mquery>/Uis", "", $this->mlData);

		$this->ManiaQuery = new \ManiaQuery\ManiaQuery($this, $this->mQueryScript);

		// ManiaScript
		preg_match_all('/<script([^>]*?)>(.*?)<\/script>/is', $this->mlData, $scriptBlocks);

		// var_dump($scriptBlocks);
		foreach ($scriptBlocks[2] as $script) {
			$this->script.= $script;
		}
		$this->script = preg_replace('/^(<!--(\/\/)?)|((\/\/)?-->)$/m', "", $this->script);
		$this->script = trim($this->script);
		// echo $this->script;
		$overlay = new ManialinkElement('<quad bgcolor="0003" class="mqMlOverlay" sizen="320 180" posn="-160 90 55" hidden="1" id="mqMlOverlay" scriptevents="1" />');
		$this->append($overlay->toString());
		$this->Maniascript->declareGlobalVariable("CMlQuad", "mqMlOverlay");
		$this->Maniascript->declareGlobalVariable("Text", "mqMlOverlayUse");
		$this->Maniascript->declareMainVariable("CMlQuad", "mqMlOverlay", true, "mqMlOverlay");
		$fadeOverlay = new ManialinkElement('<quad image="" sizen="320 180" posn="-160 90 55" hidden="1" id="fadeOverlay"  />');
		$this->append($fadeOverlay->toString());
		return $this;
	}

	/**
	 * @return array All links to files given in <style href="(.*)" />
	 *  somewhere in the manialink
	 */
	public function scriptFiles()
	{
		return $this->scriptFiles;
	}

	/**
	 * Adds a stylesheet file as ressource.
	 * @param string $file Path for a stylesheet file.
	 */
	public function addStyleSheet($file)
	{
		$this->stylesheets[] = $file;
	}

	/**
	 * Returns the first ManialinkElement with the id-attribute matching $id.
	 * @param string $id Id-attribute to search for.
	 * @return ManialinkElement matching $id.
	 */
	public function getElementById($id)
	{
		$regex = '/<([a-zA-Z0-9]+?)([^<>]*?)id=(\\"|\')('.$id.')\3(.*?)(\/>|([^(<\/)]*?)<\/\1>)/is';
		preg_match($regex, $this->mlData, $result);
		if(array_key_exists(0, $result))
			return new ManialinkElement($result[0]);
		return null;
	}

	/**
	 * Returns the ManialinkElements with the class-attribute matching $class.
	 * Also matches if multiple classes are given in an element.
	 * @param string $class Class-attribute to search for.
	 * @return array ManialinkElements matching $class.
	 */
	public function getElementsByClass($class)
	{
		$regex = '/<([a-zA-Z0-9]+?)([^<>]*?)class=(\\"|\')([a-zA-Z0-9_ ]*)\b'.$class.'\b([a-zA-Z0-9_ ]*)\3([^>]*?)(\/>|>(.*?)<\/\1>)/is';
		preg_match_all($regex, $this->mlData, $results, PREG_SET_ORDER);
		// var_dump($results);
		$return = array();
		foreach ($results as $result) {
			if ($result[1] == "frame") {
				$x = explode('>', $result[0]);
				$result[0] = $x[0].'>';
			}
			$return[] = new ManialinkElement($result[0]);
		}
		return $return;
	}

	/**
	 * Returns all ManialinkElements of type $tag.
	 * @param string $tag Element type searched for.
	 * @return array ManialinkElements of type $tag.
	 */
	public function getElementsByTag($tag)
	{
		$regex = '/<('.$tag.') ([^<>]*?)(\/>|([^(<\/)]*?)<\/\1>)/Uis';
		preg_match_all($regex, $this->mlData, $results, PREG_SET_ORDER);
		$return = array();
		foreach ($results as $result) {
			$return[] = new ManialinkElement($result[0]);
		}
		return $return;
	}

	/**
	 * development stopped
	 */
	public function scriptFunctions()
	{
		$regex = '/(((Text|Void|Real|Integer|(CMl|CGameManialink)[a-zA-Z0-9]*) ([_a-zA-Z][a-zA-Z0-9_]*))|main) ?\((.*?)\) ?\{(.*?)\}/s';
		preg_match_all($regex, $this->script, $scriptFunctions, PREG_SET_ORDER);
		print_r($scriptFunctions);
		foreach ($scriptFunctions as $key => $value) {
			if(empty($value[3]) && $value[1] == "main")
				$value[3] = "main";
			if(empty($value[5]) && $value[1] == "main")
				$value[5] = "main";
			$this->scriptFunctions[] = array(
					"type" => $value[3],
					"name" => $value[5],
					"parameters" => $value[6],
					"content" => substr($value[7], 1, -1)
			);
		}
		return $this->scriptFunctions;
	}

	/**
	 * @return The Maniascript handler for further use.
	 */
	public function scriptHandler() {
		return $this->Maniascript;
	}

	/**
	 * Adds code to the manialink output at the end of the manialink OR after
	 *  a manialinkelement if it's id is given.
	 * @param string $string Code to be added.
	 * @param string $id If given, $string will be added after the opening tag
	 *  (frames) of a ManialinkElement with id $id.
	 */
	public function append($string, $id="") {
		if(empty($id)){
			$this->mlData = preg_replace('/<\/manialink>/i', $string . "\n</manialink>", $this->mlData);
		}else{
			$old = $this->getElementById($id)->toString();
			$this->mlData = str_replace($old, $old . "\n" . $string, $this->mlData);
		}
	}

	/**
	 * Replaces an Element with it's new version.
	 * Give a ManialinkElement as only parameter and it will do everything alone,
	 * give two xml strings as parameters to replace the first by the second.
	 */
	public function updateElement($old, $new = null)
	{
		if ($old == null) {
			throw new \Exception("You have to insert the old element!", 1);
			return $this;
		}
		if ($old instanceof ManialinkElement) {
			$old = $old->replacer();
		}
		if (is_array($old)) {
			$old = array_values($old);
			list($old, $new) = $old;
		}
		if(!$this->isTag($old))
			$old = $this->getElementById($old)->toString;
		$this->mlData = str_replace($old, $new, $this->mlData);
		return $this;
	}

	/**
	 * Replaces $old by $new in the manialink.
	 */
	public function update($old, $new)
	{
		if(str_replace($old, $new, $this->mlData))
			return true;
		return false;
	}

	/**
	 * Removes a string from the manilink.
	 * @param string $string Can either be a element id or a xml tag.
	 */
	public function removeElement($string)
	{
		if(!$this->isTag($string))
			$string = $this->getElementById($string)->toString();
		$this->updateElement($string, "");
	}

	/**
	 * @return boolean True if $string starts and ends like a xml tag, false otherwise.
	 */
	private function isTag($string)
	{
		if(preg_match('/^<(.*)\/?>$/Us', $string))
			return true;
		return false;
	}

	private function replaceShapes($manialink)
	{
		$regex = '/<shape(.*?)\/>/is';
		if ($this->generateShapes && function_exists("gd_info")) {
			preg_match_all($regex, $manialink, $shapes, PREG_SET_ORDER);
			foreach ($shapes as $shape) {
				preg_match_all('/\b([a-z_]*)\b=(\"|\')(.*?)\2/is', $shape[1], $attributes);
				$query = "";
				$element = new ManialinkElement("<quad />");
				foreach ($attributes[1] as $k=>$attribute) {
					$attribute = strtolower($attribute);
					$value = trim($attributes[3][$k]);
					switch ($attribute) {
						case "sizen":
							$element->set("sizen", $value);
							$sizen = explode(' ', $value);
							list($w, $h) = $sizen;
							$query.= "&amp;size=" . $w * 10 . "," . $h * 10;
							break;
						case "bgcolor":
							$query.= "&amp;color=" . $value;
							break;
						case "type":
						case "fill":
						case "weight":
							$query.= "&amp;" . $attribute[0] . "=" . strtolower($value);
							break;
						case "rotation":
						case "angle":
							$query.= "&amp;" . $attribute . "=" . strtolower($value);
							break;
						case "posn":
						case "class":
						case "id":
						case "manialink":
						case "url":
						case "action":
						case "scriptevents":
							$element->set($attribute, $value);
							break;
					}
				}
				$query = "?" . substr($query, 5);
				$shapeUrl = "http://" . dirname($_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"])
				 . "/img/shape.php" . $query . "&amp;ed.png";
				$element->set("image", $shapeUrl);
				$manialink = str_replace($shape[0], $element->toString(), $manialink);
			}
		}
		$manialink = preg_replace($regex, "", $manialink);
		return $manialink;
	}

	/**
	 * Outputs the final rendered manialink.
	 * @param boolean $return Wether or not to return or just output the rendered manialink.
	 */
	public function output($return = false)
	{
		$result = $this->mlData;	
		$result = preg_replace('/<manialink.*?>(.*?\/timeout>)?/', "$0".$this->Maniascript->buildConstraints(), $result);
		$result = str_replace('</manialink>', "<script><!--\n" . $this->Maniascript->build() . "\n--></script>\n</manialink>", $result);
		$result = $this->replaceShapes($result);
		if($return)
			return $result;
		echo $result;
		return $this;
	}

	public function applyStyles($styleRessource = false)
	{
		if ($styleRessource && file_exists($styleRessource)) {
			$styleRessource = readCss($styleRessource);
		}
		if (!$styleRessource) {
			$styleRessource = array();
			foreach ($this->stylesheets as $value) {
				$styleRessource+= readCss($value);
			}
		}
		// var_dump($styleRessource);
		foreach ($styleRessource as $ident => $values) {
			$ident = explode(':', $ident, 2);
			array_key_exists(1, $ident)?$mod = $ident[1]:$mod=null;
			$ident = $ident[0];
			if (substr($ident, 0, 1) == "#") {
				$element = $this->getElementById(substr($ident, 1));
				if ($element != null && $this->mlssMod($element, $mod)) {
					foreach ($values as $k=>$v) {
						$element->set($k, $v);
					}
					$this->updateElement($element->replacer());
				}
			} else if (substr($ident, 0, 1) == ".") {
				$elements = $this->getElementsByClass(substr($ident, 1));
				foreach ($elements as $element) {
					if ($element != null && $this->mlssMod($element, $mod)) {
						foreach ($values as $k=>$v) {
							$element->set($k, $v);
						}
						$this->updateElement($element->replacer());
					}
				}
			} else {
				$elements = $this->getElementsByTag($ident);
				foreach ($elements as $element) {
					if ($element != null && $this->mlssMod($element, $mod)) {
						foreach ($values as $k=>$v) {
							$element->set($k, $v);
						}
						$this->updateElement($element->replacer());
					}
				}
			}
		}
		return $this;
	}

	private function mlssMod($element, $mod)
	{
		switch ($mod) {
			case 'manialink':
				if($element->get("manialink") != null)
					return true;
				break;
			case 'url':
				if($element->get("url") != null)
					return true;
				break;
			case 'action0':
				if($element->get("action") === "0")
					return true;
				break;
			case 'action1':
				if($element->get("action") === "1")
					return true;
				break;
			case null:
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Removes ManiaPlanet formating tags from a string.
	 * @param string $str String to remove formats from.
	 * @param string $type give something out of "links, colors, formats" in a string to strip the respective tags
	 */
	public static function strip_tags_tm($str, $type)
	{
		if(preg_match('/links?/i', $type) || preg_match('/all/i', $type))
			$str = preg_replace('/\$(l|h|p)(\[(.*?)\])?(.*?)(\$\1)?/is', '\4', $str);
		if(preg_match('/colors?/i', $type) || preg_match('/all/i', $type))
			$str = preg_replace('/\$([0-9a-fA-F]{3}|g)/', '', $str);
		if(preg_match('/formats?/i', $type) || preg_match('/all/i', $type))
			$str = preg_replace('/\$([own>gz<si])/i', '', $str);
		return $str;
	}
}
?>