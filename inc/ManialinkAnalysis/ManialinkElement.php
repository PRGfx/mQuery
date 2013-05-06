<?php
namespace ManialinkAnalysis;

class ManialinkElement {

	/**
	 * Contains the type of the ManialinkElement (such as Label, Quad etc.).
	 */
	public $type;

	public $maniascriptId;

	private $attributes;

	public $initial;

	private $toAppend;

	public function __construct($string) {
		$this->initial = $string;
		if(preg_match('/<([a-zA-Z0-9]*?)(.*)(\/>|>(.*)<\/\1>)/Uis', $string, $element)) {
			$this->type = $element[1];
			$this->attributes = $element[2];
		}
		if(empty($this->type))
			$this->type = "frame";
		// atributes
		$finalAttributes = array();
		preg_match_all('/([a-zA-Z0-9]*)=(\"|\')(.*)\2/Uis', $this->attributes, $attributes);
		foreach ($attributes[1] as $k => $v) {
			$attr = strtolower($v);
			$value = $attributes[3][$k];
			$this->$attr = $value;
			$finalAttributes[$attr] = $value;
		}
		if($this->type == "label" && !isset($this->text) && isset($element[4])){
			$this->text = $element[4];
			$finalAttributes["text"] = $element[4];
		}
		$this->attributes = $finalAttributes;
		if($this->get("sizen") === null)
			$this->set("sizen", "80 80");
		if($this->get("posn") === null)
			$this->set("posn", "0 0 0");
		if($this->get("halign") === null)
			$this->set("halign", "left");
		if($this->get("valign") === null)
			$this->set("valign", "top");
		return $this;
	}

	/**
	 * Returns the value of a certain attribute of the ManialinkElement.
	 *
	 * @param $attribute Attribute you want to have the value of.
	 * @return The value that is specified for this attribute - if set.
	 */
	public function get($attribute) {
		$attribute = strtolower($attribute);
		if($attribute == "color") $attribute = "textcolor";
		if($attribute == "background-color") {
			if($this->type == "quad")
				$attribute = "bgcolor";
			if($this->type == "label")
				$attribute = "focusareacolor1";
		}
		if(array_key_exists($attribute, $this->attributes))
			return $this->attributes[$attribute];
		switch($attribute){
			case "width":
				$w = explode(' ', $this->get("sizen"));
				return $w[0];
				break;
			case "height":
				$h = explode(' ', $this->get("sizen"));
				return $h[1];
				break;
			case "top":
			case "bottom":
			case "y":
				$p = explode(' ', $this->get("posn"));
				return $p[1];
				break;
			case "left":
			case "right":
			case "x":
				$p = explode(' ', $this->get("posn"));
				return $p[0];
				break;
			case "z":
			case "z-index":
				$p = explode(' ', $this->get("posn"));
				return $p[2];
				break;
		}
		return null;
	}

