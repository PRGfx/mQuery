<?php
function mq_in_get(\ManiaQuery\Maniascript $mScript, $url = null, $function = null) {
	$uses = $mScript->getInlineUses("get");
	if ($url == null)
		throw new \Exception("[Warning][mQuery] $.get() expects at least one argument.", 1);
	if ($function != null) {
		if (preg_match('/^function\s*\(\s*(.*?)\s*\)\s*\{(.*?)\};?$/is', $function, $fn)) {
			$name = $mScript->addFunction("Void", null, trim($fn[2]), "Text " . $fn[1], true);
			$func = $name;
		} else {
			$func = $call[2];
		}
	}
	$mScript->addCodeToMain('declare CHttpRequest ajaxReq'.$uses.';');
	$new = '
	if (Http.SlotsAvailable > 0)
	{
		declare Text ajaxUrl'.$uses.' = '.$url.' ^ "&" ^ Now;
		if (Http.IsValidUrl(ajaxUrl'.$uses.'))
		{
			ajaxReq'.$uses.' <=> Http.CreateGet( ajaxUrl'.$uses.' ) ;
		} else {
			log("[Error][HttpRequest] Invalid URL");
		}
	}';
	if (isset($func))
	$mScript->addCodeToLoopEnd('
		if (ajaxReq'.$uses.' != Null && ajaxReq'.$uses.'.IsCompleted) {
		if ("" ^ ajaxReq'.$uses.'.StatusCode == "12007") log("[Error][HttpRequest] Not Connected");
		if ("" ^ ajaxReq'.$uses.'.StatusCode == "404") log("[Error][HttpRequest] 404");
		if ("" ^ ajaxReq'.$uses.'.StatusCode == "200")
		{'.$func.'(ajaxReq'.$uses.'.Result);}
		Http.Destroy(ajaxReq'.$uses.');
		ajaxReq'.$uses.' = Null;  
		}
		');
	return $new;
}