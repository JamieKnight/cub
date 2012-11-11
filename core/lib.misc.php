<? 
// -------------------------------------------------------------
	function splat($text)
	{
		$atts  = array();

		if (preg_match_all('@(\w+)\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+))@s', $text, $match, PREG_SET_ORDER))
		{
			foreach ($match as $m)
			{
				switch (count($m))
				{
					case 3:
						$val = str_replace('""', '"', $m[2]);
						break;
					case 4:
						$val = str_replace("''", "'", $m[3]);

						if (strpos($m[3], '<txp:') !== FALSE)
						{
							trace_add("[attribute '".$m[1]."']");
							$val = parse($val);
							trace_add("[/attribute]");
						}

						break;
					case 5:
						$val = $m[4];
						trigger_error(gTxt('attribute_values_must_be_quoted'), E_USER_WARNING);
						break;
				}

				$atts[strtolower($m[1])] = $val;
			}

		}

		return $atts;
	}

// --------------------------------------------------------------
	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;
		static $gTxtTrue = NULL, $gTxtFalse;

		if (empty($gTxtTrue))
		{
			$gTxtTrue = gTxt('true');
			$gTxtFalse = gTxt('false');
		}

		trace_add("[$txp_current_tag: ".($condition ? $gTxtTrue : $gTxtFalse)."]");

		$els = strpos($thing, '<cub:else');

		if ($els === FALSE)
		{
			return $condition ? $thing : '';
		}
		elseif ($els === strpos($thing, '<cub:'))
		{
			return $condition
				? substr($thing, 0, $els)
				: substr($thing, strpos($thing, '>', $els) + 1);
		}

		$tag    = FALSE;
		$level  = 0;
		$str    = '';
		$regex  = '@(</?cub:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*/?'.chr(62).')@s';
		$parsed = preg_split($regex, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parsed as $chunk)
		{
			if ($tag)
			{
				if ($level === 0 and strpos($chunk, 'else') === 5 and substr($chunk, -2, 1) === '/')
				{
					return $condition
						? $str
						: substr($thing, strlen($str)+strlen($chunk));
				}
				elseif (substr($chunk, 1, 1) === '/')
				{
					$level--;
				}
				elseif (substr($chunk, -2, 1) !== '/')
				{
					$level++;
				}
			}

			$tag = !$tag;
			$str .= $chunk;
		}

		return $condition ? $thing : '';
	}

// -------------------------------------------------------------
	function trace_add($msg)
	{
		global $production_status;

		if ($production_status === 'debug')
		{
			global $txptrace,$txptracelevel;

			$txptrace[] = str_repeat("\t", $txptracelevel).$msg;
		}
	}

//-------------------------------------------------------------
	function gTxt($var, $atts=array(), $escape='html')
	{
		global $textarray;

		if (!is_array($atts)) {
			$atts = array();
		}

		if ($escape == 'html')
		{
			foreach ($atts as $key => $value)
			{
				$atts[$key] = txpspecialchars($value);
			}
		}

		$v = strtolower($var);
		if (isset($textarray[$v])) {
			$out = $textarray[$v];
			if ($out !== '') return strtr($out, $atts);
		}

		if ($atts)
			return $var.': '.join(', ', $atts);
		return $var;
	}

// --------------------------------------------------------------
	function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true)
	{
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}

// -------------------------------------------------------------
	function lAtts($pairs, $atts, $warn=1)
	{
		global $production_status;

		foreach($atts as $name => $value)
		{
			if (array_key_exists($name, $pairs))
			{
				$pairs[$name] = $value;
			}
			elseif ($warn and $production_status != 'live')
			{
				trigger_error(gTxt('unknown_attribute', array('{att}' => $name)));
			}
		}

		return ($pairs) ? $pairs : false;
	}
