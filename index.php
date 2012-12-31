<?
	//start the clock
	$before = microtime(true);

	//setup the page info.
	global $page, $cubtrace;
	
	//list or article page?
	$page['source_file'] = (isset($_GET['page'])) ? $_GET['page'] : false;
	$page['section']	 = (isset($_GET['s'])) ? $_GET['s'] : 'default';
	$page['cache'] 		 = (isset($_GET['cache'])) ? false : true;
	//$page['cache'] 		 = false;
	
	//parse the page template
	$html = parse(cub_file_get_contents("pages/default.php"));
	
	//parse again & output
	$page['secondpass'] = true;
	echo parse($html);
	
	//debug
	echo '<p class="debug">Render Time: '.round(microtime(true)-$before, 5)."s 
		  Memory Usage: ".round(memory_get_usage() / 1024, 2)."Kb. 
		  File opens: ". $page['file_open_counter']." 
		  Parse Count: ". $page['parse_counter']." 
		  <br /> Data Delay: ".number_format($page['file_open_time'], 10)."</p>";
	  
	/*
		@module TextpatternParser
		@copyright 2006 Alex Shiels http://thresholdstate.com/
		@licence GPL
	*/
	function parse($thing){
		global $page;
		$page['parse_counter'] = (isset($page['parse_counter'])) ? $page['parse_counter'] + 1 : 1;
		$f = '@(</?cub:\S+\b.*(?:/)?(?<!\\\\)>)@sU';

		$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$tagpat = '@^<(/?)cub:(\w+)\b(.*?)(/?)(?<!\\\\)>$@';

		$out = '';
		$stack = array();
		$inside = '';
		$tag = array();
		foreach ($parsed as $chunk) {
			if (preg_match($tagpat, $chunk, $m)) {
				if ($m[1] == '' and $m[4] == '') {
					// opening tag

					if (empty($stack))
						$tag = $m;
					else
						$inside .= $chunk;

					array_push($stack, $m);
				}
				elseif ($m[1] == '/' and $m[4] == '') {
					// closing tag
					$pop = @array_pop($stack);
					if (!$pop or $pop[2] != $m[2])
						trigger_error(gTxt('parse_tag_mismatch', array('code', $chunk)));

					if (empty($stack)) {
						$out .= processTags(array($m[0], $tag[2], $tag[3], '', $inside));
						$inside = '';
					}
					else
						$inside .= $chunk;
				}
				elseif ($m[1] == '' and $m[4] == '/') {
					// self closing
						if (empty($stack))
							$out .= processTags(array($m[0], $m[2], $m[3]));
						else
							$inside .= $chunk;
				}
				else {
					trigger_error(gTxt('parse_error'.':'.$chunk, array('code', $chunk)));
				}
			}
			else {
				if (empty($stack))
					$out .= $chunk;
				else
					$inside .= $chunk;
			}
		}

		if ($inside)
			$out .= $inside;

		foreach ($stack as $t)
			trigger_error(gTxt('parse_tag_unclosed', array('tag', $t[2])));

		return $out;
	}
	
	function processTags($matches){
		global $pretext, $production_status, $cubtrace, $cubtracelevel, $cub_current_tag;

		$tag = $matches[1];

		$trouble_makers = array(
			'link'
		);

		if (in_array($tag, $trouble_makers))
		{
			$tag = 'tpt_'.$tag;
		}

		$atts = isset($matches[2]) ? splat($matches[2]) : '';
		$thing = isset($matches[4]) ? $matches[4] : null;

		$old_tag = @$cub_current_tag;

		$cub_current_tag = '<cub:'.$tag.
			($atts ? $matches[2] : '').
			($thing ? '>' : '/>');

		trace_add($cub_current_tag);
		@++$cubtracelevel;

		if ($production_status == 'debug')
		{
			maxMemUsage(trim($matches[0]));
		}

		$out = '';

		if (
	function_exists($tag))
		{	
			$out = $tag($atts, $thing, $matches[0], $cub_current_tag);
		}

		elseif (isset($pretext[$tag]))
		{
			$out = $pretext[$tag];
		}

		else
		{
			trigger_error(gTxt('unknown_tag', array('{tag}'=>$tag)), E_USER_WARNING);
		}

		@--$cubtracelevel;

		if (isset($matches[4]))
		{
			trace_add('</cub:'.$tag.'>');
		}

		$cub_current_tag = $old_tag;

		return $out;
	}
	
	function splat($text){
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
	
	function EvalElse($thing, $condition){
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
	
	function trace_add($msg){
		global $production_status;

		if ($production_status === 'debug')
		{
			global $txptrace,$txptracelevel;

			$txptrace[] = str_repeat("\t", $txptracelevel).$msg;
		}
	}
	
	function gTxt($var, $atts=array(), $escape='html'){
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
	
	function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true){
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}
	
	function lAtts($pairs, $atts, $warn=1){
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
	
	function sanitizeForUrl($text){
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
	
	function dumbDown($str, $lang=''){
		return $str;
	}
 
	/*
		@module Utilities
		@copyright 2012 Jamie Knight http://jkg3.com & http://github.com/jamieknight
		@licence GPL
	*/
	function parse_form($form){
		global $page;
		
		//test and set form
		$form 		= (file_exists("./forms/".$form.".php")) ? $form : "default";
		$formPath 	= "./forms/".$form.".php";
		
		//define paths
		$sourcePath = get_source_path();
		$prefix = md5($page['source_file'].$form);
		$cachePath = "./cache/".$prefix."-".filemtime($sourcePath).filemtime($formPath);
		
		if ($page['cache'] && file_exists($cachePath)) {
			$data = explode("--END OF TITLE--", cub_file_get_contents($cachePath));
			$page['article']['title'] = $data[0];
			$markup = $data[1];
		} else {
			$page['article']  = (isset($page['article'])) ? $page['article'] : parseFile($sourcePath);
			$markup = parse(cub_file_get_contents($formPath));
			
			//manage cache
			unlink_file_by_prefix("./cache/", $prefix);
			file_put_contents($cachePath, $page['article']['title']."--END OF TITLE--".$markup);
		}
		
		return array('data' => $page['article'], 'markup' => $markup);
	}
	
	function cub_file_get_contents($path){
		global $page;
		$time_start = microtime(true);
		$page['file_open_counter'] = (isset($page['file_open_counter'])) ? $page['file_open_counter'] + 1 : 1;
		$output = file_get_contents($path);
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		$page['file_open_time'] = (isset($page['file_open_time'])) ? $page['file_open_time']+ $time: $time; 
		return $output;
	}
	
	function get_source_path(){
		global $page;
		$sourcePath = "./content/".$page['section']."/".$page['source_file'].".md";
		$sourcePath = (file_exists($sourcePath)) ? $sourcePath : "./content/default/".$page['source_file'].".md";
		return $sourcePath;
	}
	
	function unlink_file_by_prefix($dir, $prefix){
		//remove stale cache later
		foreach(scandir($dir) as $file){
			$name = explode('-', $file);
			if($name[0] == $prefix){
				unlink($dir.$file);
			}
		}		
		return true;
	}
	
	function dirmtime($directory) {
	    $last_modified_time = 0;
	
	    $handler = opendir($directory);
	
	    while ($file = readdir($handler)) {
	        if(is_file($directory.DIRECTORY_SEPARATOR.$file)){
	            $files[] = $directory.DIRECTORY_SEPARATOR.$file;
	            $filemtime = filemtime($directory.DIRECTORY_SEPARATOR.$file);
	            if($filemtime>$last_modified_time) {
	                $last_modified_time = $filemtime;
	            }
	        }
	    }
	
	    closedir($handler);
	    return $last_modified_time;
	}
	
	function load_and_sort_articles($section){
			
			global $page;
			$files = scandir("./content/$section");
		
			
			$articles = array();
			$sort = array();
			
			foreach ($files as $file){
				//only process md files.
				if(substr($file, -2) == "md"){
					//set source file.
					//TODO, fix odd scope issues. 
					$page['source_file'] = str_replace(".md", '', $file); 
					$articleData = parseFile(get_source_path());
					$articles[$page['source_file']] = $articleData;
				}
			}
	
			//create array with sort order
			foreach ($articles as $key => $node) {
			   $sort[$key] = strtotime($node['published']);
			}
			
			//apply sort to $articles.
			array_multisort($sort, SORT_DESC, $articles);
			
			return $articles;
	}
	
	/*
		@module Files
		@copyright 2012 Marco Arment http://marco.org & https://github.com/marcoarment/
		@licence MIT - https://github.com/marcoarment/secondcrack/blob/master/LICENSE
	*/
	function parseFile($source_filename){
    	
       	$file = array();
        $file['source_filename'] = $source_filename;

        $file['timestamp'] = filemtime($source_filename);

        $segments = preg_split( '/\R\R/',  trim(cub_file_get_contents($source_filename)), 2);
        if (! isset($segments[1])) $segments[1] = '';
        if (count($segments) > 1) {
            // Read headers for Tag, Type values
            $headers = explode("\n", $segments[0]);
            $has_title_yet = false;
            
            foreach ($headers as $header) {
                if (isset($header[0]) && $header[0] == '=') {
                    $has_title_yet = true;
                    continue;
                }
                
                if (! $has_title_yet) {
                    $has_title_yet = true;
                    $file['title'] = $header;
                    continue;
                }
                
                
                $fields = explode(':', $header, 2);
                if (count($fields) < 2) continue;
                $fname = strtolower($fields[0]);
                $fields[1] = trim($fields[1]);
                if ($fname == 'tags') {
                     $file['tags'] = $fields[1];
                } else if ($fname == 'type') {
                     $file['type'] = str_replace('|', ' ', $fields[1]);
                } else if ($fname == 'published') {
                     $file['published'] = $fields[1];
 
                } else {
                     $file['headers'][$fname] = $fields[1];
                }
                
                if (isset($file['headers']['link'])) $file['type'] = 'link';
            }
            array_shift($segments);
        }
        
        $file['body'] = isset($segments[0]) ? $segments[0] : '';

        return $file;
    }	
 
	/*
		@module page tags
	*/
	function articleSingle($atts){	
		extract(lAtts(array(
			'form' => '',
			'limit'=>'255',
			'section' => 'default',
			'listform' => '',
		),$atts));
	
		global $page;
		$thing = parse_form($form);
		return $thing['markup'];
	}
	
	function articleList($atts){
		
		extract(lAtts(array(
			'form' => '',
			'limit'=>'255',
			'section' => 'default',
			'listform' => '',
			'sort'=>'',
		),$atts));
	
		global $page;
		
		//get file list
		$form = ($form) ? $form : "list";
		$form = ($listform) ? $listform : $form;

		//this works but is slow.
		$sectionStat = dirmtime("./content/$section");
		$prefix 	 = md5(serialize($atts)); 
		$formStat	 = stat("./forms/$form.php");
		$cacheFile 	 = "./cache/".$prefix."-".$formStat['mtime'].$sectionStat;

		if($page['cache'] && file_exists($cacheFile)){
			return cub_file_get_contents($cacheFile);
		}else{
			
			$articles = load_and_sort_articles($section);
			$output = '';
			$count 	= 0;

			//return list of articles till limit
			foreach ($articles as $source => $article){
				if($count++ < $limit){ 
					$page['source_file'] = $source;
					$page['article'] = $article;
					$thing = parse_form($form);
					$page['article'] = '';
					$output .= $thing['markup'];
				} else {
					break;
				}
			}
		
			//cache and output
			file_put_contents($cacheFile, $output);
			//unlink_file_by_prefix("./cache", )
			return $output;	
		}
	}
	
	function page_title($atts, $thing, $matchs, $rawTag){
		extract(lAtts(array(
			'seperator' => '',
		),$atts));
		
		global $page;
		
		if (isset($page['secondpass']) && $page['secondpass']){
			if ($page['source_file'] && isset($page['article']['title'])){ 
				return $seperator.$page['article']['title'];
			}
		}else{
			return $rawTag;
		}
		
		return false;
	}
	
	function if_section($atts, $thing){
		extract(lAtts(array(
			'name' => '',
		),$atts));
	
		global $page;
	
		return parse(EvalElse($thing, ($page['section'] == $name)));	
	}
	
	function if_individual_article($atts, $thing){
		extract(lAtts(array(
			'name' => '',
		),$atts));
		
		global $page;
		return parse(EvalElse($thing, ($page['source_file'] != false)));	
	}
	
	function article($atts, $thing){
		global $page;
		return ($page['source_file']) ? articleSingle($atts) : articleList($atts);
	}
	
	/*
		@module form tags
	*/
	function title($atts, $thing){
		global $page;
		return $page['article']['title']; 
	}
	
	function body($atts, $thing){
		global $page;
		//return $page['article']['body'];
		include_once("lib/PHPMarkdownExtra/markdown.php");
		return markdown($page['article']['body']); 
	}
	
	function excerp($atts, $thing){
		global $page;
		include_once("lib/PHPMarkdownExtra/markdown.php");
		$markdown = markdown($page['article']['body']); 
		$markdown = explode("</p>", $markdown);
		return $markdown[0].$markdown[1];
	}
	
	function posted($atts, $thing){
		global $page;
		extract(lAtts(array(
			'format' => 'd/m/Y',
		),$atts));

		$rawDate = $page['article']['published'];
		return ($format == 'd/m/Y') ? $rawDate : date($format, strtotime($rawDate)) ; 
	}
	
	function permalink($atts, $thing){
		extract(lAtts(array(
			'label' => '',
			'class' => '',
		),$atts));
		
		global $page;
		
		//FIXME: I assume paths below.
		$label = ($label == '') ? parse($thing) : $label ;
		$output ='<a rel="bookmark" href="/cub/index.php?page='.$page['source_file'].'" class="'.$class.'">'.$label.'</a>';
		return $output;
	}
	
	function featured_image($atts){
		global $page;
		if(isset($page['article']['headers']['featured image'])) return "images/".$page['article']['headers']['featured image'];
		return false;
	}
	
	function if_featured_image($atts, $thing){	global $page;
		return parse(EvalElse($thing, (isset($page['article']['headers']['featured image']))));
	}
	
	function if_different($atts, $thing){
		static $last;

		$key = md5($thing);

		$cond = EvalElse($thing, 1);

		$out = parse($cond);
		if (empty($last[$key]) or $out != $last[$key]) {
			return $last[$key] = $out;
		}
		else
			return parse(EvalElse($thing, 0));
	}
	