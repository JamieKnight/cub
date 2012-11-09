Cub CMS
=======

Micro CMS using textpattern tags powered by a folder of markdown files.

__WARNING: This project is in extremely early stages, lots of stuff is in flex and it should not be trusted with your data!__

Setup:
------
To get going, simply checkout the files and upload them to some PHP 5.3 capable hosting. 

#Current Features

* Tag parser and input file handing
* Section handling via ```<cub:if_section>```
* Article list tag (```<cub:article />```)
* Article form tags (```<cub:title />```, ```<cub:body />```, ```<cub:posted />```, ```<cub:featured_image>```. ```<cub:if_featured_image>```).


#Work in progress
* Basic routing (list pages and article pages).
* Custom tags (```<cub:custom name="">```)