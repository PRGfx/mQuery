<?php
function mq_ui_checkbox($handler, $manialinkElement, $options = false)
{
	if($manialinkElement->type != "entry")
		return false;

	$scriptHandler = $handler->scriptHandler();
	$uses = $handler->getUses("mq_ui_checkbox");
	if(!$options)
		$options = new StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->classChecked))
		$options->classChecked = "mq_ui_checkbox_checked";
	if(!isset($options->classUnchecked))
		$options->classUnchecked = "mq_ui_checkbox_unchecked";
	if(!isset($options->values))
		$options->values = "[1, 0]";
	$options->values = json_decode($options->values);
	if(!isset($options->checked))
		$options->checked = false;
	if(isset($options->uses))
		$uses = $options->uses;

	$manialinkElement->set("default", $options->values[(int) !$options->checked])->set("hidden", "1");

	$cbChecked = new \ManialinkAnalysis\ManialinkElement('<quad id="mqCheckboxC'.$uses.'" />');
	$cbChecked->set("class", $options->classChecked)->set("posn", $manialinkElement->get("posn"))->set("hidden", (int) !$options->checked);
	
	$cbUnchecked = new \ManialinkAnalysis\ManialinkElement('<quad id="mqCheckboxU'.$uses.'" />');
	$cbUnchecked->set("class", $options->classUnchecked)->set("posn", $manialinkElement->get("posn"))->set("hidden", (int) $options->checked);

	$entryId = $scriptHandler->addListener($manialinkElement, false);
	$checkedId = $scriptHandler->addListener($cbChecked);
	$uncheckedId = $scriptHandler->addListener($cbUnchecked);

	$handler->append($cbChecked->toString() . $cbUnchecked->toString(), $manialinkElement->get("id"));
	$handler->updateElement($manialinkElement);


	$fnName = "mqCheckBox".$uses;

	$fnContent = '
	declare CMlQuad bC <=> (Page.GetFirstChild("mqCheckboxC'.$uses.'") as CMlQuad);
	declare CMlQuad bU <=> (Page.GetFirstChild("mqCheckboxU'.$uses.'") as CMlQuad);
	declare CMlEntry entry <=> (Page.GetFirstChild("'.$manialinkElement->get("id").'") as CMlEntry);
	declare Text newValue;
	if(checked == True) {
		bU.Hide();
		bC.Show();
		newValue = "'.$options->values[0].'";
	} else {
		bC.Hide();
		bU.Show();
		newValue = "'.$options->values[1].'";
	}
	entry.Value = newValue;
	';
	if(isset($options->onChange)) {
		preg_match('/^function(\s+)?\((\w+)?\)(\s+)?\{(.*)\}$/si', trim($options->onChange), $callback);
		$body = $callback[4];
		if(!empty($callback[2])) {
			$body = preg_replace('/\b'.$callback[2].'\b/s', 'newValue', $body);
		}
		$fnContent.=$body;
	}
	$scriptHandler->addFunction("Void", $fnName, $fnContent, "Boolean checked");

	$scriptHandler->addMouseClickEvent('mqCheckboxC'.$uses, $fnName."(False);");
	$scriptHandler->addMouseClickEvent('mqCheckboxU'.$uses, $fnName."(True);");

}

function mq_ui_switch($handler, $manialinkElement, $options = false)
{
	if($manialinkElement->type != "entry")
		return false;

	$scriptHandler = $handler->scriptHandler();
	if(!$options)
		$options = new StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(isset($options->classOn))
		$options->classChecked = $options->classOn;
	if(isset($options->classOff))
		$options->classUnchecked = $options->classOff;
	if(!isset($options->classChecked))
		$options->classChecked = "mq_ui_switch_on";
	if(!isset($options->classUnchecked))
		$options->classUnchecked = "mq_ui_switch_off";
	if(!isset($options->values))
		$options->values = '["enabled", "disabled"]';
	if(!isset($options->checked))
		$options->checked = false;
	$options->uses = $handler->getUses("mq_ui_switch") + 50;
	$options = \ManiaQuery\ManiaQuery::obj2str($options);
	// echo $options;
	mq_ui_checkbox($handler, $manialinkElement, $options);
}
?>