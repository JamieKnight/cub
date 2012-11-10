<?
// -------------------------------------------------------------
	function page_title($atts, $thing, $matchs, $rawTag){
		extract(lAtts(array(
			'seperator' => '',
		),$atts));
		
		global $page;
		
		if ($page['secondpass']){
			if ($page['type'] == "article" && isset($page['article']['title'])){ 
				return $seperator.$page['article']['title'];
			
			}
		}else{
			return $rawTag;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function if_section($atts, $thing)
	{
		extract(lAtts(array(
			'name' => '',
		),$atts));
	
		global $page;
	
		return parse(EvalElse($thing, ($page['section'] == $name)));	
	}

// -------------------------------------------------------------
	function if_individual_article($atts, $thing)
	{
		extract(lAtts(array(
			'name' => '',
		),$atts));
		
		global $page;
		return parse(EvalElse($thing, ($page['type'] == "article")));	
	}
	
// -------------------------------------------------------------
	function article($atts, $thing){

		global $page;
		switch($page['type']){
			case "article":
				return articleSingle($atts);	
				break;
			case "list":
				return articleList($atts);
				break;
		}
	}
	
// -------------------------------------------------------------	
	function title($atts, $thing){
		global $page;
		return $page['article']['title']; 
	}
// -------------------------------------------------------------	
	function body($atts, $thing){
		global $page;
		include_once("core/PHPMarkdownExtra/markdown.php");
		return markdown($page['article']['body']); 
	}
	
// -------------------------------------------------------------	
	function posted($atts, $thing){
		global $page;
		extract(lAtts(array(
			'format' => 'd/m/Y',
			'class' => '',
		),$atts));
		
		return date($format, $page['article']['published']); 
	}

// -------------------------------------------------------------	
	function featured_image(){
		global $page;
		if(isset($page['article']['headers']['featured image'])) return "images/".$page['article']['headers']['featured image'];
		return false;
	}
	
// -------------------------------------------------------------
	function if_featured_image($atts, $thing)
	{	global $page;
		return parse(EvalElse($thing, (isset($page['article']['headers']['featured image']))));
	}

// -------------------------------------------------------------
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

// -------------------------------------------------------------
	function if_different($atts, $thing)
	{
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


?>