// -------------------------------------------------------------
	function sanitizeForUrl($text)
	{
		// any overrides?
		$out = '';
		$in = $text;
		// Remove names entities and tags
		$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",dumbDown($text));
		// Dashify high-order chars leftover from dumbDown()
		$text = preg_replace("/[\x80-\xff]/","-",$text);
		// Collapse spaces, minuses, (back-)slashes and non-words
		$text = preg_replace('/[\s\-\/\\\\]+/', '-', trim(preg_replace('/[^\w\s\-\/\\\\]/', '', $text)));
		// Remove all non-whitelisted characters
		$text = preg_replace("/[^A-Za-z0-9\-_]/","",$text);
		// Sanitizing shouldn't leave us with plain nothing to show.
		// Fall back on percent-encoded URLs as a last resort for RFC 1738 conformance.
		if (empty($text) || $text == '-')
		{
			$text = rawurlencode($in);
		}
		return $text;
	}

// -------------------------------------------------------------
	function dumbDown($str, $lang='')
	{
		static $array;
		if (empty($array[$lang])) {
			$array[$lang] = array( // nasty, huh?.
				'&#192;'=>'A','&Agrave;'=>'A','&#193;'=>'A','&Aacute;'=>'A','&#194;'=>'A','&Acirc;'=>'A',
				'&#195;'=>'A','&Atilde;'=>'A','&#196;'=>'Ae','&Auml;'=>'A','&#197;'=>'A','&Aring;'=>'A',
				'&#198;'=>'Ae','&AElig;'=>'AE',
				'&#256;'=>'A','&#260;'=>'A','&#258;'=>'A',
				'&#199;'=>'C','&Ccedil;'=>'C','&#262;'=>'C','&#268;'=>'C','&#264;'=>'C','&#266;'=>'C',
				'&#270;'=>'D','&#272;'=>'D','&#208;'=>'D','&ETH;'=>'D',
				'&#200;'=>'E','&Egrave;'=>'E','&#201;'=>'E','&Eacute;'=>'E','&#202;'=>'E','&Ecirc;'=>'E','&#203;'=>'E','&Euml;'=>'E',
				'&#274;'=>'E','&#280;'=>'E','&#282;'=>'E','&#276;'=>'E','&#278;'=>'E',
				'&#284;'=>'G','&#286;'=>'G','&#288;'=>'G','&#290;'=>'G',
				'&#292;'=>'H','&#294;'=>'H',
				'&#204;'=>'I','&Igrave;'=>'I','&#205;'=>'I','&Iacute;'=>'I','&#206;'=>'I','&Icirc;'=>'I','&#207;'=>'I','&Iuml;'=>'I',
				'&#298;'=>'I','&#296;'=>'I','&#300;'=>'I','&#302;'=>'I','&#304;'=>'I',
				'&#306;'=>'IJ',
				'&#308;'=>'J',
				'&#310;'=>'K',
				'&#321;'=>'K','&#317;'=>'K','&#313;'=>'K','&#315;'=>'K','&#319;'=>'K',
				'&#209;'=>'N','&Ntilde;'=>'N','&#323;'=>'N','&#327;'=>'N','&#325;'=>'N','&#330;'=>'N',
				'&#210;'=>'O','&Ograve;'=>'O','&#211;'=>'O','&Oacute;'=>'O','&#212;'=>'O','&Ocirc;'=>'O','&#213;'=>'O','&Otilde;'=>'O',
				'&#214;'=>'Oe','&Ouml;'=>'Oe',
				'&#216;'=>'O','&Oslash;'=>'O','&#332;'=>'O','&#336;'=>'O','&#334;'=>'O',
				'&#338;'=>'OE',
				'&#340;'=>'R','&#344;'=>'R','&#342;'=>'R',
				'&#346;'=>'S','&#352;'=>'S','&#350;'=>'S','&#348;'=>'S','&#536;'=>'S',
				'&#356;'=>'T','&#354;'=>'T','&#358;'=>'T','&#538;'=>'T',
				'&#217;'=>'U','&Ugrave;'=>'U','&#218;'=>'U','&Uacute;'=>'U','&#219;'=>'U','&Ucirc;'=>'U',
				'&#220;'=>'Ue','&#362;'=>'U','&Uuml;'=>'Ue',
				'&#366;'=>'U','&#368;'=>'U','&#364;'=>'U','&#360;'=>'U','&#370;'=>'U',
				'&#372;'=>'W',
				'&#221;'=>'Y','&Yacute;'=>'Y','&#374;'=>'Y','&#376;'=>'Y',
				'&#377;'=>'Z','&#381;'=>'Z','&#379;'=>'Z',
				'&#222;'=>'T','&THORN;'=>'T',
				'&#224;'=>'a','&#225;'=>'a','&#226;'=>'a','&#227;'=>'a','&#228;'=>'ae',
				'&auml;'=>'ae',
				'&#229;'=>'a','&#257;'=>'a','&#261;'=>'a','&#259;'=>'a','&aring;'=>'a',
				'&#230;'=>'ae',
				'&#231;'=>'c','&#263;'=>'c','&#269;'=>'c','&#265;'=>'c','&#267;'=>'c',
				'&#271;'=>'d','&#273;'=>'d','&#240;'=>'d',
				'&#232;'=>'e','&#233;'=>'e','&#234;'=>'e','&#235;'=>'e','&#275;'=>'e',
				'&#281;'=>'e','&#283;'=>'e','&#277;'=>'e','&#279;'=>'e',
				'&#402;'=>'f',
				'&#285;'=>'g','&#287;'=>'g','&#289;'=>'g','&#291;'=>'g',
				'&#293;'=>'h','&#295;'=>'h',
				'&#236;'=>'i','&#237;'=>'i','&#238;'=>'i','&#239;'=>'i','&#299;'=>'i',
				'&#297;'=>'i','&#301;'=>'i','&#303;'=>'i','&#305;'=>'i',
				'&#307;'=>'ij',
				'&#309;'=>'j',
				'&#311;'=>'k','&#312;'=>'k',
				'&#322;'=>'l','&#318;'=>'l','&#314;'=>'l','&#316;'=>'l','&#320;'=>'l',
				'&#241;'=>'n','&#324;'=>'n','&#328;'=>'n','&#326;'=>'n','&#329;'=>'n',
				'&#331;'=>'n',
				'&#242;'=>'o','&#243;'=>'o','&#244;'=>'o','&#245;'=>'o','&#246;'=>'oe',
				'&ouml;'=>'oe',
				'&#248;'=>'o','&#333;'=>'o','&#337;'=>'o','&#335;'=>'o',
				'&#339;'=>'oe',
				'&#341;'=>'r','&#345;'=>'r','&#343;'=>'r',
				'&#353;'=>'s',
				'&#249;'=>'u','&#250;'=>'u','&#251;'=>'u','&#252;'=>'ue','&#363;'=>'u',
				'&uuml;'=>'ue',
				'&#367;'=>'u','&#369;'=>'u','&#365;'=>'u','&#361;'=>'u','&#371;'=>'u',
				'&#373;'=>'w',
				'&#253;'=>'y','&#255;'=>'y','&#375;'=>'y',
				'&#382;'=>'z','&#380;'=>'z','&#378;'=>'z',
				'&#254;'=>'t',
				'&#223;'=>'ss',
				'&#383;'=>'ss',
				'&agrave;'=>'a','&aacute;'=>'a','&acirc;'=>'a','&atilde;'=>'a','&auml;'=>'ae',
				'&aring;'=>'a','&aelig;'=>'ae','&ccedil;'=>'c','&eth;'=>'d',
				'&egrave;'=>'e','&eacute;'=>'e','&ecirc;'=>'e','&euml;'=>'e',
				'&igrave;'=>'i','&iacute;'=>'i','&icirc;'=>'i','&iuml;'=>'i',
				'&ntilde;'=>'n',
				'&ograve;'=>'o','&oacute;'=>'o','&ocirc;'=>'o','&otilde;'=>'o','&ouml;'=>'oe',
				'&oslash;'=>'o',
				'&ugrave;'=>'u','&uacute;'=>'u','&ucirc;'=>'u','&uuml;'=>'ue',
				'&yacute;'=>'y','&yuml;'=>'y',
				'&thorn;'=>'t',
				'&szlig;'=>'ss'
			);
		}

		return strtr($str, $array[$lang]);
	}
	
