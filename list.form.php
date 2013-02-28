<article>
<div>
	<cub:if_featured_image>
		<img src="<cub:featured_image />" alt="" class="post-image" />
	</cub:if_featured_image>
	
	<cub:if_individual_article>
		<h1><cub:title /></h1>
	<cub:else />
		<h1><cub:permalink ><cub:title /></cub:permalink></h1>
	</cub:if_individual_article>
	
	
	
	
<!--
	<cub:if_featured_image>
		<img src="<cub:featured_image />" alt="" class="post-image" />
	</cub:if_featured_image>
-->
	<cub:excerp />
	
	<p><cub:permalink >Continue Reading <cub:title /></cub:permalink></p>

	<p class="meta">
		Published: <cub:posted /> 
		<cub:permalink label="Permalink" class="permlink" />
	</p>	
</div>	
</article>
