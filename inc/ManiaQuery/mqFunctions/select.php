<?php
function mq_select($handler, $manialinkElement, $options = false)
{
	$uses = $handler->getUses("mq_select");
	$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->default)) {
		if($manialinkElement->get("default") != null)
			$options->default = $manialinkElement->get("default");
		else
			$options->default = "";
	}
	if(!isset($options->icon))
		$options->icon = true;
	$scriptHandler = $handler->scriptHandler();
	$entryId = "mqSelectEntry".$uses;
	$labelId = "mqSelectLabel".$uses;
	if($manialinkElement->type == "entry") {
		if($manialinkElement->get("name") != null)
			$options->name = $manialinkElement->get("name");
		if(isset($options->name))
			$manialinkElement->set("name", $options->name);
		$label = new \ManialinkAnalysis\ManialinkElement("<label />");
		$label->set("posn", $manialinkElement->get("posn"))->set("sizen", $manialinkElement->get("sizen"))->set("style", $manialinkElement->get("style"))->set("textcolor", $manialinkElement->get("textcolor"))->set("text", $options->default)->set("id", $labelId)->set("class", "mqSelectLabel");
		$manialinkElement->set("hidden", "1");
		if($manialinkElement->get("id") != null)
			$entryId = $manialinkElement->get("id");
		else
			$manialinkElement->set("id", $entryId);
		$manialinkElement->append($label->toString());
	}elseif($manialinkElement->type=="label") {
		// entry
		$entry = new \ManialinkAnalysis\ManialinkElement("<entry />");
		$entry->set("posn", $manialinkElement->get("posn"))->set("sizen", $manialinkElement->get("sizen"))->set("style", $manialinkElement->get("style"))->set("textcolor", $manialinkElement->get("textcolor"))->set("default", $options->default);
		if($manialinkElement->get("name") != null)
			$options->name = $manialinkElement->get("name");
		if(isset($options->name))
			$entry->set("name", $options->name);
		$entry->set("hidden", "1")->set("id", $entryId);
		// label modifications
		$manialinkElement->set("name","")->set("class", "mqSelectLabel");
		$manialinkElement->set("text", $options->default);
		if($manialinkElement->get("id") != null)
			$labelId = $manialinkElement->get("id");
		else
			$manialinkElement->set("id", $labelId);
		$manialinkElement->append($entry->toString());
	}
	$overlay = new \ManialinkAnalysis\ManialinkElement("<quad />");
	$overlay->set("bgcolor", "0000")->set("posn", $manialinkElement->get("posn"))->set("z", $manialinkElement->get("z")+1)->set("sizen", $manialinkElement->get("sizen"))->set("valign", $manialinkElement->get("valign"))->set("halign", $manialinkElement->get("halign"))->set("id", "mqSelectToggler".$uses)->set("scriptevents", "1");
	$manialinkElement->append($overlay->toString());

	$width = $manialinkElement->get("width");
	$corners = $manialinkElement->getCorners();
	$data = json_decode($options->data);
	// var_dump($data);
	$append = "";
	if($options->icon == true)
	{
		$append.= '<quad class="mqSelectIcon" id="mqSelectIcon'.$uses.'" posn="'.$corners[1][0].' '.$corners[1][1].' '.$manialinkElement->get("z").'" scriptevents="1" />';
	}
		$y = $corners[2][1]-1;
		$bgHeight = count($data) * 4 + 1;
		if($y - $bgHeight < -90)
			$y = $corners[0][1] + $bgHeight + 3;
		$append.= '<frame id="mqSelectOptions'.$uses.'" posn="'.$corners[0][0].' '.$y.' 60" hidden="1" >
		<quad posn="0 0 0" class="mqSelectBg" sizen="'.$width.' '.$bgHeight.'" action="1" />';
		$n = 0;
		foreach ($data as $k => $value) {
			$id = 'mqSelect'.$uses.'Option'.$n;
			$append.= '<label text="'.$value.'" sizen="'.($width-2).' 2" class="mqSelectOption" posn="1 '.(-$n*4-1).' 1" id="'.$id.'" scriptevents="1" />';
			if(isset($options->onSelect))
				$scriptHandler->addMouseClickEvent($id, 'mqSelectHide(); mqSelectInsert'.$uses.'("'.$uses.'", "'.$value.'", "'.$entryId.'", "'.$labelId.'");');
			else
				$scriptHandler->addMouseClickEvent($id, 'mqSelectHide(); mqSelectInsert("'.$uses.'", "'.$value.'", "'.$entryId.'", "'.$labelId.'");');
			$n++;
		}
		$append.= '</frame>';
		if($uses == 1)
			$append.= '<quad bgcolor="0000" posn="-160 90 59" sizen="320 180" id="mqSelectOverlay" scriptevents="1" hidden="1" />';
		$manialinkElement->append($append);
	if($uses == 1)
	{
		$scriptHandler->declareGlobalVariable("Text", "mqSelectCurrent");
		$scriptHandler->addMouseClickEvent("mqSelectOverlay", 'mqSelectHide();');
		// mqSelectShow(Text uses)
		$fn = '
		mqSelectCurrent = uses;
		declare CMlQuad selectOverlay <=> (Page.GetFirstChild("mqSelectOverlay") as CMlQuad);
		selectOverlay.Show();
		declare CMlFrame selectFrame <=> (Page.GetFirstChild("mqSelectOptions" ^ uses) as CMlFrame);
		selectFrame.Show();
		';
		$scriptHandler->addFunction("Void", "mqSelectShow", $fn, "Text uses");
		// mqSelectHide()
		$fn = '
		declare Text uses = mqSelectCurrent;
		mqSelectCurrent = "";
		declare CMlQuad selectOverlay <=> (Page.GetFirstChild("mqSelectOverlay") as CMlQuad);
		selectOverlay.Hide();
		declare CMlFrame selectFrame <=> (Page.GetFirstChild("mqSelectOptions" ^ uses) as CMlFrame);
		selectFrame.Hide();
		';
		$scriptHandler->addFunction("Void", "mqSelectHide", $fn);
		// mqSelectInsert(Text uses, Text value, Text entry, Text label)
		$fn = '
		declare CMlEntry selectEntry <=> (Page.GetFirstChild(entry) as CMlEntry);
		selectEntry.Value = value;
		declare CMlLabel selectLabel <=> (Page.GetFirstChild(label) as CMlLabel);
		selectLabel.SetText(value);
		';
		$scriptHandler->addFunction("Void", "mqSelectInsert", $fn, "Text uses, Text value, Text entry, Text label");
	}
	if(isset($options->onSelect)) {
		preg_match('/^function(\s+)?\((\w+)?\)(\s+)?\{(.*)\}$/si', trim($options->onSelect), $callback);
		$body = $callback[4];
		if(!empty($callback[2])) {
			$var = $callback[2];
			if($var!="value")
				$body = "declare Text ".$var." = value;".$body;
		}
		// $body = "";
		$fn = '
		declare CMlEntry selectEntry <=> (Page.GetFirstChild(entry) as CMlEntry);
		selectEntry.Value = value;
		declare CMlLabel selectLabel <=> (Page.GetFirstChild(label) as CMlLabel);
		selectLabel.SetText(value);
		' . $body;
		$scriptHandler->addFunction("Void", "mqSelectInsert".$uses, $fn, "Text uses, Text value, Text entry, Text label");
	}
	$scriptHandler->addMouseClickEvent('mqSelectToggler'.$uses, 'mqSelectShow("'.$uses.'");');
	$scriptHandler->addMouseClickEvent('mqSelectIcon'.$uses, 'mqSelectShow("'.$uses.'");');
		
	$handler->updateElement($manialinkElement);
}
?>