// -------------------------------------------------------------	
	function articleSingle($atts){
		
		extract(lAtts(array(
			'form' => '',
			'limit'=>'255',
			'section' => 'default',
			'listform' => '',
		),$atts));
	
		global $page;
		$page['source_file'] = $page['source_file'];
		$thing = parse_form($form);
		return $thing['markup'];
	}

// -------------------------------------------------------------
	function articleList($atts){
		
		extract(lAtts(array(
			'form' => '',
			'limit'=>'255',
			'section' => 'default',
			'listform' => '',
			'sort'=>'',
		),$atts));
	
		global $page;
		

		$files = scandir("./content/$section");
		
		$form = ($form) ? $form : "list";
		$form = ($listform) ? $listform : $form;
		
		$output = '';
		$count 	= 0;
		
		$articles = array();
		$sort = array();
		
		foreach ($files as $file){
			if(substr($file, -2) == "md"){
				$page['source_file'] = str_replace(".md", '', $file) ; 
				$thing = parse_form($form);
				if($thing) $articles[] = $thing;
			}
		}			

		//create sort array
		foreach ($articles as $key => $node) {
		   $sort[$key] = $node['data']['published'];
		}
		
		//apply sort to $articles.
		array_multisort($sort, SORT_DESC, $articles);
		
		//return list of articles
		foreach ($articles as $article){
			if($count++ < $limit){  
				$output .= $article['markup'];
			} else {
				break;
			}
		}
			
		return $output;	
	}

