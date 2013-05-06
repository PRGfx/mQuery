<?php
function mq_click($handler, $manialinkElement, $code)
{
	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($manialinkElement);
	$code = preg_replace('/\$this/', $elementId, $code);
	$scriptHandler->addMouseClickEvent($manialinkElement->get("id"), $code);
	$handler->updateElement($manialinkElement);
}

function mq_hover($handler, $manialinkElement, $in, $out = false)
{
	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($manialinkElement);
	$in = preg_replace('/\$this/', $elementId, $in);
	$out = preg_replace('/\$this/', $elementId, $out);
	$scriptHandler->addMouseOverEvent($manialinkElement->get("id"), $in);
	if ($out !== false)
		$scriptHandler->addMouseOutEvent($manialinkElement->get("id"), $out);
	$handler->updateElement($manialinkElement);
	return $elementId;
}

function mq_mouseover($handler, $manialinkElement, $in)
{
	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($manialinkElement);
	$in = preg_replace('/\$this/', $elementId, $in);
	$scriptHandler->addMouseOverEvent($manialinkElement->get("id"), $in);
	$handler->updateElement($manialinkElement);
	return $elementId;
}

function mq_mouseout($handler, $manialinkElement, $out)
{
	$scriptHandler = $handler->scriptHandler();
	$elementId = $scriptHandler->addListener($manialinkElement);
	$out = preg_replace('/\$this/', $elementId, $out);
	$scriptHandler->addMouseOutEvent($manialinkElement->get("id"), $out);
	$handler->updateElement($manialinkElement);
	return $elementId;
}

function mq_leave($handler, $manialinkElement, $out)
{
	$scriptHandler = $handler->scriptHandler();
	$var = "mouseover" . $manialinkElement->get("id");
	$elementId = mq_mouseover($handler, $manialinkElement, $var . " = True;");
	$corners = $manialinkElement->getCorners();
	$out = preg_replace('/\$this/', $elementId, $out);
	$out = "if(".$var."==True && (MouseX<".$corners[0][0]." || MouseX>".$corners[1][0].") && (MouseY<".$corners[2][1]." || MouseY>".$corners[0][1].")) {\n" . $var . " = False; " . $out . "}";
	$scriptHandler->addCodeToLoop($out);
	$scriptHandler->addCodeToMain("declare Boolean " . $var . " = False;");
	$handler->updateElement($manialinkElement);
}

?>