Link to Image adds BBCode and HTML link codes to the image view. Offers code for a customizable text link, thumbnail links, and full image inline.

Author: Erik Youngren <artanis.00@gmail.com>

License: GPLv2

Submit a Bug Report or Suggestion for Link to Image:
 * http://trac.shishnet.org/shimmie2/newticket?owner=artanis.00@gmail.com&component=third%20party%20extensions&keywords=link_to_image

= Use =
There is one option in Board Config: Text Link Format.
It takes the following arguments as well as plain text.
|| arguments    || replacement                      ||
|| $id          || The image ID.                    ||
|| $hash        || The MD5 hash of the image.       ||
|| $tags        || The image's tag list.            ||
|| $base        || The base HREF as set in Config.  ||
|| $ext         || The image's extension.           ||
|| $size        || The image's display size.        ||
|| $filesize    || The image's size in KB.          ||
|| $filename    || The image's original filename.   ||
|| $title       || The site title as set in Config. ||
Link to Image will default this option to '$title - $id ($ext $size $filesize)'.
To reset to the default, simply clear the current setting. Link to Image will then fill in the default value after the save.

To leave the setting blank for any reason, leave a space (' ') in it.

= Install =
 1. Copy the folder 'contrib/link_image' to 'ext'.
 2. In the Config panel, make sure Base URL is set (you may as well set Data URL while you're there, if you haven't already.)
 3. Make sure Image Link, Thumb Link, and Short Link all contain the full path ("http://" and onward,) either by using $base or plain text. Link to Image will not be able to retrieve the correct paths without these variables.
 4. If you use .htaccess to make NiceURLs, make sure that a it allows access to the /ext/ folder, or else Link to Image will not be able to access ext/link_image/style.css.
 * http://trac.shishnet.org/shimmie2/wiki/NiceURLs - Nice URLs

= Change Log =

== Version 0.1.3b - 20070509 ==
 * Renamed style.css to _style.css to avoid the auto loader.

== Version 0.1.3 - 20070508 ==
 * Created Readme.txt
 * Merged 0.1.2 into 0.1.2b
 * Removed uneeded documentation from main.php
 * Rewrote the css to be unique. Previously used css I was wrote for elsewhere. Styled to reduce space consumption.
 * Added code to insert the css import.
  * Updated Nice URLs to allow access to the /ext/ folder. (Why is my stylesheet returning html instead of css?)
 * First SVN update.

== Version 0.1.2b - 20070507 ==
(fairly simultaneous with 0.1.2)
 * shish:
  * Updated to new extension format
   * Created folder link_image in trunk/contrib
   * Renamed link_image.ext.php to main.php and moved to /link_image/
   * Created style.css { /* 404'd :|*/ }
  * Documentation (different from mine.)
  * Changed add_text_option() and added add_label() in SetupBuildingEvent because I was using an edited version of the function that shish didn't know about. It was a wonder that didn't throw massive errors.
  * Published on SVN.

== Version 0.1.2 - 20070506 ==
 * Textboxes now select-all when they gain focus.
 * Commenting and documentation.

== Version 0.1.1 - 20070506 ==
 * Fixed HTML thumbnail link code. (image tag was being html_escaped twice, resulting in "$gt;" and "&lt;" from the first escape becoming "&amp;gt;" and "&amp;lt;") It turns out that html_escape was completely unnecessary, all I had to do was replace the single-quotes around the attributes with escaped double-quotes ('\"'.)

== Version 0.1.0 - 20070506 ==
 * Release.

= Links =
 * http://trac.shishnet.org/shimmie2/wiki/Contrib/Extensions/LinkToImage - Home
 * http://forum.shishnet.org/viewtopic.php?p=153 - Discussion
 * http://trac.shishnet.org/shimmie2/browser/trunk/contrib/link_image - Shimmie2 Trac SVN
