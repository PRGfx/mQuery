<?php
function mq_tooltip($handler, $manialinkElement, $options = false)
{
	$uses = $handler->getUses("mq_tooltip");
	if (preg_match('/^(\"|\')/', $options, $del)) {
		$text = preg_replace('/^'.$del[1].'(.*)'.$del[1].';/', '\1', $options);
		$options = new \StdClass();
		$options->text = $text;
	} else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->background))
		$options->background = "true";
	$options->background = !($options->background == "false");
	if(!isset($options->class))
		$options->class = "mqTooltipText";
	if(!isset($options->bgclass))
		$options->bgclass = "mqTooltipBg";
	if(!isset($options->sizen))
		$options->sizen = "15 4";
	if(!isset($options->close))
		$options->close = "mouseleave";
	if(!isset($options->position))
		$options->position = "relative";
	if(isset($options->textid) && isset($options->text))
		unset($options->text);
	$scriptHandler = $handler->scriptHandler();
	$ttid = 'mqTooltip'.$uses;
	if(isset($options->id))
		$ttid = $options->id;

	list($width, $height) = explode(' ', $options->sizen.' 4');

	// display part
	$frame = '<frame '.(($options->position!="relative")?'posn="'.$options->position.'" ':'').'id="'.$ttid.'" >';
	if((bool) $options->background)
	$frame.= '<quad class="'.$options->bgclass.'" sizen="'.(++$width).' '.(++$height).'" posn="-0.5 0.5 0" />';
	$frame.= '<label class="'.$options->class.'" sizen="'.$width.' '.$height.'" posn="0 0 0"';
	if(isset($options->textid))
		$frame.= ' textid="'.$options->textid.'"';
	else
		$frame.= ' text="'.$options->text.'"';
	$frame.= '/>';
	$frame.= '</frame>';
	$handler->append($frame);

	// maniascript
	$elementId = $scriptHandler->addListener($manialinkElement);
	$scriptHandler->addCodeToMain(
		'declare CMlFrame mqTooltip'.$uses.' <=> (Page.GetFirstChild("mqTooltip'.$uses.'") as CMlFrame);
		mqTooltip'.$uses.'.PosnZ = 60.0;
		mqTooltip'.$uses.'.Hide();'
	);
	// mouseover
	$do = 'mqTooltip'.$uses.'.Show();';
	if($options->position=="relative")
		$do = 'mqTooltipPosn("mqTooltip'.$uses.'"); '.$do;
	$scriptHandler->addMouseOverEvent($manialinkElement->get("id"), $do);
	// mouseout
	$do = 'mqTooltip'.$uses.'.Hide();';
	$scriptHandler->addMouseOutEvent($manialinkElement->get("id"), $do);

	if ($uses == 1) {
		$fn = '
		declare CMlFrame ttf <=> (Page.GetFirstChild(id) as CMlFrame);
		if (MouseX > 145)
			ttf.PosnX = MouseX - 20;
		else
			ttf.PosnX = MouseX + 2;
		if (MouseY < -76)
			ttf.PosnY = MouseY + 10;
		else
			ttf.PosnY = MouseY - 5;
		';
		$scriptHandler->addFunction('Void', 'mqTooltipPosn', $fn, 'Text id');
	}
}
?>