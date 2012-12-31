Cub CMS
=======

Micro CMS using textpattern tags powered by a folder of markdown files.

__WARNING: This project is in extremely early stages, lots of stuff is in flex and it should not be trusted with your data!__

Why:
----
Because i liked the textpattern semantic model (sections / pages / forms / articles) but wanted something which i could publish markdown files with. Some rough aims are as follows:

* At least as fast as textpattern (if not faster)
* Very simple (one short file, simple functions)
* Dropbox / github / folder backed (work in progress, currently runs from a folder).


Setup:
------
To get going, simply checkout the files and upload them to some PHP 5.3 capable hosting. 

#Current Features

* Tag parser and input file handing
* Section handling via ```<cub:if_section>```
* Article list tag (```<cub:article />```)
* Article form tags (```<cub:title />```, ```<cub:body />```, ```<cub:posted />```, ```<cub:featured_image>```. ```<cub:if_featured_image>```).
* Fancy stuff like ```<cub:if_different >```
* Free minimal theme.

#Work in progress
* Custom tags (```<cub:custom name="">```)
* Clean URLS
* Tidy up the API
* Should write some tests... _blush_
