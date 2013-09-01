<?php

namespace ManiaQuery;

require_once 'Maniascript.php';
require_once 'ManiascriptFunction.php';

/**
 *
 */
class ManiascriptHandler extends Maniascript
{

	private $declareCounter = 0;

	private $manialinkAnalizer;
	private $declaredElements = array();

	private $constraints = array();

	/**
	 * Handles the Maniascript in the project.
	 *
	 * @param \ManialinkAnalysis\manialinkAnalizer $manialinkAnalizer manialinkAnalizer of the project.
	*/
	public function __construct(\ManialinkAnalysis\manialinkAnalizer $manialinkAnalizer)
	{
		$this->manialinkAnalizer = $manialinkAnalizer;
		$this->addCodeBeforeMain(
			'declare Text[Integer][Integer] _timeouts;
			declare CMlControl[Integer][Integer] _timeoutBinders;
			
			Void setTimeout(Text function, Integer offset, CMlControl binder)
			{
				declare Integer key = Now + offset;
				declare Integer index = 0;
				if(_timeouts.existskey(key)) index = _timeouts[key].count;
				else { _timeouts[key] = Text[Integer]; _timeoutBinders[key] = CMlControl[Integer]; } 
				_timeouts[key][index] = function;
				_timeoutBinders[key][index] = binder;
			}
			
			Void setTimeout(Text function, Integer offset)
			{
				setTimeout(function, offset, Null);
			}'
		);
		$functions = glob("./inc/ManiaQuery/msFunctions/*");
		foreach ($functions as $function) {
			if(substr($function, -3) == "php")
				$fn = ManiascriptHandler::renderPhpToString($function);
			elseif (substr($function, -3) == "txt")
			$fn = file_get_contents($function);
			else
				$fn = "";
			$this->addCodeBeforeMain($fn);
		}
		$this->setConstraints(array("Include"=>array(array('"MathLib"', 'MathLib'), array('"TextLib"', 'TextLib'))));
	}

	/**
	 * Adds the scriptevents attribute, removes manialink, action and url from the given manialinkElement,
	 * if necessary adds a generated id for the element, registers the CMlControl and returns the controlId.
	 * @param \ManialinkAnalysis\ManialinkElement $manialinkElement
	 * @param bool $scriptevents
	 * @return string controlId
	 */
	public function addListener(\ManialinkAnalysis\ManialinkElement $manialinkElement, $scriptevents = true)
	{
		$this->declareCounter++;
		$elementId = "mlE" . $this->declareCounter;
		if($scriptevents)
			$manialinkElement->set("scriptevents", "1")->set("manialink", "")->set("url", "")->set("action", "");
		if($manialinkElement->get("id") === null)
			$manialinkElement->set("id", $elementId);
		else
			$elementId = $manialinkElement->get("id");
		$manialinkElement->maniascriptId = $elementId;
		if (!array_key_exists($manialinkElement->get("id"), $this->declaredElements)) {
			$this->addCodeToMain($manialinkElement->getManiaScriptDeclare($elementId));
			$this->declaredElements[$manialinkElement->get("id")] = $elementId;
		} else {
			$elementId = $this->declaredElements[$manialinkElement->get("id")];
		}
		$this->manialinkAnalizer->updateElement($manialinkElement);
		return $elementId;
	}

	/**
	 * adds constraits to render later on
	 * @param {array} $constraints [ConstraitType=>[[parameter, parameter]]]
	 */
	public function setConstraints($constraints)
	{
		$this->constraints = array_merge_recursive($this->constraints, $constraints);
	}

	/**
	 * Renders all given constraits, includes custom libraries
	 * @return {string} script tag containing the includes
	 */
	public function buildConstraints()
	{
		$included = array();
		$consts = array();
		$contexts = array();
		$settings = array();
		$stack = array("Include"=>array("included", 1, " as "),
					   "Const"=>array("consts", 1, " "),
					   "RequiredContext"=>array("contexts", 0, false),
					   "Setting"=>array("settings", 1, " "));
		$result = "";
		$includes = "";
		foreach ($this->constraints as $type => $constraint) {
			$array = $$stack[$type][0];
			foreach ($constraint as $values) {
				if (!array_key_exists($values[$stack[$type][1]], $array)) {
					$array[$values[$stack[$type][1]]] = true;
					if ($type == "Include" && !in_array(substr($values[0], 1, -1), array("MathLib", "TextLib", "MapUnits"))) {
						$includes.='<include url="./inc/ManiaQuery/msLib/incLib.php?path='.substr($values[0], 1, -1).'" />';
						$libname = basename(substr($values[0], 1, -1));
						$this->addReplace(array("/" . $values[1] . "::/", $libname . "_"));
					} else {
					$result .= "#" . $type . " " . 
								($stack[$type][2] ? implode($stack[$type][2], $values) : $values) . 
								" " . PHP_EOL;
					}
				}
			}
		}
		if (!empty($result)) $result = '<script><!--' . $result . '--></script>';
		return $result . $includes;
	}

	/**
	 * Renders a php file and returns the output.
	 *
	 * @param string $file The file to render.
	 * @param array $vars The variables the file needs to render.
	 *
	 * @return string The rendered output.
	 */
	public static function renderPhpToString($file, array $vars=NULL)
	{
		if (is_array($vars) && !empty($vars)) {
			extract($vars);
		}
		ob_start();
		include $file;
		return ob_get_clean();
	}
}
?>