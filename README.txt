
          _________.__    .__                .__       ________  
         /   _____/|  |__ |__| _____   _____ |__| ____ \_____  \ 
         \_____  \ |  |  \|  |/     \ /     \|  |/ __ \ /  ____/ 
         /        \|   Y  \  |  Y Y  \  Y Y  \  \  ___//       \ 
        /_______  /|___|  /__|__|_|  /__|_|  /__|\___  >_______ \
                \/      \/         \/      \/        \/        \/

Shimmie 2.3
~~~~~~~~~~~
1000 days since the first import was converted to git \o/

And over a year since the last non-beta release x_x


New since 2.2
~~~~~~~~~~~~~
For admins:
 o) Simplified installer, only one question
 o) Improved mass tag editor, allows searching and replacing multiple tags
 o) Automated installation
 o) Automatic detection of niceurl support
 o) Support for caching data, to lower database load
 o) Improved extension management, built-in documentation function
 o) Built-in documentation for extensions
 o) Better spam filtering
 o) CAPTCHA tests for signup and anonymous comments
 o) Detailed event logging

For users:
 o) New default theme
 o) Better BBCode support
 o) Image ratings, with eg. hiding of explicit images to anonymous users
 o) Theme improvements, new themes
 o) Better searching
 o) Cooliris support
 o) Search for images you've voted for (simple "favourites" system)
 o) Gravatar support
 o) Nicer date formatting
 o) Random image extension
 o) The current featured image is linkable
 o) Private message system
 o) Only the most recent posts for each image shown on the comment list
 o) Image pools extension

For developers:
 o) More sensible APIs
 o) Automated testing
 o) Preliminary SQLite & Postgres support, for people with very small and
    very large sites (Not all extensions are compatible yet though)

And much more that I can't remember \o/


Requirements
~~~~~~~~~~~~
MySQL 4.1+
PHP 5.0+
GD or ImageMagick

There is no PHP4 support, because it lacks many useful features which make
shimmie development easier, faster, and less bug prone. PHP4 is officially
dead, if your host hasn't upgraded to at least 5, I would suggest switching
hosts. I'll even host galleries for free if you can't get hosting elsewhere
for whatever reason~


Installation
~~~~~~~~~~~~
1) Create a blank database
2) Unzip shimmie into a folder on the web host
3) Visit the folder with a web browser
4) Enter the location of the database
5) Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~


Upgrade from 2.2.X
~~~~~~~~~~~~~~~~~~
Should be automatic, just unzip into a clean folder and copy across
config.php, images and thumbs folders from the old version. This
includes automatically messing with the database -- back it up first!


Upgrade from earlier versions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
I very much recommend going via each major release in turn (eg, 2.0.6
-> 2.1.3 -> 2.2.4 -> 2.3.0 rather than 2.0.6 -> 2.3.0). While the basic
database and file formats haven't changed *completely*, it's different
enough to be a pain.


Development Info
~~~~~~~~~~~~~~~~
http://shimmie.shishnet.org/doc/

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them


Contact
~~~~~~~
#shimmie on Freenode -- IRC
webmaster at shishnet.org -- email
http://redmine.shishnet.org/projects/show/shimmie2 -- bug tracker


Licence
~~~~~~~
All code is GPLv2 unless mentioned otherwise; ie, if you give shimmie to
someone else, you have to give them the source (which should be easy, as PHP
is an interpreted language...). If you want to add customisations to your own
site, then those customisations belong to you, and you can do what you want
with them.



