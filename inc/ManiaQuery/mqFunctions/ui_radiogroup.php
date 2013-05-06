<?php
function mq_ui_radiogroup($handler, $manialinkElement, $options = false)
{
	if($manialinkElement->type != "quad")
		return false;

	$scriptHandler = $handler->scriptHandler();
	$uses = $handler->getUses("mq_ui_radiogroup");

	if(!$options)
		$options = new StdClass();
	else
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->classChecked))
		$options->classChecked = "mq_ui_radiobutton_checked";
	if(!isset($options->classUnchecked))
		$options->classUnchecked = "mq_ui_radiobutton_unchecked";
	if(!isset($options->checked))
		$options->checked = false;
	if(isset($options->uses))
		$uses = $options->uses;
	if(!isset($options->group))
		$options->group = "default";
	if(!isset($options->name))
		$options->name = $options->group;

	if(isset($_POST["mqUiRadiogroups"]))
		$jsu = json_decode($_POST["mqUiRadiogroups"], true);
	else
		$jsu = array();
	$primUse = false;
	if(!array_key_exists($options->group, $jsu))
		$jsu[$options->group] = 0;
	$jsu[$options->group]++;
	if($jsu[$options->group] == 1)
		$primUse = true;
	$_POST["mqUiRadiogroups"] = json_encode($jsu);

	$uses = count($jsu);

	if(!isset($options->function))
		$options->function = "mqUiRadiogroupChange".$uses;

	$value = $manialinkElement->get("value");
	$manialinkElement->set("value", "");

	$manialinkElement2 = clone $manialinkElement;
	$manialinkElement->set("class", $options->classUnchecked);
	$manialinkElement2->set("class", $options->classChecked);
	$entry = "";

	if($primUse) {
		$manialinkElement->set("hidden", "1");
		$entry = new \ManialinkAnalysis\ManialinkElement('<entry id="mqRadiogroup'.$uses.'" />');
		$entry->set("name", $options->name)->set("default", $value)->set("hidden", "1");
	}else
		$manialinkElement2->set("hidden", "1");

	$eid2 = $scriptHandler->addListener($manialinkElement);
	$eid1 = $scriptHandler->addListener($manialinkElement2);

	$handler->append($manialinkElement2->toString(), $manialinkElement->get("id"));

	// var_dump($options);

	if($primUse) {
		$handler->append($entry->toString(), $manialinkElement2->get("id"));
		$scriptHandler->declareGlobalVariable("CMlQuad[]", "mqUiRadiogroups".$uses);
		$scriptHandler->declareMainVariable("CMlQuad[]", "mqUiRadiogroups".$uses, true);
		$scriptHandler->declareGlobalVariable("Text[]", "mqUiRadiogroupValues".$uses);
		$scriptHandler->declareMainVariable("Text[]", "mqUiRadiogroupValues".$uses, true);
		// $scriptHandler->declareGlobalVariable("Integer", "mqUiRadiogroupIndex".$uses);
		$scriptHandler->declareGlobalVariable("Integer", "mqUiRadiogroupIndex".$uses, true, 0);
		$additional = "";
		if(isset($options->onChange)) {
			preg_match('/^function(\s+)?\((\w+)?\)(\s+)?\{(.*)\}$/si', trim($options->onChange), $callback);
			$body = $callback[4];
			if(!empty($callback[2])) {
				$body = preg_replace('/\b'.$callback[2].'\b/s', 'newValue', $body);
			}
			$additional=$body;
		}
		$fnContent ='
			declare CMlQuad[] mqUiRadiogroups'.$uses.' for Page;
			declare Text[] mqUiRadiogroupValues'.$uses.' for Page;
			declare Integer mqUiRadiogroupIndex'.$uses.' for Page;
			declare CMlEntry entry <=> (Page.GetFirstChild("mqRadiogroup'.$uses.'") as CMlEntry);
			if(mqUiRadiogroupValues'.$uses.'.existskey(index)) {
				mqUiRadiogroups'.$uses.'[mqUiRadiogroupIndex'.$uses.' * 2].Hide();
				mqUiRadiogroups'.$uses.'[mqUiRadiogroupIndex'.$uses.' * 2+ 1].Show();
				mqUiRadiogroupIndex'.$uses.' = index;
				mqUiRadiogroups'.$uses.'[mqUiRadiogroupIndex'.$uses.' * 2].Show();
				declare Text newValue = mqUiRadiogroupValues'.$uses.'[mqUiRadiogroupIndex'.$uses.'];
				entry.Value = newValue;'.$additional.'
			}';
		$scriptHandler->addFunction("Void", $options->function, $fnContent, "Integer index");
	}
	$handler->updateElement($manialinkElement);

	$scriptHandler->addCodeToMain('
		mqUiRadiogroups'.$uses.'.add('.$eid1.');
		mqUiRadiogroups'.$uses.'.add('.$eid2.');
		mqUiRadiogroupValues'.$uses.'.add("'.$value.'");
		');

	$scriptHandler->addMouseClickEvent($eid1, $options->function.'('.($jsu[$options->group]-1).');');
	$scriptHandler->addMouseClickEvent($eid2, $options->function.'('.($jsu[$options->group]-1).');');

}

?>