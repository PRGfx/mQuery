<?php
namespace ManiaQuery;

/**
 * Handles ManiaScript.
 * Originally made for Boxes.
 *
 * @author Blade
 * @author zocka
 * @version 07/19/2013
 */
class Maniascript
{
	public $globalVariables = array();
	public $functions = array();
	public $anonFunctions = array();
	public $mainVariables = array();
	public $mainContent = array();
	public $loopContent = array();
	public $loopEndContent = array();
	public $keyPressEvents = array();
	public $mouseClickEvents = array();
	public $mouseOverEvents = array();
	public $mouseOutEvents = array();
	public $areaClickEvents = array();
	public $subEvents = array();
	public $afterMainContent = array();
	public $finalResult = "";
	public $beforeMainContent = "";
	private $replaces = array();
	private $inlineUses = array();

	private $built = false;
	
	/**
	 * Declares a variable outside of every function. It will be declared 'for Page'.
	 *
	 * @param string $dataType The ManiaScript datatype.
	 * @param string $name The name for the variable.
	 * @throws Exception if there already is a variable with this name.
	 */
	public function declareGlobalVariable($dataType, $name)
	{
		global $alert;
		$arrayTest = is_in_array($this->globalVariables, $name);
		if ($arrayTest) {
			$this->globalVariables[$name] = $dataType; 
		} else {
			throw new \Exception("The variable ".$name." already exists!");
		}
	}
	
	/**
	 * Declares a ManiaScript function.
	 *
	 * @param string $dataType The function's return type as ManiaScript datatype.
	 * @param string $name The function's name.
	 * @param string $content The function's body.
	 * @param string $parameter Optional: Parameters for the function as you would write it for a ManiaScript function.
	 * @param boolean $anon Whether or not this function is defined anonymously.
	 * @throws Exception if there already is a function with this name.
	 */
	public function addFunction($dataType, $name, $content, $parameter="", $anon = false)
	{
		global $alert;
		$arrayTest = is_in_array($this->functions, $name);
		if ($arrayTest) {
			if ($anon) {
				$name = "anonFn" . (count($this->anonFunctions) + 1);
				$this->anonFunctions[$name] = array("Void", $content, $parameter);
			} else
			$this->functions[$name] = array(ucfirst($dataType), $content, $parameter);
			return $name;
		} else {
			throw new \Exception("The function ".$name." already exists!");
		}
	}

	/**
	 * Returns wether a function is already defined.
	 *
	 * @param string $name A function's name to check for.
	 * @return boolean True if the named function is already declared, false otherwise.
	 */
	public function isFunction($name)
	{
		return is_in_array($this->functions, $name);
	}

	/**
	 * Declares a variable in the main() function.
	 *
	 * @param string $dataType ManiaScript datatype for the variable.
	 * @param string $name Name for the variable.
	 * @param boolean $global States wether or not the variable should be made 
	 * 	global by declaring it for the Page object.
	 * @param string $content Optional predefined value for the variable.
	 * @param boolean $ifNotExists Able to suppres the Exception.
	 * @throws Exception if a variable with this name already is defined.
	 */
	public function declareMainVariable($dataType, $name, $global = false, $content = "", $ifNotExists = false)
	{
		global $alert;
		$arrayTest = is_in_array($this->mainVariables, $name);
		if ($arrayTest) {
			$this->mainVariables[$name] = array(ucfirst($dataType),$global,$content); 
		} else {
			if(!$ifNotExists)
				throw new \Exception("The variable ".$name." already exists!");
		}
	}
	
	/**
	 * Adds code in the main() function.
	 *
	 * @param string $code ManiaScript code to be added.
	 */
	public function addCodeToMain($code)
	{
		$this->mainContent[] = $code;
	}
	
	/**
	 * Adds code in the while(true) loop.
	 *
	 * @param string $code ManiaScript code to be added.
	 */
	public function addCodeToLoop($code)
	{
		$this->loopContent[] = $code;
	}
	
	/**
	 * Adds code at the end of the while(true) loop.
	 *
	 * @param string $code ManiaScript code to be added.
	 */
	public function addCodeToLoopEnd($code)
	{
		$this->loopEndContent[] = $code;
	}
	
	/**
	 * Declares a listener for KeyPress Events.
	 *
	 * @param string $controlID The id of the element triggering the event.
	 * @param string $code The code executed on triggering the event.
	 */
	public function addKeyPressEvent($controlID, $code)
	{
		$this->keyPressEvents[] = array($controlID, $code); 
	}
	
