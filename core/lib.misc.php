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

		$els = strpos($thing, '<txp:else');

		if ($els === FALSE)
		{
			return $condition ? $thing : '';
		}
		elseif ($els === strpos($thing, '<txp:'))
		{
			return $condition
				? substr($thing, 0, $els)
				: substr($thing, strpos($thing, '>', $els) + 1);
		}

		$tag    = FALSE;
		$level  = 0;
		$str    = '';
		$regex  = '@(</?txp:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*/?'.chr(62).')@s';
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

	function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true)
	{
//		Ignore ENT_HTML5 and ENT_XHTML for now.
//		ENT_HTML5 and ENT_XHTML are defined in PHP 5.4+ but we consistently encode single quotes as &#039; in any doctype.
//		global $prefs;
//		static $h5 = null;
//		if (defined(ENT_HTML5)) {
//			if ($h5 === null) {
//				$h5 = ($prefs['doctype'] == 'html5' && txpinterface == 'public');
//			}
//			if ($h5) {
//				$flags = ($flags | ENT_HTML5) & ~ENT_HTML401;
//			}
//		}
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}
