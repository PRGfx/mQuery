<?php
namespace ManiaQuery;

class ManiaqueryParser {

	private $in;

	private $bracket_round = 0;
	private $bracket_curly = 0;
	private $bracket_square = 0;
	private $string_delimiter = "";

	private $strings = array();
	private $variables = array();
	private $jqueryStacks = array();

	public function __construct($input) {
		$this->in = $input;
		$this->shortHand();
	}

	private function shortHand() {
		$this->in = preg_replace('/substr\((.*)\)/s', 'TextLib::SubString(\1)', $this->in);
		$this->in = preg_replace('/round\((.*)\)/s', 'MathLib::NearestInteger(\1)', $this->in);
		$this->in = preg_replace('/ceil\((.*)\)/s', 'MathLib::CeilingInteger(\1)', $this->in);
		$this->in = preg_replace('/floor\((.*)\)/s', 'MathLib::FloorInteger(\1)', $this->in);
		$this->in = preg_replace('/toString\((.*)\)/is', 'TextLib::ToText(\1)', $this->in);
	}

	private function isString() {
		return ($this->string_delimiter == "'" || $this->string_delimiter == '"');
	}

	public function parse() {
		$jqueryStacks = array();
		$variables = array();
		$match = array();
		$start = 0;
		$dots = 0;
		$selector = false;
		$state = "0";
		for ($cursor = 0; $cursor < strlen($this->in); $cursor++) {
			$char = $this->in[$cursor];
			if ($cursor >= 1)
				$pred = $this->in[$cursor-1];
			// no string
			if ($this->string_delimiter == "") {
				// escaped?
				if(!isset($pred) || $pred != "\\") {
					if ($char == "'") {
						$this->string_delimiter = "'";
						$this->strings[$cursor] = "";
					}
					elseif ($char == '"') {
						$this->string_delimiter = '"';
						$this->strings[$cursor] = "";
					}
					if($char == ".")
						$dots++;
					// brackets
					if ($char == "(")
						$this->bracket_round++;
					if ($char == ")")
						$this->bracket_round--;
					if ($char == "[")
						$this->bracket_square++;
					if ($char == "]")
						$this->bracket_square--;
					if ($char == "{")
						$this->bracket_curly++;
					if ($char == "}")
						$this->bracket_curly--;
					// search for jquery stacks
					switch ($state) {
						case '0':
							$match = array();
							$start = $cursor;
							$dots = 0;
							$match["raw"] = "";
							if($char == '$') {
								$match["selector"] = $char;
								$match["functions"] = array();
								$fn = null;
								$state = "sel";
							}
							if($char == 'g') {
								$match["global"] = "g";
								$state = "g1";
							}
							if($char == 'v') {
								$match["global"] = false;
								$state = "v1";
							}
							if(preg_match('/[gv]/', $char)) {
								$match["name"] = "";
								$match["value"] = "";
								$match["type"] = "";
							}
							break;
						case "sel":
							switch ($char) {
								case '(':
									$state = "sel2";
									$match["selector"].= $char;
									break;
								case '.':
									$state = "fn1";
									break;
								case (is_numeric($char) ? true : false):
									$state = 0;
									break;
								case (preg_match('/[a-zA-Z0-9_]/', $char) ? true : false):
									$state = "sel5";
									$match["selector"].= $char;
									break;
								default:
									$match["selector"].= $char;
									break;
							}
						break;
						case "sel2":
							switch ($char) {
								case ')':
									$state = "sel3";
									$match["selector"].= $char;
									break;
								case '"':
								case "'":
									$state = "sel2";
									$match["selector"].= $char;
									break;
								case (preg_match('/[a-zA-Z0-9_]/', $char) ? true : false):
									$state = "sel4";
									$match["selector"].= $char;
									break;
								default:
									$state = "0";
									break;
							}
						break;
						case "sel3":
							if ($char == ".")
								$state = "fn1";
							else
								$state = "0";
						break;
						case "sel4":
							switch ($char) {
								case ')':
									$state = "sel3";
									$match["selector"].= $char;
									break;
								case (preg_match('/[a-zA-Z0-9_]/', $char) ? true : false):
									$match["selector"].= $char;
									break;
								default:
									$state = "0";
									break;
							}
						break;
						case "sel5":
							switch ($char) {
								case (preg_match('/[a-zA-Z0-9_]/', $char) ? true : false):
									$match["selector"].= $char;
									break;
								case ".":
									$state = "fn1";
									break;
								default:
									$state = "0";
									break;
							}
						break;
						case "fn1":
							if (preg_match('/[a-z_]/i', $char)) {
								$state = "fn2";
								$fn["name"] = $char;
							}
							else
								$state = "0";
						break;
						case "fn2":
							if (preg_match('/[a-z_0-9]/i', $char)){
								$state = "fn2";
								$fn["name"].= $char;
							}
							elseif ($char == "(")
								$state = "param";
							else
								$state = "0";
						break;
						case "param":
							if ($char == ")")
								$state = "fn3";
							else {
								$state = "p";
								$fn["parameters"][] = $char;
							}
						break;
						case "p":
							if ($this->bracket_round == 1 && $this->bracket_curly == 0 && $this->bracket_square == 0 && $char == ",") {
								$state = "param";
							}
							elseif ($char == ")" && $this->bracket_round == 0 && $this->bracket_curly == 0 && $this->bracket_square == 0) {
								$state = "fn3";
							}
							else {
								// $state = "p";
								addToLastKey($fn["parameters"], $char);
							}
						break;
						case "fn3":
							$match["functions"][] = $fn;
							if ($char == ".") {
								$state = "fn1";
								$fn["parameters"] = null;
							}
							elseif ($char == ";") {
								$state = "0";
								$match["raw"].= $char;
								$match["init"] = $start;
								$jqueryStacks[$start] = $match;
							}
							else
								$state = "0";
						break;
						case "g1":
							$st = "global";
							if($char == substr($st, strlen($match["global"])-strlen($st), 1))
								$match["global"].=$char;
							else {
								if (strlen($match["global"]) == strlen($st)) {
									if($char == "v")
										$state = "v1";
									elseif ($char == " ")
										$state = "g1";
									else
										$state = "0";
								}
							}
						break;
						case "v1":
							$st = "var";
							($char == "a")?$state = "v2":$state = "0";
						break;
						case "v2":
							$st = "var";
							($char == "r")?$state = "v3":$state = "0";
						break;
						case "v3":
							$st = "var";
							if($char == " ")
								$state = "v3";
							else {
								if (preg_match('/[a-z_]/i', $char)) {
									$state = "vn";
									$match["name"].=$char;
								} elseif ($char == "(") {
									$state = "vt";
								} else
									$state = "0";
							}
						break;
						case "vt":
							if(preg_match('/[a-z0-9]/i', $char)) {
								$match["type"].=$char;
							} elseif ($char == ")") {
								$state = "vn";
							} else
								$state = "0";
						break;
						case "vn":
							if(preg_match('/[a-z0-9_]/i', $char)) {
								$match["name"].=$char;
							} elseif ($char == " ") {
								$state = $state;
							} elseif ($char == "=") {
								$state = "vv";
							} elseif ($char == ";") {
								$match["init"] = $start;
								$this->variables[] = $match;
								$state = "0";
							} else
								$state = "0";
						break;
						case "vv":
							if ($char == ";") {
								$match["init"] = $start;
								$this->variables[] = $match;
								$state = "0";
							} else
								$match["value"].= $char;
						break;
						default:
							echo ' something somewhere went terribly wrong!<br/>';
					}
					// continue;
				}
			} else {
				// string
				if($char == $this->string_delimiter && $pred != '\\')
					$this->string_delimiter = "";
				else {
					addToLastKey($this->strings, $char);
				}
				switch ($state) {
					case 'sel2':
						$match["selector"].= $char;
						break;
					case 'p':
						addToLastKey($fn["parameters"], $char);
						break;
					case 'vv':
						$match["value"].=$char;
						break;
					
					default:
						# code...
						break;
				}
			}
			if($state != "0")
				$match["raw"].= $char;
			// echo '@' . $cursor . ', zeichen: <b>' . $char . '</b>, zustand: <b>' . $state . '</b> stringstate: '.$this->string_delimiter.'<br>';
			// echo '  round: ' . $this->bracket_round . ', curly: ' . $this->bracket_curly . ', square: ' . $this->bracket_square . '<br/>';
		}
		// print_r($this->strings);
		// print_r($jqueryStacks);
		// print_r($this->variables);
		$this->jqueryStacks = $jqueryStacks;
	}

