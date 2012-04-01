
     _________.__     .__                   .__         ________   
    /   _____/|  |__  |__|  _____    _____  |__|  ____  \_____  \  
    \_____  \ |  |  \ |  | /     \  /     \ |  |_/ __ \  /  ____/  
    /        \|   Y  \|  ||  Y Y  \|  Y Y  \|  |\  ___/ /       \  
   /_______  /|___|  /|__||__|_|  /|__|_|  /|__| \___  >\_______ \ 
           \/      \/           \/       \/          \/         \/ 
                                                                
_________________________________________________________________________


Shimmie Alpha
~~~~~~~~~~~~~
If you're reading this on github and looking for the stable version, go
to the top of the page -> switch branches -> pick a stable branch. To do
similarly with a git clone, "git checkout -b my_2.X origin/branch_2.X"

This code is for people who want to write extensions compatible with the
next version of shimmie. You can run a production site with it if you're
feeling brave, but it's not recommended.

If there is a feature here, and not in the stable branch, that's probably
because the feature doesn't work yet :P


Requirements
~~~~~~~~~~~~
MySQL 5.1+ (with experimental support for PostgreSQL 8+ and SQLite 3)
PHP 5.2.6+
GD or ImageMagick


Installation
~~~~~~~~~~~~
1) Create a blank database
2) Unzip shimmie into a folder on the web host
3) Visit the folder with a web browser
4) Enter the location of the database
5) Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~


Upgrade from 2.3.X
~~~~~~~~~~~~~~~~~~
- Backup your current files and database!
- Unzip into a clean folder
- Copy across the images, thumbs, and data folders
- Move old/config.php to new/data/config/shimmie.conf.php
- Edit shimmie.conf.php to use the new database connection format:

 OLD: $database_dsn = "<proto>://<username>:<password>@<host>/<database>";
 NEW: define("DATABASE_DSN", "<proto>:user=<username>;password=<password>;host=<host>;dbname=<database>");

The rest should be automatic~

If there are any errors with the upgrade process, "in_upgrade=true" will
be left in the config table and the process will be paused for the admin
to investigate. Deleting this config entry and refreshing the page should
continue the upgrade from where it left off.


Upgrade from earlier versions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
I very much recommend going via each major release in turn (eg, 2.0.6
-> 2.1.3 -> 2.2.4 -> 2.3.0 rather than 2.0.6 -> 2.3.0). While the basic
database and file formats haven't changed *completely*, it's different
enough to be a pain.


Custom Configuration
~~~~~~~~~~~~~~~~~~~~
Various aspects of Shimmie can be configured to suit your site specific needs
via the file "data/config/shimmie.conf.php" (created after installation).
Take a look at "core/sys_config.inc.php" for the available options that can
be used.


Custom User Classes
~~~~~~~~~~~~~~~~~~~
User classes can be added to or altered by placing them in
`data/config/user-classes.conf.php`. For example, one can override the
default anonymous "allow nothing" permissions like so:

new UserClass("anonymous", "base", array(
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"create_image_report" => True,
));

For a list of permissions, see core/userclass.class.php


Development Info
~~~~~~~~~~~~~~~~
http://shimmie.shishnet.org/doc/

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them


Contact
~~~~~~~
#shimmie on Freenode -- IRC
webmaster at shishnet.org -- email
https://github.com/shish/shimmie2/issues -- bug tracker


Licence
~~~~~~~
All code is GPLv2 unless mentioned otherwise; ie, if you give shimmie to
someone else, you have to give them the source (which should be easy, as PHP
is an interpreted language...). If you want to add customisations to your own
site, then those customisations belong to you, and you can do what you want
with them.
