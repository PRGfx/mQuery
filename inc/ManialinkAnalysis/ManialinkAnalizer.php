<?php
namespace ManialinkAnalysis;

require_once('inc/ManialinkAnalysis/ManialinkElement.php');
require_once('inc/ManiaQuery/ManiascriptHandler.php');

class ManialinkAnalizer {

	private $mlData;

	private $script = "";

	private $scriptFunctions = array();

	private $stylesheets = array();

	private $scriptFiles = array();

	private $Maniascript;

	private $ManiaQuery;

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
		$this->ManiaQuery = new \ManiaQuery\ManiaQuery($this);
		// ManiaScript
		preg_match_all('/<script([^>]*?)>(.*?)<\/script>/is', $this->mlData, $scriptBlocks);
		// var_dump($scriptBlocks);
		foreach($scriptBlocks[2] as $script)
		{
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
		return $this;
	}

	public function scriptFiles() {
		return $this->scriptFiles;
	}

	public function addStyleSheet($file) {
		$this->stylesheets[] = $file;
	}

	public function getElementById($id) {
		$regex = '/<([a-zA-Z0-9]+?)([^<>]*?)id=(\\"|\')('.$id.')\3(.*?)(\/>|([^(<\/)]*?)<\/\1>)/is';
		preg_match($regex, $this->mlData, $result);
		if(array_key_exists(0, $result))
			return new ManialinkElement($result[0]);
		return null;
	}

	public function getElementsByClass($class) {
		$regex = '/<([a-zA-Z0-9]+?)([^<>]*?)class=(\\"|\')\b'.$class.'\b\3([^>]*?)(\/>|>(.*?)<\/\1>)/is';
		preg_match_all($regex, $this->mlData, $results, PREG_SET_ORDER);
		// var_dump($results);
		$return = array();
		foreach($results as $result)
		{
			if($result[1] == "frame") {
				$x = explode('>', $result[0]);
				$result[0] = $x[0].'>';
			}
			$return[] = new ManialinkElement($result[0]);
		}
		return $return;
	}

	public function getElementsByTag($tag) {
		$regex = '/<('.$tag.') ([^<>]*?)(\/>|([^(<\/)]*?)<\/\1>)/Uis';
		preg_match_all($regex, $this->mlData, $results, PREG_SET_ORDER);
		$return = array();
		foreach($results as $result)
		{
			$return[] = new ManialinkElement($result[0]);
		}
		return $return;
	}

	public function scriptFunctions() {
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

	public function scriptHandler() {
		return $this->Maniascript;
	}

	public function append($string, $id="") {
		if(empty($id)){
			$this->mlData = preg_replace('/<\/manialink>/i', $string . "\n</manialink>", $this->mlData);
		}else{
			$old = $this->getElementById($id)->toString();
			$this->mlData = str_replace($old, $old . "\n" . $string, $this->mlData);
		}
	}

	public function updateElement($old, $new = null) {
		if($old == null) {
			throw new \Exception("You have to insert the old element!", 1);
			return $this;
		}
		if($old instanceof ManialinkElement) {
			$old = $old->replacer();
		}
		if(is_array($old)){
			$old = array_values($old);
			list($old, $new) = $old;
		}
		if(!$this->isTag($old))
			$old = $this->getElementById($old)->toString;
		$this->mlData = str_replace($old, $new, $this->mlData);
		return $this;
	}

	public function update($old, $new) {
		if(str_replace($old, $new, $this->mlData))
			return true;
		return false;
	}

	public function removeElement($string) {
		if($this->isTag($string))
			$string = $this->getElementById($string)->toString();
		$this->updateElement($string, "");
	}

	private function isTag($string) {
		if(preg_match('/^<(.*)\/?>$/Us', $string))
			return true;
		return false;
	}

	public function output($return = false) {
		$result = str_replace('</manialink>', "<script>\n<!--\n" . $this->Maniascript->build() . "\n--></script>\n</manialink>", $this->mlData);
		if($return)
			return $result;
		echo $result;
		return $this;
	}

	public function applyStyles($styleRessource = false) {
		if($styleRessource && file_exists($styleRessource)) {
			$styleRessource = readCss($styleRessource);
		}
		if(!$styleRessource){
			$styleRessource = array();
			foreach ($this->stylesheets as $value) {
				$styleRessource+= readCss($value);
			}
		}
		foreach ($styleRessource as $ident => $values) {
			$ident = explode(':', $ident, 2);
			array_key_exists(1, $ident)?$mod = $ident[1]:$mod=null;
			$ident = $ident[0];
			if(substr($ident, 0, 1) == "#"){
				$element = $this->getElementById(substr($ident, 1));
				if($element != null && $this->mlssMod($element, $mod)) {
					foreach($values as $k=>$v){
						$element->set($k, $v);
					}
					$this->updateElement($element->replacer());
				}
			} else if(substr($ident, 0, 1) == "."){
				$elements = $this->getElementsByClass(substr($ident, 1));
				foreach($elements as $element) {
					if($element != null && $this->mlssMod($element, $mod)) {
						foreach($values as $k=>$v){
							$element->set($k, $v);
						}
						$this->updateElement($element->replacer());
					}
				}
			} else {
				$elements = $this->getElementsByTag($ident);
				foreach($elements as $element) {
					if($element != null && $this->mlssMod($element, $mod)) {
						foreach($values as $k=>$v){
							$element->set($k, $v);
						}
						$this->updateElement($element->replacer());
					}
				}
			}
		}
		return $this;
	}

	private function mlssMod($element, $mod) {
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

	public static function strip_tags_tm($str, $type) {
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