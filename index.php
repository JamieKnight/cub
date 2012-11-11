<?
	//start the clock
	$before = microtime(true);
	
	//handy files.
	include_once("core/lib.misc.php");
	include_once("core/lib.file.php");
	include_once("core/lib.tags.php");
	include_once("core/lib.parse.php");

	//setup the page info.
	global $page;
	
	//enable caching?
	$page['cache'] = (isset($_GET['cache'])) ? false : true;
	//$page['cache'] = false;
	
	//list or article page?
	$article['source'] = (isset($_GET['page'])) ? $_GET['page'] : false;
	$page['section'] = (isset($_GET['s'])) ? $_GET['s'] : 'default';
	
	if($article['source'])
	{
		$page['type'] = "article";
		$page['source_file'] = $article['source'];
	}else{
		$page['type'] = "list";
	}

	//load template and parse
	$html = file_get_contents("pages/default.php");
	
	//parse the page
	$page['secondpass'] = false;
	$html = parse($html);
	
	//quickly reparse to allow for title tag to be work 
	$page['secondpass'] = true;
	$html = parse($html);
	echo $html;
	//execution time.
	echo "<p>Render Time: ".(microtime(true)-$before)."s <br />Memory Usage: ".(memory_get_usage() / 1024)."Kb. File opens: ". $page['file_open_counter']."</p>";

?>