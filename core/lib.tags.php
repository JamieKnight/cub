<?

// -------------------------------------------------------------
	function page_title($atts){
		return "Page Title";
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
		
		global $page;
		if($page['type'] == "article"){
			
			$cachePath = "./core/cache/".$page['source_file'].".md";
			$sourcePath = "./content/".$page['source_file'].".md";
			$cache = true;
			//check cache
			if($cache && file_exists($cachePath) && (microtime(true)-filemtime($cachePath) < 120))
			{
				print_r(microtime(true)-filemtime($cachePath));
				return file_get_contents($cachePath);
				
			}else if(file_exists($sourcePath)){
				
				//get file content and apply markdown.
				$article = readFileContentIntoArray($sourcePath);
				$article['body'] = Markdown($article['body']);
	
				//load form:
				$markup = parse(file_get_contents("./templates/form.default.php"));
				
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
?>