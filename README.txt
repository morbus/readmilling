
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Available scripts
 * Installing your own Readmilling


INTRODUCTION
------------

Current Maintainer: Morbus Iff <morbus@disobey.com>

Readmilling is a collection of potentially useful open source scripts using
the API provided by Readmill, a social ebook reader available for iPhone and
iPad. These scripts are not affiliated with or created by the fine chaps and
chapettes at Readmill.com.

  Readmill:                 http://readmill.com/
  Readmilling Online:       http://www.disobey.com/d/code/readmilling/
  Readmilling source code:  https://github.com/morbus/readmilling/

Found bugs or have a request? Like 'em and want to give thanks?

  Twitter:                  https://twitter.com/morbusiff
  Email:                    morbus@disobey.com
  Amazon wishlist:          http://www.amazon.com/gp/registry/25USVJDH68554


AVAILABLE SCRIPTS
-----------------

 * book-comments.php tries to merge all comments left on similar highlights
   into a single entry. Different versions of the same book can have highlights
   in different locations, so the script can't guarantee the merged entries
   are in the same order they appear in the text.


INSTALLING YOUR OWN READMILLING
-------------------------------

There are two ways to run Readmilling:

 * Through someone else's installation
 * Through your own installation

The quickest approach is through the official web installation at:

  http://www.disobey.com/d/code/readmilling/

If you'd like to customize the display of your data with a new theme, or
else tweak Readmilling's code, you'll need your own installation with:

 * PHP 5.2+ with HTTP_Request2 from PEAR.
 * A Readmill API key from http://developers.readmill.com/
 * A web server with support for PHP scripts.

Grab the source from https://github.com/morbus/readmilling, throw your API
key into the settings.php (or settings.local.php), and you should then be
able to access index.html and any of the scripts from scripts/.

Don't hesitate to email morbus@disobey.com with further questions.

