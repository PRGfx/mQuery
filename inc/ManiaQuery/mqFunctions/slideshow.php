<?php
function mq_slideshow($handler, $manialinkElement, $options = false)
{
	$uses = $handler->getUses("mq_slideshow");
	if (!$options) {
		$options = new \stdClass();
	} else {
		$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	}
	if (isset($options->data)) {
		if(!isset($options->speed))
			$options->speed = 10;
		if(!isset($options->time))
			$options->time = 30;
		if(!isset($options->autoplay))
			$options->autoplay = "True";
		else
			$options->autoplay = ($options->autoplay==true)?"True":"False";
		if(!isset($options->accelerate))
			$options->accelerate = "True";
		else
			$options->accelerate = ($options->accelerate==true)?"True":"False";
		$options->data = json_decode($options->data);
		$scriptHandler = $handler->scriptHandler();
		$im1 = new \ManialinkAnalysis\ManialinkElement('<quad />');
		$im1->set("id", "mqSlideshow_".$uses."_1")->set("image", $options->data[0])
			->set("sizen", $manialinkElement->get("sizen"))->set("posn", $manialinkElement->get("posn"));
		$eid1 = $scriptHandler->addListener($im1);
		$im2 = new \ManialinkAnalysis\ManialinkElement('<quad />');
		$im2->set("id", "mqSlideshow_".$uses."_2")->set("image", $options->data[1])
			->set("sizen", $manialinkElement->get("sizen"))->set("posn", $manialinkElement->get("posn"));
		$eid2 = $scriptHandler->addListener($im2);
		$manialinkElement->set("hidden", "1")->append($im1->toString() . $im2->toString());
		$handler->updateElement($manialinkElement);
		$binder = $scriptHandler->addListener($manialinkElement, false);
		
		$onChange = "";
		if (isset($options->onChange)) {
			preg_match('/^function(\s+)?\((\w+)?\)(\s+)?\{(.*)\}$/si', trim($options->onChange), $callback);
			$body = $callback[4];
			if (!empty($callback[2])) {
				$var = $callback[2];
				if($var!="index")
					$body = "declare Integer ".$var." = index;".$body;
			}
			$onChange = $body;
		}
		$scriptHandler->addCodeToMain(
			'/* slideshow '.$uses.'*/
			declare Text[] _mqslideshow'.$uses.'_data for '.$binder.' = '.stripslashes(json_encode($options->data)).';
			declare Integer _mqslideshow'.$uses.'_runs for '.$binder.'= 0;
			declare Boolean _mqslideshow'.$uses.'_autoplay for '.$binder.'= '.$options->autoplay.';
			declare Boolean _mqslideshow_accelerate for '.$binder.'= '.$options->accelerate.';
			declare Integer _slideshowAniSpeed for '.$binder.' = '.$options->speed.';
			declare Real _slideshowAniWidth for '.$binder.' = '.$binder.'.Size[0];
			declare Real _slideshowAniHeight for '.$binder.' = '.$binder.'.Size[1];
			declare CMlControl _slideshowAniE1 for '.$binder.' = '.$eid1.';
			declare CMlControl _slideshowAniE2 for '.$binder.' = '.$eid2.';
			if(_mqslideshow'.$uses.'_autoplay)
			_mqSlideshowSlideNext'.$uses.'('.$binder.');
			/* end slideshow '.$uses.' */'
		);
		$scriptHandler->addFunction(
			"Void",
			"_mqSlideshowSlide".$uses, 
			"declare Integer _slideshowAniSpeed for binder;
			declare Real _slideshowAniWidth for binder;
			declare Real _slideshowAniHeight for binder;
			declare Integer _mqslideshow".$uses."_runs for binder;
			declare Text[] _mqslideshow".$uses."_data for binder;
			declare CMlControl _slideshowAniE1 for binder;
			declare CMlControl _slideshowAniE2 for binder;
			declare Boolean _mqslideshow_accelerate for binder;
			_slideshowAniE2.PosnX=_slideshowAniE1.PosnX+_slideshowAniE1.Size[0];
			_slideshowAniE2.Size[0] = 0.;
			(_slideshowAniE2 as CMlQuad).ImageUrl = _mqslideshow".$uses.
			"_data[(_mqslideshow".$uses."_runs + 1) % _mqslideshow".
			$uses."_data.count];
			Resize(_slideshowAniE1, <0., _slideshowAniHeight>, ".$options->speed.", _mqslideshow_accelerate);
			Resize(_slideshowAniE2, <_slideshowAniWidth, _slideshowAniHeight>, ".$options->speed.", _mqslideshow_accelerate);
			Slide(_slideshowAniE2, <_slideshowAniE1.PosnX, _slideshowAniE1.PosnY>, ".$options->speed.", _mqslideshow_accelerate);
				_mqslideshow".$uses."_runs += 1;
			",
			"CMlControl binder"
		);
		$scriptHandler->addFunction(
			"Void",
			"_mqSlideshowSlideNext".$uses,
			"declare CMlControl _slideshowAniE1 for binder;
			declare CMlControl _slideshowAniE2 for binder;
			declare Integer _mqslideshow".$uses."_runs for binder;
			declare Text[] _mqslideshow".$uses."_data for binder;
			declare Boolean _mqslideshow".$uses."_autoplay for binder;
			declare Integer index = (_mqslideshow".$uses.
			"_runs + 1) % _mqslideshow".$uses."_data.count;
			declare CMlControl copy = _slideshowAniE1;
			_slideshowAniE1.PosnX = binder.PosnX;
			_slideshowAniE1 = _slideshowAniE2;
			_slideshowAniE2 = copy;
			_mqSlideshowSlide".$uses."(binder);
			".$onChange."
			if(_mqslideshow".$uses."_autoplay)
			setTimeout(\"_mqSlideshowSlideNext".$uses."\", ".
			($options->time + $options->speed + 1).", binder);
			",
			"CMlControl binder"
		);
		
	}
}