<?php
namespace ManiaQuery;

require_once('Maniascript.php');
require_once('ManiascriptFunction.php');

/**
* 
*/
class ManiascriptHandler extends Maniascript
{
	
	private $declareCounter = 0;

	private $ManialinkAnalizer;
	private $declaredElements = array();

	public function __construct($ManialinkAnalizer) {
		$this->ManialinkAnalizer = $ManialinkAnalizer;
	}

	public function addListener($manialinkElement, $scriptevents = true) {
		$this->declareCounter++;
		$elementId = "mlE" . $this->declareCounter;
		if($scriptevents)
			$manialinkElement->set("scriptevents", "1")->set("manialink", "")->set("url", "")->set("action", "");
		if($manialinkElement->get("id") === null)
			$manialinkElement->set("id", $elementId);
		$manialinkElement->maniascriptId = $elementId;
		if(!array_key_exists($manialinkElement->get("id"), $this->declaredElements)) {
			$this->addCodeToMain($manialinkElement->getManiaScriptDeclare($elementId));
			$this->declaredElements[$manialinkElement->get("id")] = $elementId;
		} else {
			$elementId = $this->declaredElements[$manialinkElement->get("id")];
		}
		$this->ManialinkAnalizer->updateElement($manialinkElement);
		// echo $manialinkElement->getManiaScriptDeclare($elementId) .'<hr>';
		return $elementId;
	}

	public function hover($manialinkElement, $in, $out = false) {
		$elementId = $this->addListener($manialinkElement);
		if($manialinkElement->get("id") === null)
			$manialinkElement->set("id", $elementId);
		if ($in instanceof ManiascriptFunction) {
			$this->addFunction($in->getType(), $in->getName(), $in->getBody(), implode(', ', $in->getArguments()));
			$todo = $in->getName() . "()\n";
		} else
			$todo = $in;
		$this->addMouseOverEvent($manialinkElement->get("id"), $todo);
		if($out !== false) {
			if ($out instanceof ManiascriptFunction) {
				$this->addFunction($out->getType(), $out->getName(), $out->getBody(), implode(', ', $out->getArguments()));
				$todo = $out->getName() . "()\n";
			} else
				$todo = $out;
			$this->addMouseOutEvent($manialinkElement->get("id"), $todo);
		}
		$this->ManialinkAnalizer->updateElement($manialinkElement);
		return $this;
	}

	public function click($manialinkElement, $do) {
		if($manialinkElement->get("id") === null) {
			$elementId = $this->addListener($manialinkElement);
			$manialinkElement->set("id", $elementId);
		}
		$this->addMouseClickEvent($manialinkElement->get("id"), $do);
		return $this;
	}
}
?>