	/**
	 * Declares a listener for MouseClick Events.
	 *
	 * @param string $controlID The id of the element triggering the event.
	 * @param string $code The code executed on triggering the event.
	 */
	public function addMouseClickEvent($controlID, $code)
	{
		$this->mouseClickEvents[] = array($controlID, $code); 
	}
	
	/**
	 * Declares a listener for MouseClicks in a certain area Events.
	 *
	 * @param array $area The coordinates of the area where the event is triggered.
	 * @param string $code The code executed on triggering the event.
	 */
	public function addAreaClickEvent($area, $code)
	{
		$this->areaClickEvents[] = array($area, $code); 
	}
	
	/**
	 * Declares a listener for MouseOver Events.
	 *
	 * @param string $controlID The id of the element triggering the event.
	 * @param string $code The code executed on triggering the event.
	 */
	public function addMouseOverEvent($controlID, $code)
	{
		$this->mouseOverEvents[] = array($controlID, $code); 
	}
	
	/**
	 * Declares a listener for MouseOut Events.
	 *
	 * @param string $controlID The id of the element triggering the event.
	 * @param string $code The code executed on triggering the event.
	 */
	public function addMouseOutEvent($controlID, $code)
	{
		$this->mouseOutEvents[] = array($controlID, $code);   
	}
	
	/**
	 * @deprecated
	 */
	function addSubEvent($subID, $code, $event)
	{
		$this->subEvents[] = array($subID, $code, $event); 
	}
	
	/**
	 * @deprecated
	 */
	public function addCodeAfterMain($code)
	{
		$this->afterMainContent[] = $code;
	}
	
	/**
	 * Adds global code before the main() method.
	 */
	public function addCodeBeforeMain($code)
	{
		$this->beforeMainContent.= $code;
	}

	public function addReplace(array $replacer, $direct = null)
	{
		if ($direct == null) $direct = false;
		$this->replaces[] = array($replacer, $direct);
	}

	private function replaceInlineMQ($code)
	{
		$mqInlineFunctions = array();
		foreach (glob(dirname(__FILE__)."/mqInlineFunctions/*.php") as $file)
		{
		    require_once($file);
		    $d = file_get_contents($file);
		    preg_match_all('/function mq_in_(\w+)/', $d, $names, PREG_SET_ORDER);
		    foreach ($names as $fn) {
		    	$mqInlineFunctions[] = $fn[1];
		    }
		}
		$inlineMQ = $this->findInlineMQ($code);
		foreach ($inlineMQ as $function => $calls) {
			if (in_array($function, $mqInlineFunctions)) {
				foreach ($calls as $call) {
					$mqFunction = "mq_in_" . $function;
					$parameters = array_slice($call, 1);
					try {
						if (!array_key_exists($function, $this->inlineUses))
							$this->inlineUses[$function] = 0;
						$this->inlineUses[$function]++;
						$new = call_user_func_array($mqFunction, array_merge(array($this), $parameters));
						// $code = str_replace($call[0], $new, $code);
						$this->addReplace(array($call[0], $new), true);
					} catch (\Exception $e) {
						throw new \Exception($e->getMessage(), 1);
					}
				}
			}
		}
		// $anonFunctions = "";
		// foreach ($this->anonFunctions as $name=>$content) {
		// 	$anonFunctions .= $content[0]." ".$name."(".$content[2].") {".$content[1]."}";
		// }
		// $code = str_replace("###anonFunctions###", $anonFunctions, $code);
		return $code;
	}

	public function getInlineUses($function) {
		return $this->inlineUses[$function];
	}

	private function findInlineMQ($code)
	{
		$availableFunctions = array('get');
		$results = array();
		foreach ($availableFunctions as $function) {
			$allposes = strallpos($code, '$.'.$function.'(');
			if (count($allposes) > 0) {
				$results[$function] = array();
				foreach ($allposes as $pos) {
					$fnEnd = false;
					$stack = 1;
					$isString = false;
					$parameters = array("");
					$parameter = "";
					$i = $pos + strlen($function) + 3;
					$raw = "\$.$function(";
					while (!$fnEnd) {
						$char = $code[$i];
						$raw .= $char;
						if ($char == "'" || $char == '"') {
							if ($isString === false)
								$isString = $char;
							else
								$isString = false;
						}
						if ($char == '(' && $isString === false) $stack++;
						if ($char == ')' && $isString === false) $stack--;
						if ($stack >= 1) {
							if ($stack == 1 && $char == ",") {
								$parameters[] = trim($parameter);
								$parameter = "";
							} else
								$parameter .= $char;
						} else {
							if ($stack == 0 && $char == ";"){
								$parameters[] = trim($parameter);
								$fnEnd = true;
							}
						}
						$i++;
					}
					$parameters[0] = $raw;
					$results[$function][$pos] = $parameters;
				}
			}
		}
		return $results;
	}
	
