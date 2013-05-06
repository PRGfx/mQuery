<?php
function mq_pointOfMouse($handler, $ManialinkElement, $options = false)
{
	$uses = $handler->getUses("mq_pointOfMouse");
	if(!$options)
		$options = new StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->x))
		$options->x = 0.1;
	if(!isset($options->y))
		$options->y = 0.2;
	if(!isset($options->mirror))
		$options->mirror = "true";
	($options->mirror!="true")?$rc='+':$rc='-';
	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($ManialinkElement, false);
	list($x, $y) = explode(' ', $ManialinkElement->get("posn"));
	preg_match('/\./', $x)?$x=$x:$x.='.0';
	preg_match('/\./', $y)?$y=$y:$y.='.0';
	$scriptHandler->declareMainVariable("Real", "mqPOM_".$uses."_x", false, $x);
	$scriptHandler->declareMainVariable("Real", "mqPOM_".$uses."_y", false, $y);
	$scriptHandler->addCodeToLoop('
		'.$elementId.'.PosnX = mqPOM_'.$uses.'_x '.$rc.' MouseX * '.$options->x.';
		'.$elementId.'.PosnY = mqPOM_'.$uses.'_y '.$rc.' MouseY * '.$options->y.';
		');
}
?>