	public function getJqueryStacks() {
		return $this->jqueryStacks;
	}

	public function getVariables() {
		return $this->variables;
	}

	public function getStrings() {
		return $this->strings;
	}

	public static function parseObj($string) {
		if(!is_string($string))
			return $string;
		$string = trim($string);
		// if(!preg_match('/^\{(.*)\}$/s', $string))
		// 	return $string;
		$string_delimiter = "";
		$result = new \stdClass();
		$state = 0;
		$copen = 0;
		$sopen = 0;
		for($i=0; $i<strlen($string); $i++) {
			$char = $string[$i];
			if($state != "3") {
				if($char=="{")
					$copen++;
				if($char=="}")
					$copen--;
				if($char=="[")
					$sopen++;
				if($char=="]")
					$sopen--;
			}
			switch ($state) {
				// default, wainting vor the key
				case 0:
					$match = array("key"=>"", "value"=>"");
					if(preg_match('/[a-z]/i', $char)) {
						$match["key"] = $char;
						$state = 1;
					}
					break;
				// matching the key
				case 1:
					if(preg_match('/[a-z0-9_]/i', $char)) {
						$match["key"].= $char;
					} elseif ($char == ":") {
						$state = 2;
					} else
						$state = 0;
					break;
				// matching the value
				case 2:
					if ($char == "," || $char == "}") {
						if($sopen == 0 && (($copen == 0 && $char == "}") || ($copen == 1 && $char==",")))
						{
							$key = trim($match["key"]);
							$match["value"] = preg_replace('/^(\"|\')(.*)\1$/s', '\2', trim($match["value"]));
							if($match["value"] === "true")
								$match["value"] = true;
							if($match["value"] === "false")
								$match["value"] = false;
							$result->$key = $match["value"];
							if($char=="}")
								return $result;
							$state = 0;
							continue;
						}
					}
					if(preg_match('/(\"|\')/', $char, $s)) {
						$string_delimiter = $s[1];
						$state = 3;
						if($match["value"][0] != $string_delimiter && strlen($match["value"]) > 0)
							$match["value"].= $char;
					} else 
						$match["value"].= $char;
					break;
				// matching strings
				case 3:
					if($char == $string_delimiter && $string_delimiter!="" && $string[$i-1]!="\\") {
						$string_delimiter = "";
						$state = 2;
						if($match["value"][0] != $string_delimiter)
							$match["value"].= $char;
					} else 
						$match["value"].= $char;
					break;
			}
			// echo 'char: '.$char.', state: '.$state.', brackets: ['.$sopen.', {'.$copen.'<br/>';
		}
		return $result;
	}

}

function addToLastKey(&$stack, $value) {
	$lastKey = max(array_keys($stack));
	$stack[$lastKey].=$value;
}

// $in = file_get_contents('script.mq');
// $test = new ManiaqueryParser($in);
// $test->parse();

?>