	/**
	 * Puts everything together.
	 *
	 * @param boolean $return Decide if this function directly outputs
	 *  the ManiaScript or just returns it.
	 * @return If called this way, it will return the ManiaScript.
	 */
	public function build($return = true)
	{
		
		// $this->finalResult.='#Include "TextLib" as TextLib ' . "\n";
		// $this->finalResult.='#Include "MathLib" as MathLib ' . "\n";
		$this->finalResult.='declare Vec2[] _mousepath;declare Real _mouseTollerance; ';
		if (is_array($this->globalVariables)) {
			foreach ($this->globalVariables as $name=>$dataType) {
				$this->finalResult.="declare ".$dataType." ".$name.";\n";
			}
			$this->finalResult.=' ';
		}
		$this->finalResult.='declare CMlEvent Event_bak; declare Boolean Event_blocked;' . "\n" .'
			Void bakEvent(CMlEvent e) {if(!Event_blocked) {}Event_bak <=> e;}'."\n";
		$this->finalResult.=$this->beforeMainContent;
		// Tom: 2. funktion
		$this->finalResult.='
		Integer strlen(Text text)
		{
			declare length = 0;
			while(TextLib::SubString(text, length,1) != "")
			{
				length = length+1;
			}
			return length;
		}
		
		Boolean waitFor(Integer Time, Text Name)
		{
			declare Integer PassedTime for Page;
			declare Integer[Text] sleeps for Page;
			if(!sleeps.existskey(Name)) {
				sleeps[Name] = CurrentTime;
			}
			if(CurrentTime-sleeps[Name] >= Time) {
				sleeps[Name] = CurrentTime;
				return True;
			}
			
			return False;
		}
		Real _max(Real a1, Real a2){if(a1>a2)return a1;return a2;}
		Integer _max(Integer a1, Integer a2){if(a1>a2)return a1;return a2;}
		Real _min(Real a1, Real a2){if(a1<a2)return a1;return a2;}
		Integer _min(Integer a1, Integer a2){if(a1<a2)return a1;return a2;}
		Text _getMousepath()
		{
			declare Text[] prout;
			declare Real dX;
			declare Real dY;
			declare Real m;
			declare Text step;
			declare Integer j = 1;
			for (i, 1, _mousepath.count - 1) {
				step = "";
				dX = _mousepath[i][0] - _mousepath[i-j][0];
				dY = _mousepath[i][1] - _mousepath[i-j][1];
				if (dX == 0) m = 0.0;
				else m = MathLib::Abs(dY / dX);
				if (_max(MathLib::Abs(dX), MathLib::Abs(dY)) > 2) {
					j = 1;
					if (dX > 0) step ^= "R";
					if (dX < 0) step ^= "L";
					if (dY > 0) step ^= "U";
					if (dY < 0) step ^= "D";
					/*if (dX > 0 && m < _mouseTollerance) step ^= "R";
					if (dX < 0 && m < _mouseTollerance) step ^= "L";
					if (dY > 0 && (1 - m) < _mouseTollerance) step ^= "U";
					if (dY < 0 && (1 - m) < _mouseTollerance) step ^= "D";*/
					if (prout.count == 0 || prout[prout.count - 1] != step) prout.add(step);
					log("dX: " ^ TextLib::ToText(dX) ^ ", dY: " ^ TextLib::ToText(dY) ^ ", m: " ^ TextLib::ToText(m));
				} else {
					j += 1;
				}
			}
			log(prout);
			declare Text out = "";
			for (i, 0, prout.count - 2) {
				out ^= prout[i] ^ ";";
			}
			if (prout.count > 0) out ^= prout[_max(prout.count - 1, 0)];
			return out;
		}
		';
		foreach ($this->functions as $name=>$content) {
			$this->finalResult .= $content[0]." ".$name."(".$content[2].") {".$content[1]."}";
		}

		// $this->finalResult.="###anonFunctions###\n";
		foreach ($this->anonFunctions as $name=>$content) {
			$this->finalResult .= $content[0]." ".$name."(".$content[2].") {".$content[1]."}";
		}
	
		$this->finalResult.="main() {_mouseTollerance=1.0;";
		
		if (is_array($this->mainVariables)) {
			foreach ($this->mainVariables as $name=>$content) {
				if(!preg_match('/^CMl/', $content[0]))
					$this->finalResult.="declare " . $content[0] . " " . $name .  ($content[1]?" for Page ":"")
					 . ($content[2]?" = " . $content[2]:"") . ";";
				else
					$this->finalResult.="declare " . $content[0] . " " . $name .  ($content[1]?" for Page ":"")
					 . ($content[2]?" <=> (Page.GetFirstChild(\"".$content[2]."\") as " . $content[0] . ")":"") . ";";
			}
		}
		
		if (is_array($this->mainContent)) {
			foreach ($this->mainContent as $id=>$content) {
				$this->finalResult.= $content;
			}
		}
		// Tom: f
		$this->finalResult.= "declare PassedTime for Page = 0; declare lastTime = CurrentTime;\n";
		$this->finalResult.='
		while(True) {';
		if (is_array($this->loopContent)) {
			foreach ($this->loopContent as $id=>$content) {
				$this->finalResult.= $content;
			}
				
		}
		
		if (!empty($this->areaClickEvents)) {
			$this->finalResult.='if(MouseLeftButton) {';
			if (is_array($this->areaClickEvents)) {
				foreach ($this->areaClickEvents as $key=>$code) {
					$posns = $code[0];
					if ($posns[0][0]<$posns[1][0]) {
						$left = $posns[0];
						$right = $posns[1];
					} else {
						$left = $posns[1];
						$right = $posns[0];
					}
					if ($posns[0][1]<$posns[1][1]) {
						$bot = $posns[0];
						$top = $posns[1];
					} else {
						$bot = $posns[1];
						$top = $posns[0];
					}
					$this->finalResult.='if (MouseX <= '.$right[0].' && MouseX >= '.$left[0].' 
							&& MouseY <= '.$top[1].' && MouseY >= '.$bot[1].') {
						'.$code[1].'
					}';
				}
			}
			$this->finalResult.='
					_mousepath.add(<MouseX, MouseY>);
						} else {
					if (_mousepath.count > 1) {
						/*log(_getMousepath());*/
					}
					_mousepath = Vec2[];
						}';
		}

