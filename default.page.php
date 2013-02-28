<!DOCTYPE html>
<html>
<head>
	<title>(CUB) Jamie & Lion<cub:page_title seperator=" - " /></title>
	<link href="screen.css" media="screen" rel="stylesheet" type="text/css" />
	<meta name="viewport" content="width=device-width initial-scale=1.0, minimum-scale=1.0" />
	<meta charset="UTF-8">
</head>

<body id="home">
	<nav>
		<ul>
			<li><a href="/cub/">Home</a></li>
			<li><a href="/cub/index.php?s=archive">Archive</a></li>
			<li><a href="http://pluslion.com">Work</a></li>
		</ul>
	</nav>
	
	<aside>
		<a href="/">
			<img src="/headshot.jpg" alt="Photo of Jamie &amp; Lion" />
		</a>
		<p>The personal site of <strong>Jamie Knight</strong>, a slightly autistic (<abbr title="British Boradcasting Corpoeration">BBC</abbr>)  <strong>web developer</strong>, <strong>speaker</strong> and <strong>mountain biker</strong> who is never seen far from his plush sidekick <em>Lion</em>.</p>
	</aside>

	<cub:if_section name="archive">
		<div id="archive">
			<cub:article listform="archive"/>
		</div>
	<cub:else />
		<cub:article limit="3" />		
	</cub:if_section>
</body>
</html>