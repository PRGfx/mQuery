<?php
function mq_slide($handler, $manialinkElement, $options = false)
{
	function number($x) {
		if(!preg_match('/\./', $x))
			$x.=".0";
		return $x;
	}
	$uses = $handler->getUses("mq_slide");
	if(!$options)
		$options = new StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->autoplay))
		$options->autoplay = true;
	if(!isset($options->speed))
		$options->speed = "fast";
	$speeds = array("fast"=>1, "middle"=>10, "slow"=>20);
		$options->speed = $speeds[$options->speed] * 1;
	if(!isset($options->steps))
		$options->steps = 3;
	if(!isset($options->ox))
		$options->ox = -300;
	if(!isset($options->oy))
		$options->oy = 0;
	if(!isset($options->tx))
		$options->tx = ($manialinkElement->get("x")!=null)?$manialinkElement->get("x"):0;
	if(!isset($options->ty))
		$options->ty = ($manialinkElement->get("y")!=null)?$manialinkElement->get("y"):0;

	$ratio = 0;
	$dx = ($options->tx - $options->ox);
	$dy = ($options->ty - $options->oy);
	if ($dx == 0) {
		if ($dy == 0)
			return false;
		$options->stepsY = $options->steps;
	} elseif ($dy == 0) {
		$options->stepsY = 0;
	} else
		$ratio = $dy / $dx;
	$options->stepsX = $options->steps;
	if(!isset($options->stepsY))
		$options->stepsY = $options->stepsX * $ratio;
		// echo $dx .' | ' . $dy .' | ' .$ratio;
	// var_dump($options);

	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($manialinkElement);

	// should move
	$scriptHandler->declareGlobalVariable('Boolean', 'mqSlideShould'.$uses);
	$scriptHandler->declareMainVariable('Boolean', 'mqSlideShould'.$uses, true, ($options->autoplay)?"True":"False");

	// move function
	$fnname = 'mqSlideMove'.$uses;
	$fn = '
	declare Real xOrig = '.number($options->ox).';
	declare Real yOrig = '.number($options->oy).';
	declare Real xTarg = '.number($options->tx).';
	declare Real yTarg = '.number($options->ty).';
	declare Text yDire;
	if (yOrig < yTarg)
		yDire = "d";
	else 
		yDire = "u";
	declare Text xDire;
	if (xOrig < xTarg)
		xDire = "r";
	else 
		xDire = "l";
	'.$manialinkElement->getManiaScriptDeclare("e").'
	if(e.PosnX != xTarg && e.PosnY != yTarg) {
		e.PosnX+='.$options->stepsX.';
		e.PosnY+='.$options->stepsY.';
		if((xDire == "r" && e.PosnX > xTarg) || (xDire == "l" && e.PosnX < xTarg))
		{	e.PosnX = xTarg;	}
		if((yDire == "u" && e.PosnY < yTarg) || (yDire == "d" && e.PosnY > yTarg))
		{	e.PosnY = yTarg;	}
	} else {
		declare Boolean mqSlideShould'.$uses.' for Page;
		mqSlideShould'.$uses.' = False;
	}
	log(xDire ^ " - " ^ yDire);
	log(e.PosnX ^ " - " ^ e.PosnY ^ " - " ^ xTarg ^ " - " ^ yTarg ^ " - '.$options->stepsX.' - '.$options->stepsY.'");
	';
	$scriptHandler->addFunction('Void', $fnname, $fn);
	$scriptHandler->addCodeToMain($elementId.'.PosnX = '.number($options->ox).';'.$elementId.'.PosnY = '.number($options->oy).';');

	// add to loop
	$scriptHandler->addCodeToLoop('if(mqSlideShould'.$uses.'==True){ if(waitFor('.$options->speed.', "'.$fnname.'") == True){'.$fnname.'();}}');

}
?>