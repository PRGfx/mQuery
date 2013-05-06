<?php
namespace ManiaQuery;

require_once('ManiaqueryParser.php');

class ManiaQuery
{
	private $input;
	private $ManialinkAnalizer;
	private $MScript;
	private $availableFunctions = array();
	private $calledFunctions = array();
	
	function __construct($ManialinkAnalizer, $script = false)
	{
		$this->ManialinkAnalizer = $ManialinkAnalizer;
		if ($script)
			$this->input = $script;
		else {
			$scripts = $this->ManialinkAnalizer->scriptFiles();
			foreach ($scripts as $script) {
				if (!file_exists($script))
					throw new \Exception("The script file '$script' could not be found!", 1);
				elseif (!$data = file_get_contents($script))
					throw new \Exception("The file '$script' could not be accessed!", 1);
				else
					$this->input.= $data;
			}
		}
		$this->MScript = $this->ManialinkAnalizer->scriptHandler();
		try {
			$this->identifyFunctions();
		} catch (\Exception $e) {
			$this->MScript->addCodeToMain('log("'.$e->getMessage().'");');
		}
	}

	private function identifyFunctions() {
		foreach (glob(dirname(__FILE__)."/mqFunctions/*.php") as $file)
		{
		    require_once($file);
		    $d = file_get_contents($file);
		    preg_match_all('/function mq_(\w+)/', $d, $names, PREG_SET_ORDER);
		    foreach ($names as $fn) {
		    	$this->availableFunctions[] = $fn[1];
		    }
		}
		$scriptHandler = $this->MScript;
		$ManiaqueryParser = new ManiaqueryParser($this->input);
		$ManiaqueryParser->parse();

		// variables
		$variables = array();
		$variables = $ManiaqueryParser->getVariables();
		foreach($variables as $var) {
			$var["type"] = $this->adjustType($var["type"]);
			if($var["value"] != "")
				$var["value"] = $this->adjustValue($var["type"], $var["value"]);
			if((bool) $var["global"] === true) {
				$scriptHandler->declareGlobalVariable($var["type"], $var["name"]);
			}
			try {
				$scriptHandler->declareMainVariable($var["type"], $var["name"], (bool) $var["global"], $var["value"]);
			} catch (\Exception $e) {
				$scriptHandler->addCodeToMain('log("'.$e->getMessage().'");');
			}
			// var_dump($var);
			// echo'<hr>';
		}

		// $stacks = $ManiaqueryParser->parse();
		// $stacks = array_values($stacks);
		// print_r($stacks);
		$stacks = $ManiaqueryParser->getJqueryStacks();
		foreach ($stacks as $stack) {
			$selector = $stack["selector"];
			foreach ($stack["functions"] as $function) {
				if (!$this->mq_function_exists($function["name"])) {
					throw new \Exception("Error: '".$function["name"]."()' is not a valid function!", 1);
					continue;
				} else {
					$mq_function = "mq_" . $function["name"];
					$elements = $this->getElements($selector);
					if(empty($elements))
						throw new \Exception("Notice: No elements matching '".addslashes($selector)."' found!", 1);
						// $scriptHandler->addCodeToMain('log("No elements matching \''.addslashes($selector).'\' found!");');
					foreach($elements as $key=>$element) {
						if(!$element instanceof \ManialinkAnalysis\ManialinkElement)
							continue;
						$this->prepareAttributes($function["parameters"]);
						if (empty($function["parameters"]))
							$function["parameters"] = array();
						$parameters = array_merge(array($this, $element), $function["parameters"]);
						$uses = $this->useFn($mq_function);
						if($uses == 1 && file_exists(dirname(__FILE__)."/mqStyles/".$mq_function.".css"))
							$this->ManialinkAnalizer->addStyleSheet(dirname(__FILE__)."/mqStyles/".$mq_function.".css");
						try {
							$new = call_user_func_array($mq_function, $parameters);
						} catch (\Exception $e) {
							throw new \Exception($e->getMessage(), 1);
						}
					}
					$elements = null;
				}
			}
		}
	}

	private function adjustType($type) {
		if(empty($type))
			return "Text";
		$type = strtolower($type);
		if($type == "int")
			$type = "integer";
		if(in_array($type, array("real", "float", "double")))
			$type = "real";
		if($type == "string")
			$type = "text";
		if(in_array($type, array("quad", "label", "entry", "fileentry", "event", "frame")))
			$type = "CMl" . ucfirst($type);
		return ucfirst($type);
	}

