<?php
namespace ManiaQuery;

/**
 * Handles ManiaScript.
 * Originally made for Boxes.
 *
 * @author Blade
 * @version 03/26/2012
 */
class Maniascript
{
	public $globalVariables = array();
	public $functions = array();
	public $mainVariables = array();
	public $mainContent = array();
	public $loopContent = array();
	public $keyPressEvents = array();
	public $mouseClickEvents = array();
	public $mouseOverEvents = array();
	public $mouseOutEvents = array();
	public $subEvents = array();
	public $afterMainContent = array();
	public $finalResult = "";
	
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
		}else{
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
	 * @throws Exception if there already is a function with this name.
	 */
	public function addFunction($dataType, $name, $content, $parameter="")
	{
		global $alert;
		$arrayTest = is_in_array($this->functions, $name);
		if ($arrayTest) {
			$this->functions[$name] = array(ucfirst($dataType),$content,$parameter); 
		}else{
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
	 * @param boolean $global States wether or not the variable should be made global by declaring it for the Page object.
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
		}else{
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
	 * Puts everything together.
	 *
	 * @param boolean $return Decide if this function directly outputs the ManiaScript or just returns it.
	 * @return If called this way, it will return the ManiaScript.
	 */
	public function build($return = true)
	{
		
		$this->finalResult.='#Include "TextLib" as TextLib ' . "\n";
		$this->finalResult.='#Include "MathLib" as MathLib ' . "\n";
		if (is_array($this->globalVariables)){
			foreach ($this->globalVariables as $name=>$dataType){
				$this->finalResult.="declare ".$dataType." ".$name.";\n";
			}
			$this->finalResult.=' ';
		}
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
			if(!sleeps.existskey(Name)){
				sleeps[Name] = CurrentTime;
			}
			if(CurrentTime-sleeps[Name] >= Time){
				sleeps[Name] = CurrentTime;
				return True;
			}
			
			return False;
		}
		
		';
		if (is_array($this->functions)){
			foreach ($this->functions as $name=>$content){
				$this->finalResult.="".$content[0]." ".$name."(".$content[2]."){".$content[1]."}";
			}
		}
		
		$this->finalResult.="main(){";
		
		if (is_array($this->mainVariables)){
			foreach ($this->mainVariables as $name=>$content){
				if(!preg_match('/^CMl/', $content[0]))
					$this->finalResult.="declare " . $content[0] . " " . $name .  ($content[1]?" for Page ":"") . ($content[2]?" = " . $content[2]:"") . ";";
				else
					$this->finalResult.="declare " . $content[0] . " " . $name .  ($content[1]?" for Page ":"") . ($content[2]?" <=> (Page.GetFirstChild(\"".$content[2]."\") as " . $content[0] . ")":"") . ";";
			}
		}
		
		if (is_array($this->mainContent)){
			foreach ($this->mainContent as $id=>$content){
				$this->finalResult.= $content;
			}
		}
		// Tom: f
		$this->finalResult.= "declare PassedTime for Page = 0; declare lastTime = CurrentTime;\n";
		$this->finalResult.='
		while(True){
		';
		if (is_array($this->loopContent)){
			foreach ($this->loopContent as $id=>$content){
				$this->finalResult.= $content;
			}
				
		}
		$this->finalResult.='declare substrEventId = "";
			declare substrEventIdRest = "";
			declare Integer substrEbentIdRestLenght;
			foreach(Event in PendingEvents){
				substrEventId = TextLib::SubString(Event.ControlId^"", 0, 5);
				substrEbentIdRestLenght = strlen(Event.ControlId^"");
				substrEventIdRest = TextLib::SubString(Event.ControlId^"", 5, substrEbentIdRestLenght);';
				
		if (is_array($this->keyPressEvents) && !empty($this->keyPressEvents)){
			$this->finalResult.="if(Event.Type == CMlEvent::Type::KeyPress){";
				foreach ($this->keyPressEvents as $id=>$code){
					$this->finalResult.='if (Event.CharPressed == "'.$code[0].'"){'.$code[1].'
					}';
				}
			$this->finalResult.="
			}";
		}
		
		if (is_array($this->mouseClickEvents) or is_array($this->subEvents)){
			$this->finalResult.='if(Event.Type == CMlEvent::Type::MouseClick){';
			if (is_array($this->mouseClickEvents)){
				foreach ($this->mouseClickEvents as $id=>$code){
					$this->finalResult.='if (Event.ControlId == "'.$code[0].'"){'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)){
				foreach ($this->subEvents as $subId=>$code){
					if ($code[2]=="MouseClick"){
						$this->finalResult.='if (substrEventId == "'.$code[0].'"){'.$code[1].'
						}';
					}
				}
			$this->finalResult.="
			}";
			}
		}
		
		if (is_array($this->mouseOverEvents) or is_array($this->subEvents)){
			$this->finalResult.="if(Event.Type == CMlEvent::Type::MouseOver){";	
			if (is_array($this->mouseOverEvents)){
				foreach ($this->mouseOverEvents as $id=>$code){
					$this->finalResult.='if (Event.ControlId == "'.$code[0].'"){'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)){
				foreach ($this->subEvents as $subId=>$code){
					if ($code[2]=="MouseOver"){
						$this->finalResult.='if (substrEventId == "'.$code[0].'"){'.$code[1].'
						}';
					}
				}
			$this->finalResult.="
			}";
			}
		}
		
		if (is_array($this->mouseOutEvents) or is_array($this->subEvents)){
			$this->finalResult.="if(Event.Type == CMlEvent::Type::MouseOut){";
			if (is_array($this->mouseOutEvents)){
				foreach ($this->mouseOutEvents as $id=>$code){
					$this->finalResult.='if (Event.ControlId == "'.$code[0].'"){'.$code[1].'
					}';
				}
			}
			if (is_array($this->subEvents)){
				foreach ($this->subEvents as $subId=>$code){
					if ($code[2]=="MouseOut"){
						$this->finalResult.='if (substrEventId == "'.$code[0].'"){'.$code[1].'
						}';
					}
				}
			
			$this->finalResult.="
			}";
			}
		}
		$this->finalResult.="
		}";		
		if (is_array($this->afterMainContent)){
			foreach ($this->afterMainContent as $id=>$content){
				$this->finalResult.= $content;
			}
		}
		// Tom: f
		$this->finalResult.= "PassedTime = CurrentTime-lastTime; lastTime = CurrentTime;";
		$this->finalResult.="yield;
		}}";
	$this->finalResult=str_replace(array("\r","\n"),array("",""),$this->finalResult);
	$this->finalResult = preg_replace('/\s+/', ' ', $this->finalResult);
	if($return)
		return $this->finalResult;
	echo $this->finalResult;
	
	}
}
?>