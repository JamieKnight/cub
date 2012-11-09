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
	
	//If this is an article page then i could really do with the content for things like the title tag!
	if($article['source'])
	{
		$page['type'] = "article";
		$page['section'] = "default";
		$page['source_file'] = $article['source'];
	}else{
		$page['type'] = "list";
		$page['section'] = "default";
	}

	//load template and parse
	$html = file_get_contents("templates/page.default.php");
	
	//parse the page
	$page['secondpass'] = false;
	$html = parse($html);
	
	//quickly reparse to allow for title tag to be work 
	$page['secondpass'] = true;
	$html = parse($html);
	echo $html;
	//execution time.
	echo "<p>Render Time: ".(microtime(true)-$before)."s <br />Memory Usage: ".(memory_get_usage() / 1024)."Kb</p>";

?>