	/**
	 * Specifies or deletes an attribute of the ManialinkElement.
	 *
	 * @param $attribute Attribute to set a value for
	 * @param $value New value for the attribute. Leave empty to remove the attribute from the element.
	 * @return current ManialinkElement, allowing concatenations of set() commands.
	 */
	public function set($attribute, $value) {
		$atribute = strtolower($attribute);
		if($attribute == "sizen") {
			list($width, $height) = explode(' ', trim($value) . " 80");
			$this->set("width", $width);
			$this->set("height", $height);
			return $this;
		}
		if($attribute == "posn") {
			list($x, $y, $z) = explode(' ', trim($value) . " 0 0");
			$this->set("x", $x);
			$this->set("y", $y);
			$this->set("z", $z);
			return $this;
		}
		// borders
		if(preg_match('/border/', $attribute)) {
			if($attribute == "border") {
				$this->set("borderRight", $value);
				$this->set("borderLeft", $value);
				$this->set("borderTop", $value);
				$this->set("borderBottom", $value);
			}
			if(preg_match('/^border-/i', $attribute)) {
				$t = explode('-', $attribute);
				$t[1] = ucfirst($t[1]);
				$attribute = $t[0] . $t[1];
			}
			if($value == "none") {
				unset($this->$attribute);
				return $this;
			}
			$values = array();
			if(preg_match('/inset/i', $value))
				$values["inset"] = true;
			preg_match('/#?([0-9a-fA-F]{3,4})/', $value, $color);
			empty($color)?$values["color"] = "000F" : $values["color"] = strtoupper($color[1]);
			preg_match('/([0-9]*)px/i', $value, $strength);
			if(!empty($strength))
				$values["strength"] = $strength[1] / 10;
			$this->$attribute = $values;
			return $this;
		}
		$r = array_values($this->convertValues($attribute, $value));
		list($attribute, $value) = $r;
		switch($attribute){
			case "width":
				$w = explode(' ', $this->get("sizen"));
				if(preg_match('/r$/', $value))
					$w[0]+= preg_replace('/r$/', '', $value);
				else
					$w[0] = $value;
				$attribute = "sizen"; $value = implode(' ', $w);
				break;
			case "height":
				$h = explode(' ', $this->get("sizen"));
				if(preg_match('/r$/', $value))
					$h[1]+= preg_replace('/r$/', '', $value);
				else
					$h[1] = $value;
				$attribute = "sizen"; $value = implode(' ', $h);
				break;
			case "top":
			case "bottom":
			case "y":
				$p = explode(' ', $this->get("posn"));
				if(preg_match('/r$/', $value))
					$p[1]+= preg_replace('/r$/', '', $value);
				else
					$p[1] = $value;
				$attribute = "posn"; $value = implode(' ', $p);
				break;
			case "left":
			case "right":
			case "x":
				$p = explode(' ', $this->get("posn"));
				if(preg_match('/r$/', $value))
					$p[0]+= preg_replace('/r$/', '', $value);
				else
					$p[0] = $value;
				$attribute = "posn"; $value = implode(' ', $p);
				break;
			case "z":
			case "z-index":
				$p = explode(' ', $this->get("posn"));
				if(preg_match('/r$/', $value))
					$p[2]+= preg_replace('/r$/', '', $value);
				else
					$p[2] = $value;
				$attribute = "posn"; $value = implode(' ', $p);
				break;
			case "class":
				if($this->get("class") != null) {
					$c = explode(' ', $this->get("class"));
					$c[] = $value;
					$value = implode(' ', $c);
				}
		}
		if(empty($value) && $value!=0)
		{
			unset($this->attribute);
			unset($this->attributes[$attribute]);
		}else{
			if(substr($this->get($attribute), -10, 10) != "!important")
			{
				$this->$attribute = $value;
				$this->attributes[$attribute] = $value;
			}
		}
		return $this;
	}

	public function hide() {
		$this->set("hidden", "1");
		return $this;
	}

	public function show() {
		if($this->get("hidden") == "1")
			$this->set("hidden", "");
		return $this;
	}

	/**
	 * @return The Manialink element as xml dom element with all defined attributes.
	 */
	public function toString() {
		if(isset($this->display) && $this->display == "none")
			return "";
		if(isset($this->css)) {
			if(array_key_exists('css', $this->attributes))
				unset($this->attributes["css"]);
			preg_match_all('/([a-z][a-z0-9-_]*)\s?:(\s*)?(.*);/Uis', $this->css, $styleattritubes, PREG_SET_ORDER);
			foreach ($styleattritubes as $sa) {
				$this->set($sa[1], $sa[3]);
			}
		}
		$output="<" . $this->type ." ";
		ksort($this->attributes);
		foreach ($this->attributes as $key => $value) {
			if(!empty($value) && !preg_match('/border/', $key))
			{
				$value = preg_replace('/ \!important$/', "", $value);
				$output.=$key . "=\"" . $value . "\" ";
			}
		}
		if($this->type!="frame"){
			$output.="/>";
		}else
			$output.=">";
		// borders
		$output.= $this->addBorders();
		// append
		if(!empty($this->toAppend))
			$output.=$this->toAppend;
		return $output;
	}

	/**
	 * @return Returns the original xml element, that was inserted.
	 */
	public function getInitial() {
		return $this->initial;
	}

	/**
	 * @return An array containing the original input and the modified output as keys "in" and "out".
	 *    It can be given as parameter for ManialinkAnalizer::updateElement().
	 */
	public function replacer() {
		return array("in"=>$this->initial, "out"=>$this->toString());
	}