// -------------------------------------------------------------
	function parse_form($form){
		global $page;
		
		$output = array();
		
		//define paths
		$cachePath 	= "./core/cache/".$form."-".$page['source_file'].".md";
		$sourcePath = "./content/".$page['section']."/".$page['source_file'].".md";
		$sourcePath = (file_exists($sourcePath)) ? $sourcePath : "./content/default/".$page['source_file'].".md";
		
		//test and set form
		$form 		= (file_exists("./forms/".$form.".php")) ? $form : "default";
		$formPath 	= "./forms/".$form.".php";
		
		//handle caching
		$sourceTime   = (file_exists($sourcePath)) ? filemtime($sourcePath) : false;
		$cacheTime	  = (file_exists($cachePath)) ? filemtime($cachePath) : false;
		$formTime	  = filemtime($formPath);
		
		//provide raw file data (costs 5ms on single articles, where data is unused. But has nicer flow)
		$data  =  readFileContentIntoArray($sourcePath);
		$status = (isset($data['headers']['status'])) ? $data['headers']['status'] : "live" ;
		
		//ignore articles without title and which are hidden
		if ($data['title'] && $status !== "hidden"){
		
			//Cache is valid only if source and form are older than cache.
			if ($page['cache'] && ($sourceTime && $cacheTime && $sourceTime < $cacheTime && $formTime < $cacheTime)){
				$markup = cub_file_get_contents($cachePath);			
		
			} elseif (file_exists($sourcePath)){
				$page['article'] = $data;
				$markup = parse(cub_file_get_contents($formPath));
				file_put_contents($cachePath, $markup);
			}
			
			return array('data' => $data, 'markup' =>$markup);
		}else{
			return false;
		}
	}
	
// -------------------------------------------------------------
	function cub_file_get_contents($path){
		global $page;
		$page['file_open_counter'] = (isset($page['file_open_counter'])) ? $page['file_open_counter'] + 1 : 1;
		return file_get_contents($path);
	}
	
	
