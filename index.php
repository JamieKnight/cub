<?
	//start the clock
	$before = microtime(true);
	
	//handy files.
	include_once("core/lib.misc.php");
	include_once("core/lib.file.php");
	include_once("core/lib.tags.php");
	include_once("core/lib.parse.php");
	include_once("core/PHPMarkdownExtra/markdown.php");
	
	//setup the page info.
	global $page;
	
	//find the file and parse the markdown
	$article['source'] = (isset($_GET['page'])) ? $_GET['page'] : false;
	if($article['source'])
	{
		$file = "content/".$article['source'].".md";
		if(file_exists($file))
		{
			$article = readFileContentIntoArray($file);
			$article['body'] = Markdown($article['body']);
			
			//setup the page info.
			global $page;
			$page['type'] = "article";
			$page['section'] = "default";
			$page['article'] = $article;
		}
	}else{
		$page['type'] = "list";
		$page['section'] = "default";
	}

	//load template and parse
	$html = file_get_contents("templates/page.default.php");
	echo parse($html);
	
	//execution time.
	echo "<p>Render Time: ".(microtime(true)-$before)."s <br />Memory Usage: ".(memory_get_usage() / 1024)."Kb</p>";

?>