	private function addBorders() {
		$output = "";
		list($width, $height) = explode(' ', $this->get("sizen"));
		list($x, $y, $z) = explode(' ', $this->get("posn"));
		$z++;
		$corners = $this->getCorners();
		if(isset($this->borderTop)) {
			$bs = $this->borderTop;
			$bT = new ManialinkElement('<quad />');
				$bT->set("sizen", $width . " " . $bs["strength"]);
				$bT->set("posn", $corners[0][0] . " " . ($corners[0][1] + $bs["strength"]) . " " . $z);
				$bT->set("bgcolor", $bs["color"]);
			if(array_key_exists('inset', $bs))
				$bT->set("y", $corners[0][1]);
			if(isset($this->borderRight))
				$bT->set("width", $width + $this->borderRight["strength"]);
			if(isset($this->borderLeft)) {
				$bT->set("width", $bT->get("width") + $this->borderLeft["strength"]);
				$bT->set("x", $bT->get("x") - $this->borderLeft["strength"]);
			}
			$output.= $bT->toString();
		}
		if(isset($this->borderRight)) {
			$bs = $this->borderRight;
			$bT = new ManialinkElement('<quad />');
				$bT->set("sizen", $bs["strength"] . " " . $height);
				$bT->set("posn", $corners[1][0] . " " . $corners[1][1] . " " . $z);
				$bT->set("bgcolor", $bs["color"]);
			if(array_key_exists('inset', $bs))
				$bT->set("x", $corners[1][0] - $bs["strength"]);
			$output.= $bT->toString();
		}
		if(isset($this->borderBottom)) {
			$bs = $this->borderBottom;
			$bT = new ManialinkElement('<quad />');
				$bT->set("sizen", $width . " " . $bs["strength"]);
				$bT->set("posn", $corners[0][0] . " " . $corners[2][1] . " " . $z);
				$bT->set("bgcolor", $bs["color"]);
			if(array_key_exists('inset', $bs))
				$bT->set("y", $corners[2][1] + $bs["strength"]);
			if(isset($this->borderRight))
				$bT->set("width", $width + $this->borderRight["strength"]);
			if(isset($this->borderLeft)) {
				$bT->set("width", $bT->get("width") + $this->borderLeft["strength"]);
				$bT->set("x", $bT->get("x") - $this->borderLeft["strength"]);
			}
			$output.= $bT->toString();
		}
		if(isset($this->borderLeft)) {
			$bs = $this->borderLeft;
			$bT = new ManialinkElement('<quad />');
				$bT->set("sizen", $bs["strength"] . " " . $height);
				$bT->set("posn", ($corners[0][0] - $bs["strength"]) . " " . $corners[0][1] . " " . $z);
				$bT->set("bgcolor", $bs["color"]);
			if(array_key_exists('inset', $bs))
				$bT->set("x", $corners[0][0]);
			$output.= $bT->toString();
		}
		return $output;
	}

	public function getCorners() {
		$corners = array();
		list($width, $height) = explode(' ', $this->get("sizen"));
		list($x, $y, $z) = explode(' ', $this->get("posn"));
		if ($this->halign == "left")
			$corners[0][0] = $x;
		elseif ($this->halign == "center")
			$corners[0][0] = $x - $width / 2;
		elseif ($this->halign == "right")
			$corners[0][0] = $x - $width;
		if ($this->valign == "top")
			$corners[0][1] = $y;
		elseif ($this->valign == "center")
			$corners[0][1] = $y + $height / 2;
		elseif ($this->valign == "bottom")
			$corners[0][1] = $y + $height;
		$corners[1][0] = $corners[0][0] + $width;
		$corners[1][1] = $corners[0][1];
		$corners[2][0] = $corners[0][0];
		$corners[2][1] = $corners[0][1] - $height;
		$corners[2][0] = $corners[0][0] + $width;
		$corners[2][1] = $corners[0][1] - $height;
		return $corners;
	}

	private function convertValues($attribute, $value) {
		$attribute = strtolower($attribute);
		$colors = array('textcolor', 'focusareacolor1', 'focusareacolor2', 'bgcolor');
		if($attribute == "color" && $this->type=="label")
			$attribute = "textcolor";
		if($attribute == "color" && $this->type=="quad")
			$attribute = "bgcolor";
		if($attribute == "background-color" && $this->type=="quad")
			$attribute = "bgcolor";
		if(in_array($attribute, $colors)){
			$value = preg_replace('/#([0-9a-fA-F]{3,4})/', '\1', $value);
		}
		if($attribute == "strip-tags" && $this->type=="label" && $this->get("text") != null)
		{
			$value = ManialinkAnalizer::strip_tags_tm($this->text, $value);
			$attribute = "text";
		}
		if($attribute == "underline" && $this->type=="label" && $this->get("text") != null)
		{
			switch ($value) {
				case 'null':
				case 'none':
				case 'false':
					$value = $this->get("text");
				case 'blue':
					$t = "h";
					break;
				case 'orange':
				default:
					$t = "l";
					break;
			}
			if(isset($t))
				$value = '$'.$t.'[]'.$this->get("text").'$'.$t;
			$attribute = "text";
		}

		$result = array("attribute"=>$attribute, "value"=>$value);
		return $result;
	}

	public function getManiaScriptType() {
		switch ($this->type) {
			default:
				return "CMl" . ucfirst($this->type);
				break;
		}
	}

	public function getManiaScriptDeclare($name) {
		if(!isset($this->id))
			return false;
		return 'declare ' . $this->getManiaScriptType() . ' ' . $name .' <=> (Page.GetFirstChild("'.$this->id.'") as ' . $this->getManiaScriptType() . ');';
	}

	public function append($string) {
		$this->toAppend.=$string;
		return $this;
	}

}
?>