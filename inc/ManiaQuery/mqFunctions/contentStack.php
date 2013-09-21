<?php
function mq_contentStack($handler, $manialinkElement, $options = false)
{
	// options
	if(!$options)
		$options = new \StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->start))
		$options->start = 0;
	if(!isset($options->play))
		$options->play = true;
	if(!isset($options->random))
		$options->random = false;
	if(!isset($options->ticks))
		$options->ticks = 3000;
	if(!isset($options->group))
		$options->group = "default";
	$scriptHandler = $handler->scriptHandler();
	$uses = $handler->getUses("mq_contentStack");
	
	if(isset($_POST["mqContentStackUses"]))
		$jsu = json_decode($_POST["mqContentStackUses"], true);
	else
		$jsu = array();
	$primUse = false;
	if(!array_key_exists($options->group, $jsu))
		$jsu[$options->group] = 0;
	$jsu[$options->group]++;
	if($jsu[$options->group] == 1)
		$primUse = true;
	$_POST["mqContentStackUses"] = json_encode($jsu);

	$uses = count($jsu);
	
	if(!isset($options->function))
		$options->function = "mqContentStackPerform".$uses;

	if(isset($_POST["mqContentStackTypes"]))
		$jst = json_decode($_POST["mqContentStackTypes"], true);
	else
		$jst = array();
	if(!isset($jst[$uses]))
		$jst[$uses] = $manialinkElement->getManiaScriptType();
	$_POST["mqContentStackTypes"] = json_encode($jst);

	// echo $manialinkElement->initial;

	$contentStack = 'mqContentStack'.$uses;
	$usesRand = 'mqContentStackUR'.$uses;
	$playPause = 'mqContentStackPlay'.$uses;
	$currentIndex = 'mqContentStackCurrent'.$uses;
	$type = $jst[$uses];
	$elementId = $scriptHandler->addListener($manialinkElement, false);
	if($primUse) {
		$type = $manialinkElement->getManiaScriptType();
		$scriptHandler->declareGlobalVariable('Boolean', $playPause);
		$scriptHandler->declareMainVariable('Boolean', $playPause, true, $options->play?"True":"False");
		$scriptHandler->declareGlobalVariable('Boolean', $usesRand);
		$scriptHandler->declareMainVariable('Boolean', $usesRand, true, ($options->random?"True":"False"));
		$scriptHandler->declareGlobalVariable('Integer', $currentIndex);
		$scriptHandler->declareMainVariable('Integer', $currentIndex, true, $options->start);
		// $scriptHandler->declareMainVariable($manialinkElement->getManiaScriptType() . '[Integer]', $contentStack, true, $elementId);
		
		// $scriptHandler->declareGlobalVariable($manialinkElement->getManiaScriptType() . '[Integer]', $contentStack);
		// $scriptHandler->addCodeToMain('declare '.$manialinkElement->getManiaScriptType() . '[Integer] ' . $contentStack .' for Page = [0=>'.$elementId.'];');
		
		$scriptHandler->declareGlobalVariable('CMlControl[Integer]', $contentStack);
		$scriptHandler->addCodeToMain('declare CMlControl[Integer] ' . $contentStack .' for Page = [0=>'.$elementId.'];');
	}
	// if($manialinkElement->getManiaScriptType() != $type) {
	// 	throw new \Exception("The assigned manialink elements are not of the same type! Expected was '".$type."'.", 1);
	// 	return false;
	// }
	if(!$primUse) {
		$n = $jsu[$options->group]-1;
		$scriptHandler->addCodeToMain('
			'.$elementId.'.Hide();
			'.$contentStack.'['.$n.'] = '.$elementId.';
		if('.$contentStack.'.existskey('.$currentIndex.'))
			'.$contentStack.'['.$currentIndex.'].Show();
		else
			'.$contentStack.'[0].Show();
			');
	}
	$scriptHandler->addCodeToMain('
			'.$elementId.'.Hide();
			');
	$body = "";
	if(isset($options->onChange)) {
		preg_match('/^function(\s+)?\((\w+)?\)(\s+)?\{(.*)\}$/si', trim($options->onChange), $callback);
		$body = $callback[4];
		if(!empty($callback[2])) {
			$body = preg_replace('/\b'.$callback[2].'\b/s', $currentIndex, $body);
		}
	}
	if($options->ticks > 0)
	$scriptHandler->addCodeToLoop('
		if(waitFor('.$options->ticks.', "mqContentStack'.$uses.'") && '.$playPause.' == True) {
			'.$options->function.'("next");
		}
		if(waitFor(20000, "mqContentStack'.$uses.'") && '.$playPause.' == False) {
			'.$playPause.' = True;
			'.$options->function.'("next");
		}
		');
	// $fn = 'declare '.$manialinkElement->getManiaScriptType() . '[Integer] ' . $contentStack .' for Page;
	$fn = 'declare CMlControl[Integer] ' . $contentStack .' for Page;
	declare Integer '.$currentIndex.' for Page;
	declare Boolean '.$usesRand.' for Page;
	declare Boolean '.$playPause.' for Page;
	if ('.$contentStack.'.existskey('.$currentIndex.')){
	'.$contentStack.'['.$currentIndex.'].Hide();}
	if (direction=="next") {
		if ('.$contentStack.'.existskey('.$currentIndex.' + 1)) {
			'.$currentIndex.'+=1;
		} else {
			'.$currentIndex.' = 0;
		}
	} else if (direction=="prev") {
		if ('.$contentStack.'.existskey('.$currentIndex.' - 1)) {
			'.$currentIndex.'-=1;
		} else {
			'.$currentIndex.' = '.$contentStack.'.count - 1;
		}
	} else if (direction=="pause") {
		if ('.$playPause.' == True) {
			'.$playPause.'=False;
		} else {
			'.$playPause.'=True;
		}
	} else {
		declare Integer key = TextLib::ToInteger(direction);
		if ('.$contentStack.'.existskey(key)) {
			'.$currentIndex.' = key;
		} else {
			'.$currentIndex.' = 0;
		}
	}
	if('.$usesRand.') {
		'.$currentIndex.' = MathLib::Rand(0, '.$contentStack.'.count - 1);
	}
	'.$contentStack.'['.$currentIndex.'].Show();
	' . $body;
	if($primUse)
	$scriptHandler->addFunction("Void", $options->function, $fn, "Text direction");
		
	$manialinkElement->set("hidden", "1");
	$handler->updateElement($manialinkElement);

}
?>