		$this->finalResult.='declare substrEventId = "";
			declare substrEventIdRest = "";
			declare Integer substrEbentIdRestLenght;
			foreach(Event in PendingEvents) {
				bakEvent(Event);
				substrEventId = TextLib::SubString(Event.ControlId^"", 0, 5);
				substrEbentIdRestLenght = strlen(Event.ControlId^"");
				substrEventIdRest = TextLib::SubString(Event.ControlId^"", 5, substrEbentIdRestLenght);';
				
		if (is_array($this->keyPressEvents) && !empty($this->keyPressEvents)) {
			$this->finalResult.="if(Event_bak.Type == CMlEvent::Type::KeyPress) {Event_blocked=True;";
				foreach ($this->keyPressEvents as $id=>$code) {
					$this->finalResult.='if (Event_bak.CharPressed == "'.$code[0].'") {'.$code[1].'
					}';
				}
			$this->finalResult.="
			}";
		}
		
		if (is_array($this->mouseClickEvents) or is_array($this->subEvents)) {
			$this->finalResult.='if(Event_bak.Type == CMlEvent::Type::MouseClick) {Event_blocked=True;';
			if (is_array($this->mouseClickEvents)) {
				foreach ($this->mouseClickEvents as $id=>$code) {
					$this->finalResult.='if (Event_bak.ControlId == "'.$code[0].'") {'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)) {
				foreach ($this->subEvents as $subId=>$code) {
					if ($code[2]=="MouseClick") {
						$this->finalResult.='if (substrEventId == "'.$code[0].'") {'.$code[1].'
						}';
					}
				}
			$this->finalResult.="
			}";
			}
		}
		
		if (is_array($this->mouseOverEvents) or is_array($this->subEvents)) {
			$this->finalResult.="if(Event_bak.Type == CMlEvent::Type::MouseOver) {Event_blocked=True;";	
			if (is_array($this->mouseOverEvents)) {
				foreach ($this->mouseOverEvents as $id=>$code) {
					$this->finalResult.='if (Event_bak.ControlId == "'.$code[0].'") {'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)) {
				foreach ($this->subEvents as $subId=>$code) {
					if ($code[2]=="MouseOver") {
						$this->finalResult.='if (substrEventId == "'.$code[0].'") {'.$code[1].'
						}';
					}
				}
			$this->finalResult.="
			}";
			}
		}
		
		if (is_array($this->mouseOutEvents) or is_array($this->subEvents)) {
			$this->finalResult.="if(Event_bak.Type == CMlEvent::Type::MouseOut) {Event_blocked=True;";
			if (is_array($this->mouseOutEvents)) {
				foreach ($this->mouseOutEvents as $id=>$code) {
					$this->finalResult.='if (Event_bak.ControlId == "'.$code[0].'") {'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)) {
				foreach ($this->subEvents as $subId=>$code) {
					if ($code[2]=="MouseOut") {
						$this->finalResult.='if (substrEventId == "'.$code[0].'") {'.$code[1].'
						}';
					}
				}
			
			$this->finalResult.="
			}";
			}
		}
		$this->finalResult.="
		}";		
		if (is_array($this->afterMainContent)) {
			foreach ($this->afterMainContent as $id=>$content) {
				$this->finalResult.= $content;
			}
		}
		// Tom: f
		$this->finalResult.= "PassedTime = CurrentTime-lastTime; lastTime = CurrentTime;";
		$this->finalResult.="foreach(_timeoutkey=>_timeoutarray in _timeouts) {
				if(_timeoutkey <= Now) {
					for(k, 0, _timeoutarray.count - 1)
					{
						declare Text _timeoutFn = _timeoutarray[k];
						declare CMlControl _timeoutBinder = _timeoutBinders[_timeoutkey][k];
						switch(_timeoutFn){
							##timeoutSwitch##	
						}
					}
					_timeouts.removekey(_timeoutkey);
					_timeoutBinders.removekey(_timeoutkey);
				}
			}";
		if (is_array($this->loopEndContent)) {
			foreach ($this->loopEndContent as $id=>$content) {
				$this->finalResult.= $content;
			}
		}
		$this->finalResult.="yield;
		}}";
		// $this->finalResult=str_replace(array("\r","\n"),array("",""),$this->finalResult);
		// $this->finalResult = preg_replace('/\s+/', ' ', $this->finalResult);
		foreach ($this->replaces as $replacer) {
			$replace = $replacer[0];
			if (!$replacer[1])
				$this->finalResult = preg_replace($replace[0].'s', $replace[1], $this->finalResult);
			else
				$this->finalResult = str_replace($replace[0], $replace[1], $this->finalResult);
			// var_dump($replace[0], $replace[1]);
		}
		$this->timeoutSwitch();
		if (!$this->built) {
			$this->finalResult = $this->replaceInlineMQ($this->finalResult);
			$this->built = true;
			$this->finalResult = "";
			return $this->build($return);
		} else {
			if($return)
				return $this->finalResult;
			echo $this->finalResult;
		}
	
	}
	
	private function timeoutSwitch()
	{
		preg_match_all(
			'/setTimeout\(\"(.*?)\",(.*?),(.*?)\);/s',
			$this->finalResult,
			$timeoutFunctions,
			PREG_SET_ORDER
		);
		$fn = array();
		foreach ($timeoutFunctions as $timeout) {
			$name = $timeout[1];
			if(!in_array($name, $fn)) $fn[] = $name;
		}
		$switchcases = "";
		foreach ($fn as $f) {
			$switchcases .= 'case "'.$f.'":{'.$f.'(_timeoutBinder);}';
		}
		$this->finalResult = str_replace("##timeoutSwitch##", $switchcases, $this->finalResult);
	}
}

function strallpos($haystack, $needle, $offset = 0){
    $result = array();
    for($i = $offset; $i<strlen($haystack); $i++){
        $pos = strpos($haystack,$needle,$i);
        if($pos !== FALSE){
            $offset =  $pos;
            if($offset >= $i){
                $i = $offset;
                $result[] = $offset;
            }
        }
    }
    return $result;
} 
?>