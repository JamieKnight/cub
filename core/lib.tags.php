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
	
		$section = "house";
	
		return parse(EvalElse($thing, ($section == $name)));	
	}	
	
// -------------------------------------------------------------
	function article($atts, $thing){
		
		extract(lAtts(array(
			'form' => '',
			'limit' => '',
		),$atts));
		
		global $page;
		if($page['type'] == "article"){
			
			$cachePath = "./core/cache/".$form."-".$page['source_file'].".md";
			$sourcePath = "./content/".$page['source_file'].".md";
			$formPath = "./templates/form.".$form.".php";
			$cache = true;
			//check cache
			if($cache && file_exists($cachePath) && (microtime(true)-filemtime($cachePath) < 120))
			{
				return file_get_contents($cachePath);
				
			}else if(file_exists($sourcePath)){
				
				//get file content and apply markdown.
				$article = readFileContentIntoArray($sourcePath);
				$article['body'] = Markdown($article['body']);
				$page['article'] = $article;
	
				//load form:
				$form = (file_exists($formPath)) ? $form : "default";
				$markup = parse(file_get_contents("./templates/form.".$form.".php"));
				
				//add to cache
				file_put_contents($cachePath, $markup);
				return $markup;
			}			
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
		return $page['article']['body']; 
	}
	
// -------------------------------------------------------------	
	function posted($atts, $thing){
		global $page;
		return date("d/m/Y", $page['article']['published']); 
	}

// -------------------------------------------------------------	
	function featured_image(){
		global $page;
		if(isset($page['article']['headers']['feature image'])) return "images/".$page['article']['headers']['feature image'];
		return false;
	}
	
// -------------------------------------------------------------
	function if_featured_image($atts, $thing)
	{	global $page;
		return parse(EvalElse($thing, (isset($page['article']['headers']['feature image']))));
	}

// -------------------------------------------------------------
	function permalink($atts){
		extract(lAtts(array(
			'label' => '',
			'class' => '',
		),$atts));
		
		global $page;
		//FIXME: I assume paths below.
		$output ='<a rel="bookmark" href="/cub/index.php?page='.$page['source_file'].'" class="'.$class.'">'.$label.'</a>';
		return $output;
	}

?>