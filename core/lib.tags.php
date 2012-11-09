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
			
			//load form:
			$html = file_get_contents("./templates/form.default.php");
			return parse($html);
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