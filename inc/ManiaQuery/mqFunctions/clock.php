<?php
function mq_clock($handler, $manialinkElement, $code = false)
{
	$format = "H:i:s";
	if($code !== false){
		$settings = \ManiaQuery\ManiaQuery::jsobj2php($code);
		// var_dump($settings);
		if(isset($settings->format))
			$format = $settings->format;
	}
	if($manialinkElement->type == "label") {
		$scriptHandler = $handler->scriptHandler();
		$elementId = $scriptHandler->addListener($manialinkElement, false);
		// parse date
		if(preg_match('/[^\\]?[FM]/', $format)) {
		}
		if($handler->getUses("mq_clock") == 1) {
			$scriptHandler->declareMainVariable("Text[Integer]", "mqMonthMap", false, '[1=>"January", 2=>"February", 3=>"March", 04=>"April", 5=>"May", 6=>"June", 7=>"July", 8=>"August", 9=>"September", 10=>"October", 11=>"November", 12=>"December"]');
			// $scriptHandler->declareMainVariable("Text[Integer]", "mqDayMap", false, '[1=>"Monday", 2=>"Tuesday", 3=>"Wednesday", 04=>"Thurday", 5=>"Friday", 6=>"Saturday", 7=>"Sunday"]');
			$scriptHandler->addFunction("Text", "mqDayTime", 'declare Integer H = TextLib::ToInteger(TextLib::SubString(CurrentLocalDateText, 11, 2));
				if(mode=="h"){
					if(H==12 || H==24)
						return "12";
					return H % 12 ^ "";
				}
				if(mode=="a") { if(H < 13) return "am"; return "pm"; }
				if(mode=="A") { if(H < 13) return "AM"; return "PM"; }
				if(mode=="S") { declare Integer L = TextLib::ToInteger(TextLib::SubString(CurrentLocalDateText, 9, 1)); if(L==1)return "st"; if(L==2) return "nd"; if(L==3) return "rd"; return "th"; }
				return "";', "Text mode");
			$scriptHandler->addFunction("Text", "mqDayOfWeek", 'declare Text inp = input; if(inp==""){ inp = TextLib::SubString(CurrentLocalDateText, 0, 10);}
				declare Integer d = TextLib::ToInteger(TextLib::SubString(input, 8, 2));
				declare Integer m = TextLib::ToInteger(TextLib::SubString(input, 5, 2)); if(m<3)m+=12;
				declare Integer c = TextLib::ToInteger(TextLib::SubString(input, 0, 2));
				declare Integer y = TextLib::ToInteger(TextLib::SubString(input, 2, 2));
				declare Integer w = 0;
				w = (d + MathLib::NearestInteger((m+1)*2.6) + y + MathLib::NearestInteger(y/4.0) + MathLib::NearestInteger(c/4.0) - 2*c) % 7;
				w = (w + 6) % 7 - 2;
				if(mode == "w")
					return w ^"";
				if(w==0)w=7;
				if(mode == "N")
					return w ^"";
				declare Text[Integer] mqDayMap = [1=>"Monday", 2=>"Tuesday", 3=>"Wednesday", 04=>"Thurday", 5=>"Friday", 6=>"Saturday", 7=>"Sunday"];
				if(mode == "l")
					return mqDayMap[w];
				return "";
				', "Text mode, Text input");
		}
		$out = '"';
		$offsets = array();
		$offsets["H"] = "TextLib::SubString(CurrentLocalDateText, 11, 2)";
		$offsets["h"] = "mqDayTime(\"h\")";
		$offsets["a"] = "mqDayTime(\"a\")";
		$offsets["A"] = "mqDayTime(\"A\")";
		$offsets["S"] = "mqDayTime(\"S\")";
		$offsets["w"] = "mqDayOfWeek(\"w\", \"\")";
		$offsets["N"] = "mqDayOfWeek(\"N\", \"\")";
		$offsets["l"] = "mqDayMap[mqDayOfWeek(\"N\", \"\")]";
		$offsets["l"] = "mqDayOfWeek(\"l\", \"\")";
		$offsets["i"] = "TextLib::SubString(CurrentLocalDateText, 14, 2)";
		$offsets["s"] = "TextLib::SubString(CurrentLocalDateText, 17, 2)";
		$offsets["Y"] = "TextLib::SubString(CurrentLocalDateText, 0, 4)";
		$offsets["y"] = "TextLib::SubString(CurrentLocalDateText, 2, 2)";
		$offsets["m"] = "TextLib::SubString(CurrentLocalDateText, 5, 2)";
		$offsets["F"] = "mqMonthMap[TextLib::ToInteger(TextLib::SubString(CurrentLocalDateText, 5, 2))]";
		$offsets["M"] = "TextLib::SubString(mqMonthMap[TextLib::ToInteger(TextLib::SubString(CurrentLocalDateText, 5, 2))], 0, 3)";
		$offsets["d"] = "TextLib::SubString(CurrentLocalDateText, 8, 2)";
		for($i=0; $i<strlen($format); $i++) {
			$char = $format[$i];
			if(array_key_exists($char, $offsets)) {
				if($i > 0 && $format[$i-1]=="\\") {
					$out.= $char;
				} else {
					$out.='"^'.$offsets[$char].'^"';
				}				
			}else
				$out.= $char;
		}
		$out.='"';
		$scriptHandler->addCodeToLoop($elementId . ".SetText(".$out.");");
		$handler->updateElement($manialinkElement);
	}
}
?>