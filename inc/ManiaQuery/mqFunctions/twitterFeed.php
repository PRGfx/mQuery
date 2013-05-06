<?php
function mq_twitterFeed($handler, $manialinkElement, $options) {

	// options
	$options = \ManiaQuery\ManiaQuery::jsobj2php($options);
	if(!isset($options->src))
		$options->src = "@maniaplanet";
	if(!isset($options->count))
		$options->count = 3;
	if(!isset($options->max))
		$options->max = 15;
	if(!isset($options->scroll))
		$options->scroll = true;
	if(strtolower($options->scroll) == "false")
		$options->scroll = false;

	preg_match('/^(.)(.*)$/', $options->src, $stream);
	if($stream[1] == "@")
		$url = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name=".$stream[2];
	if(function_exists("curl_init")) {
		$curl   = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, false);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
	}else{
		$response = file_get_contents($url);
	}
	$tweets = json_decode($response);
	$results = array();
	foreach ($tweets as $tweet) {
		$result["id"] = $tweet->id_str;
		$result["link"] = "http://twitter.com/".$tweet->user->name.'/status/'.$tweet->id_str;
		$result["content"] = $tweet->text;
		$result["date"] = strtotime($tweet->created_at);
		$result["user"] = $tweet->user->name;
		$result["user_screen_name"] = $tweet->user->screen_name;
		$result["user_image"] = $tweet->user->profile_image_url;
		$result["user_link"] = "http://twitter.com/".$tweet->user->name;
		if(substr($result["user_image"], -4) == ".gif")
			$result["user_image"] = 'http://hegy2012.de/gif_png.php?image='.urlencode(substr($result["user_image"], 0, -4));
		$results[] = $result;
	}
	$results = array_slice($results, 0, $options->max);
	
	
	$scriptHandler = $handler->scriptHandler();
	$uses = $handler->getUses("mq_twitterFeed");

	$n = 0;
	$bgHeight = ($options->count * 15);
	$bgWidth = 37;
	$tweetStart = 0;
	$title = "";
	$arrows = "";
	if(isset($options->title)) {
		$bgHeight+=6;
		$tweetStart-=6;
		$title = '<label class="mqTwitterTitle" text="'.$options->title.'" posn="1 -2 5" />';
	}
	if($options->scroll == true) {
		$bgHeight+=2;
		$bgWidth+=3;
		$scrollBarHeightTotal = $bgHeight - 10;
		$scrollBarHeight = $scrollBarHeightTotal / ceil(count($results)/$options->count);
		$scrollBarStep = $scrollBarHeight * 2;
		$arrows = '
		<quad style="Icons64x64_1" substyle="ArrowDown" posn="40 '.(-$bgHeight+5).' 3" halign="right" sizen="4 4" id="mqTwitterNext_'.$uses.'" scriptevents="1" />
		<quad style="Icons64x64_1" substyle="ArrowUp" posn="40 -1 3" halign="right" sizen="4 4" id="mqTwitterPrev_'.$uses.'" scriptevents="1" />
		<quad style="Bgs1" substyle="BgProgressBar" sizen="2 '.$scrollBarHeight.'" id="mqTwitterSB_'.$uses.'" posn="39 -5 3" halign="right" />
		';
	}

	if($options->scroll == true) {
		$scriptHandler->declareGlobalVariable("Integer", "mqTwitterStart_".$uses);
		$scriptHandler->declareMainVariable("Integer", "mqTwitterStart_".$uses, true, 0);
		$scriptHandler->addMouseClickEvent('mqTwitterPrev_'.$uses, 'mqTwitterPager_'.$uses.'("prev");');
		$scriptHandler->addMouseClickEvent('mqTwitterNext_'.$uses, 'mqTwitterPager_'.$uses.'("next");');
		$scriptHandler->addCodeToMain('declare CMlQuad mqTwitterPrev_'.$uses.' <=> (Page.GetFirstChild("mqTwitterPrev_'.$uses.'") as CMlQuad);
			mqTwitterPrev_'.$uses.'.Hide();');
		$fn = 'declare Integer items = '.count($results).'; declare Integer show = '.$options->count.';
		declare CMlQuad arrPrev <=> (Page.GetFirstChild("mqTwitterPrev_'.$uses.'") as CMlQuad);
		declare CMlQuad arrNext <=> (Page.GetFirstChild("mqTwitterNext_'.$uses.'") as CMlQuad);
		declare CMlQuad scrollBar <=> (Page.GetFirstChild("mqTwitterSB_'.$uses.'") as CMlQuad);
		';
		$declared=array();
		for($i=0; $i<count($results); $i++)
		{
			$fn.='declare CMlFrame mqTwitterTweet_'.$uses.'_'.$i.' <=> (Page.GetFirstChild("mqTweet_'.$uses.'_'.$i.'") as CMlFrame);';
			$declared[]=$i."=>mqTwitterTweet_".$uses."_".$i;
		}
		$fn.='declare CMlFrame[Integer] tweets = ['.implode(', ', $declared).'];
		for(i, mqTwitterStart_'.$uses.', (mqTwitterStart_'.$uses.' + show - 1))
		{
			if(i<tweets.count) {
				tweets[i].Hide();
				log("hid " ^i);
			}
		}
		if(mode == "next") {
			arrPrev.Show();
			mqTwitterStart_'.$uses.'+=show;
			if(mqTwitterStart_'.$uses.' + show >= items) {
				arrNext.Hide();
			}
			scrollBar.PosnY-='.$scrollBarStep.';
		} else if (mode == "prev") {
			mqTwitterStart_'.$uses.'-=show;
			if(mqTwitterStart_'.$uses.' == 0) {
				arrPrev.Hide();
			}
			if(mqTwitterStart_'.$uses.' + show < items) {
				arrNext.Show();
			}
			scrollBar.PosnY+='.$scrollBarStep.';
		}
		for(i, mqTwitterStart_'.$uses.', (mqTwitterStart_'.$uses.'+show-1))
		{
			if(i<tweets.count) {
				tweets[i].Show();
				log("showed " ^i);
			}
		}
		';
		$scriptHandler->addFunction("Void", 'mqTwitterPager_'.$uses, $fn, "Text mode");

		
	}


	$append = '<frame id="mqTweet_'.$uses.'" >
	<quad class="mqTwitterBg" id="mqTweet_'.$uses.'Bg" sizen="'.$bgWidth.' '.($bgHeight+1).'" />
	' . $title . $arrows;
	foreach($results as $tweet) {
		$hd = "";
		if($n >= $options->count)
			$hd = ' hidden="1"';
		$tweet["content"] = formatTweets($tweet["content"]);
		$append.= '
		<frame id="mqTweet_'.$uses.'_'.$n.'" class="mqTweetBg" posn="0 '.($tweetStart-(15*($n % $options->count))).' 0"'.$hd.' >
			<quad image="'.$tweet["user_image"].'" sizen="7 7" posn="1 -1 2" />
			<quad class="mqTweetLinkIcon" posn="36 -1 2" url="'.$tweet["link"].'" halign="right" />
			<label class="mqTweetHeadline" text="'.$tweet["user"].'" posn="9 -1 2" />
			<label class="mqTweetScreenName" text="@'.$tweet["user_screen_name"].'" posn="9 -3.5 2" url="'.$tweet["user_link"].'" />
			<label class="mqTweetDate" text="'.date("d.m.Y, H:i", $tweet["date"]).'" posn="9 -6 2" />
			<label class="mqTweetText" text="'.$tweet["content"].'" posn="1 -9 2" autonewline="1" sizen="35 4" maxline="3" />
		</frame>
		';
		$n++;
	}
	$append.='</frame>';
	$manialinkElement->append($append);
	$handler->updateElement($manialinkElement);
}

function formatTweets($tweet) {
	$tweet = preg_replace('/(^| )(http:\/\/|www.)([^\s]*)(\s|$)/is', '$l[\2\3]\2\3$l', $tweet);
	$tweet = preg_replace('/(^| )([^\s@]*)@([a-z0-9]*)\.([a-z0-9]{2,6})(\s|$)/is', '$l[mailto:\2@\3.\4]\2@\3.\4$l', $tweet);
	$tweet = preg_replace('/(^| )#([a-z]\w+)/is', '$l[https://twitter.com/search?q=%23\2&amp;src=hash]#\2$l', $tweet);
	$tweet = preg_replace('/(^| )@([a-z]\w+)/is', '$l[https://twitter.com/\2]@\2$l', $tweet);
	return $tweet;
}
?>