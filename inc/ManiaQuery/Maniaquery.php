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
	private $variables;
	private $CustomClass;
	
	/**
	 * ManiaQuery manager.
	 * @param \ManialinkAnalysis\ManialinkAnalizer $ManialinkAnalizer Usually the ManialinkAnalizer
	 *   instance working with this class.
	 * @param string $additional Optional additional ManiaQuery script.
	 */
	public function __construct($ManialinkAnalizer, $additional = "")
	{
		$this->ManialinkAnalizer = $ManialinkAnalizer;
		$scripts = $this->ManialinkAnalizer->scriptFiles();
		foreach ($scripts as $script) {
			if (!file_exists($script))
				throw new \Exception("The script file '$script' could not be found!", 1);
			elseif (!$data = file_get_contents($script))
				throw new \Exception("The file '$script' could not be accessed!", 1);
			else
				$this->input.= $data;
		}
		if (!empty($additional))
			$this->input.= $additional;

		$this->MScript = $this->ManialinkAnalizer->scriptHandler();
		try {
			$this->identifyFunctions();
		} catch (\Exception $e) {
			$this->MScript->addCodeToMain('log("'.$e->getMessage().'");');
		}
	}

	/**
	 * capsels everything
	 */
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
			$var["value"] = trim($var["value"]);
			if(preg_match('/^\$/', $var["value"]))
				$var["type"] = "CMlControl";
			elseif (preg_match('/new ([A-Z].*)\((.*?)\)/', $var["value"], $class)) {
				$var["type"] = "Class";
				$var["class"] = $class[1];
				$var["attr"] = $class[2];
			}
			else {
				$var["type"] = $this->adjustType($var["type"], $var["value"]);
				if($var["value"] != "")
					$var["value"] = $this->adjustValue($var["type"], $var["value"]);
			}
			// var_dump($var);
			if($var["type"] == "CMlControl")
			{
				$elements = $this->getElements($var["value"]);
				$selectorContent = preg_replace('/\$\((\"|\')(\.|#)?(.*)\1\)/', '\3', $var["value"]);
				if(count($elements) > 1) {
					$var["type"].= '[]';
					$declared = array();
					foreach ($elements as $key => $element) {
						$varN = "mqAutoDec_" . $selectorContent . $key;
						$declared[] = $varN;
						if($element->get("id")==null)
							$element->set("id", $varN);
						$scriptHandler->addCodeToMain($element->getManiaScriptDeclare($varN));
					}
					$value = ' = ['.implode(', ', $declared).'];';
				} elseif (count($elements == 1)) {
					$element = $elements[0];
					$varN = "mqAutoDec_" . $selectorContent;
					$type = "CMlControl";
					if($element->get("id")==null)
						$element->set("id", $varN);
					$value = explode('<=>', $element->getManiaScriptDeclare($var["name"]));
					$value = ' <=> ' . trim($value[1]);
				}
				if((bool) $var["global"] === true) {
					$scriptHandler->addCodeBeforeMain("declare " . $var["type"] . " " . $var["name"] . ";");
					$scriptHandler->addCodeToMain($var["name"] .$value);
				} else {
					$scriptHandler->addCodeToMain("declare " . $var["type"] . " " . $var["name"] .$value);
				}
			}elseif ($var["type"] == "Class") {
				// $scriptHandler->addCodeToMain('log("'.$var["class"].'");');
				// initiate class
				$fnname = $var["class"].'_construct';
				if($scriptHandler->isFunction($fnname))
				{
					$id = $var["name"];
					$binder = new \ManialinkAnalysis\ManialinkElement('<quad sizen="0 0" />');
					$binder->set("id", $id)->set("hidden", "1");
					$this->ManialinkAnalizer->append($binder->toString());
					$scriptHandler->addCodeBeforeMain('declare CMlControl '.$id.';');
					$scriptHandler->addCodeToMain($id.' <=> (Page.GetFirstChild("'.$id.'") as CMlControl);');
				}
				// var_dump($var);
			} else {
				if ((bool) $var["global"] === true) {
					$scriptHandler->declareGlobalVariable($var["type"], $var["name"]);
					// $scriptHandler->addCodeBeforeMain("declare " . $var["type"] . " " . $var["name"] . ";");
					try {
						$scriptHandler->declareMainVariable($var["type"], $var["name"], false, $var["value"]);
					} catch (\Exception $e) {
						$scriptHandler->addCodeToMain('log("'.$e->getMessage().'");');
					}
				}
				try {
					$scriptHandler->declareMainVariable($var["type"], $var["name"], (bool) $var["global"], $var["value"], true);
				} catch (\Exception $e) {
					$scriptHandler->addCodeToMain('log("'.$e->getMessage().'");');
				}
			}
			$this->variables[] = $var;
		}
		// var_dump($this->variables);

		$stacks = $ManiaqueryParser->getJqueryStacks();
		// var_dump($stacks);
		foreach ($stacks as $stack) {
			$selector = $stack["selector"];
			if (strpos($selector, '(') === false && !empty($selector) && strlen($selector) > 1) {
				$selector = substr($selector, 1);
				if ($this->varDefined($selector, $stack["init"]) === false) {
					throw new \Exception("Error: the variable '".$selector."' is not defined!", 1);
					continue;
				} else {
					$var = $this->variables[$this->varDefined($selector, -1)];
					$selector = $var["value"];
				}
			} else {
				$var = null;
			}
			foreach ($stack["functions"] as $function) {
				if (isset($var) && $var["type"] == "Class") {
					if(!array_key_exists("parameters", $function))
						$function["parameters"] = array();
					$p = array_reverse($function["parameters"]);
					$p[] = $var["name"];
					$p = array_reverse($p);
					$fnname = $var["class"] .'_' . $function["name"];
					$fncall = $fnname . '('.implode(', ', $p).');';
					# no parameters
					$reg1 = '/\$('.$var["name"].')\.([a-z0-9A-Z_]*)\(\)/';
					$reg2 = $var["class"] . '_\2(\1)';
					$scriptHandler->addReplace(array($reg1, $reg2));
					# with parameters
					$reg1 = '/\$('.$var["name"].')\.([a-z0-9A-Z_]*)\((.+?)\)/';
					$reg2 = $var["class"] . '_\2(\1, \3)';
					$scriptHandler->addReplace(array($reg1, $reg2));
					// var_dump($fncall);
				} else {
					if($selector == "$")
					{
						if(count($function["parameters"]) == 1)
						{
							$code = trim(substr($function["parameters"][0], 1, -1));
							switch ($function["name"]) {
								case 'main':
									$this->MScript->addCodeToMain($code);
									break;
								case 'loop':
									$this->MScript->addCodeToLoop($code);
									break;
								case 'body':
									$this->MScript->addCodeBeforeMain($code);
									break;
							}
						}
					} else {
						if (!$this->mq_function_exists($function["name"])) {
							throw new \Exception("Error: '".$function["name"]."()' is not a valid function!", 1);
							continue;
						} else {
							$mqFunction = "mq_" . $function["name"];
							$elements = $this->getElements($selector);
							/* $selector == '$' for global function calls */
							/* not implemented yet! */
							if(empty($elements))
								throw new \Exception("Notice: No elements matching '".addslashes($selector)."' found!", 1);
							foreach ($elements as $key=>$element) {
								if(!$element instanceof \ManialinkAnalysis\ManialinkElement)
									continue;
								$this->prepareAttributes($function["parameters"]);
								if (empty($function["parameters"]))
									$function["parameters"] = array();
								$parameters = array_merge(array($this, $element), $function["parameters"]);
								$uses = $this->useFn($mqFunction);
								if($uses == 1 && file_exists(dirname(__FILE__)."/mqStyles/".$mqFunction.".css"))
									$this->ManialinkAnalizer->addStyleSheet(dirname(__FILE__)."/mqStyles/".$mqFunction.".css");
								try {
									$new = call_user_func_array($mqFunction, $parameters);
								} catch (\Exception $e) {
									throw new \Exception($e->getMessage(), 1);
								}
							}
							$elements = null;
						}
					}
				}
			}
		}
	}

	/**
	 * Enables multiple (espacially shorthand-)versions of data types.
	 * e.g. int->Integer, quad->CMlQuad, string->Text etc.
	 */
	private function adjustType($type, $value="")
	{
		if ($value!=" ")
			$value = trim($value);
		if ($value == "True" || $value == "False")
			return "Boolean";
		if (is_numeric($value)) {
			if(preg_match('/\./', $value))
				$type = "real";
			else
				$type = "int";
		}
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

	/**
	 * Does conversion stuff in the output for ManiaScript code.
	 * e.g. $type differs from the read type of $value, it will be fixed with
	 * TextLib::ToText(), MathLib::ToInteger() etc.
	 */
	private function adjustValue(&$type, $value)
	{
		if($value!=" ")
			$value = trim($value);
		if (!preg_match('/^\$/', trim($value))) {
			// $value = preg_replace('/^(\"|\')(.*)\1$/', '\2', $value);
			if (is_numeric($value)) {
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
				$value = "TextLib::ToReal(".$value.")";
			if($type == "Integer" && gettype($value) == "string")
				$value = "TextLib::ToInteger(".$value.")";
			if($type == "Integer" && gettype($value) == "double")
				$value = "MathLib::NearestInteger(".$value.")";
		}
		return $value;
	}

	/**
	 * Increases the use-counter of a function.
	 * @return int Number of uses of the function.
	 */
	private function useFn($mqFunction) {
		if(!array_key_exists($mqFunction, $this->calledFunctions))
			$this->calledFunctions[$mqFunction] = 1;
		else
			$this->calledFunctions[$mqFunction]+=1;
		return $this->calledFunctions[$mqFunction];
	}

	/**
	 * Interprets the selector and returns the ManialinkElement objects
	 * for .class, #id or type respectively.
	 * Selectors don't support multiple idents (yet?)!
	 *
	 * @todo add support for multiple idents?
	 */
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
		} elseif (preg_match('/\((\"|\')\.(.*)\1\)/', $selector, $match)) {
			$type = "class";
			return $this->ManialinkAnalizer->getElementsByClass($match[2]);
		} elseif (preg_match('/\((\"|\')(.*)\1\)/', $selector, $match)) {
			$type = "class";
			return $this->ManialinkAnalizer->getElementsByTag($match[2]);
		}
	}

	/**
	 * Checks wether or not a certain function has been defined.
	 * @param string $function function name to check.
	 * @return boolean True, if function is defined, false otherwise.
	 */
	private function mq_function_exists($function)
	{
		return in_array($function, $this->availableFunctions);
	}

	/**
	 * @return the ManiscriptHandler for further access.
	 */
	public function scriptHandler()
	{
		return $this->MScript;
	}

	/**
	 * Calls the \ManialinkAnalysis\ManialinkAnalizer's updateElement method, replacing the old
	 * ManialinkElement with the modified version.
	 * @param \ManialinkAnalysis\ManialinkElement $element
	 */
	public function updateElement($element)
	{
		$this->ManialinkAnalizer->updateElement($element);
	}

	/**
	 * Calls the \ManialinkAnalysis\ManialinkAnalizer's append method, adding xml-code after the
	 * opening tag of the element with $id if given, or at the end of the manialink otherwise.
	 * @param string $code xml code to be appended.
	 * @param string $id see method description :P
	 */
	public function append($code, $id = false)
	{
		$this->ManialinkAnalizer->append($code, $id);
	}

	private function prepareAttributes(&$attributes)
	{
		if (!empty($attributes) && is_array($attributes)) {
			foreach ($attributes as $key=>$attribute) {
				$attribute = preg_replace('/^function\(([^)]*)\)\s*?\{(.*)}$/s', '\2', trim($attribute));
				$attribute = trim($attribute);
				if(!empty($attribute) && !preg_match('/\}|;$/', $attribute))
					$attribute .= ";";
				$attributes[$key] = $attribute;
			}
		}
	}

	/**
	 * Returns how often the function has been called. Note that binding a function on a selector matching
	 * multiple elements is respectively.
	 */
	public function getUses($function)
	{
		if (array_key_exists($function, $this->calledFunctions))
			return $this->calledFunctions[$function];
		return 0;
	}

	/**
	 * @param string Object in javascript notation
	 * @return \StdClass Object of an object in javascript notation (fixing some errors of json_decode)
	 */
	public static function jsobj2php($obj)
	{
		return ManiaqueryParser::parseObj($obj);
		/* $obj = preg_replace('/^(\s*)?(\w+): /m', '"\2": ', $obj);
		$obj = preg_replace('/;$/s', '', $obj);
		return json_decode($obj); */
	}

	/**
	 * @param \StdClass Php object to encode
	 * @return string Adjusted version of json_encode
	 */
	public static function obj2str($obj)
	{
		$str = json_encode($obj);
		$str = preg_replace('/\"([^\"]*)\":/', '\1: ', $str);
		$str = preg_replace('/\"(function\(([^)]*)\)\s*?\{(.*)})\"/s', '\1', trim($str));
		preg_match_all('/\"(\[(.*)\])\"/s', $str, $arrays, PREG_SET_ORDER);
		foreach ($arrays as $a) {
			$str = str_replace($a[0], '['.stripslashes($a[2]).']', $str);
		}
		$str = str_replace(array('\n', '\t', '\r'), '', $str);
		return $str;
	}

	private function varDefined($variable, $pos){
		foreach ($this->variables as $key=>$var) {
			if ($var["name"] == $variable && ($var["init"] < $pos || $pos < 0)) {
				return $key;
			}
		}
		return false;
	}
}

/**
 * Somehow required, didn't want to adjust stuff :D
 */
function is_in_array($array, $index)
{
	if (is_array($array)) {
		if (array_key_exists($index, $array)) {
			return false;
		} else {
			return true;
		}
	} else {
		return true;
	}
}
?>