	private function adjustValue(&$type, $value) {
		if($value!=" ")
			$value = trim($value);
		if(!preg_match('/^\$/', trim($value))) {
			// $value = preg_replace('/^(\"|\')(.*)\1$/', '\2', $value);
			if(is_numeric($value)) {
				if(preg_match('/\./', $value))
					$value = (double) $value;
				else
					$value = (integer) $value;
			}
			if($type == "Text" && gettype($value) != "string")
				$value = "TextLib::ToText(".$value.")";
			if($type == "Real" && gettype($value) == "integer")
				$value.= ".0";
			if($type == "Real" && gettype($value) == "string")
				$value.= "TextLib::ToReal(".$value.")";
			if($type == "Integer" && gettype($value) == "string")
				$value.= "TextLib::ToInteger(".$value.")";
			if($type == "Integer" && gettype($value) == "double")
				$value.= "MathLib::NearestInteger(".$value.")";
		} else {
			$elements = $this->getElements($value);
			if(count($elements) > 1) {
				$type = $elements[0]->getManiaScriptType() . '[Integer]';
				$declared = array();
				foreach ($elements as $key => $element) {
					$varN = "mqAutoDec_" . preg_replace('/\$\((\"|\')(\.|#)?(.*)\1\)/', '\3', $value) . $key;
					$declared[] = $varN;
					if($element->get("id")==null)
						$element->set("id", $varN);
					$scriptHandler->addCodeToMain($element->getManiaScriptDeclare($varN));
				}
				$value = '['.implode(', ', $declared).']';
			}
			elseif (count($elements <= 0)) {
				return $value;
			}
			else
				$type = $elements[0]->getManiaScriptType();
		}
		return $value;
	}

	private function useFn($mq_function) {
		if(!array_key_exists($mq_function, $this->calledFunctions))
			$this->calledFunctions[$mq_function] = 1;
		else
			$this->calledFunctions[$mq_function]+=1;
		return $this->calledFunctions[$mq_function];
	}

	private function getElements($selector)
	{
		$matches = array();
		$type = "tag";
		$selector = preg_replace('/^\$/', '', $selector);
		if (preg_match('/\((\"|\')#(.*)\1\)/', $selector, $match)) {
			$type = "id";
			$matches[0] = $this->ManialinkAnalizer->getElementById($match[2]);
			if($matches[0] == null)
				return array();
			return $matches;
		}
		elseif (preg_match('/\((\"|\')\.(.*)\1\)/', $selector, $match)) {
			$type = "class";
			return $this->ManialinkAnalizer->getElementsByClass($match[2]);
		}
		elseif (preg_match('/\((\"|\')(.*)\1\)/', $selector, $match)) {
			$type = "class";
			return $this->ManialinkAnalizer->getElementsByTag($match[2]);
		}
	}

	private function mq_function_exists($function)
	{
		return in_array($function, $this->availableFunctions);
	}

	public function scriptHandler() {
		return $this->MScript;
	}

	public function updateElement($element) {
		$this->ManialinkAnalizer->updateElement($element);
	}

	public function append($code, $id = false) {
		$this->ManialinkAnalizer->append($code, $id);
	}

	private function prepareAttributes(&$attributes) {
		if (!empty($attributes) && is_array($attributes)) {
			foreach ($attributes as $key=>$attribute) {
				$attribute = preg_replace('/^function\(([^)]*)\)\s*?\{(.*)}$/s', '\2', trim($attribute));
				$attribute = trim($attribute);
				if(!preg_match('/;$/', $attribute))
					$attribute.=';';
				$attributes[$key] = $attribute;
			}
		}
	}

	public function getUses($function) {
		if (array_key_exists($function, $this->calledFunctions))
			return $this->calledFunctions[$function];
		return 0;
	}

	public static function jsobj2php($obj) {
		return ManiaqueryParser::parseObj($obj);
		$obj = preg_replace('/^(\s*)?(\w+): /m', '"\2": ', $obj);
		$obj = preg_replace('/;$/s', '', $obj);
		return json_decode($obj);
	}

	public static function obj2str($obj) {
		$str = json_encode($obj);
		$str = preg_replace('/\"([^\"]*)\":/','\1:', $str);
		return $str;
	}
}

function is_in_array($array,$index){
	if (is_array($array)){
		if (array_key_exists($index,$array)){
			return false;
		}else{
			return true;
		}
	}else{
		return true;
	}
}
?>