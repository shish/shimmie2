
             _________.__    .__                .__       ________  
            /   _____/|  |__ |__| _____   _____ |__| ____ \_____  \ 
            \_____  \ |  |  \|  |/     \ /     \|  |/ __ \ /  ____/ 
            /        \|   Y  \  |  Y Y  \  Y Y  \  \  ___//       \ 
           /_______  /|___|  /__|__|_|  /__|_|  /__|\___  >_______ \
                   \/      \/         \/      \/        \/        \/

Shimmie Alpha
~~~~~~~~~~~~~
Tag history, RSS for search results, unified image info editor, extensible
file format support (ZIP, SWF, SVG, MP3, ...), wget transloader, event log
filtering and sorting, as well as the usual various improvements~


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
4) Enter the location of the database, and choose login details for the first
   admin of the board
5) Click "install". Hopefully you'll end up at the configuration screen; if
   not, you should be given instructions on how to fix any errors~


Upgrade from 2.1.X
~~~~~~~~~~~~~~~~~~
Should be automatic, just unzip and copy across config.php, images and thumbs
folders from the old version. This includes automatically messing with the
database -- back it up first!


Contact
~~~~~~~
http://forum.shishnet.org/viewforum.php?f=6 -- discussion forum
http://trac.shishnet.org/shimmie2/ -- bug tracker
webmaster at shishnet.org -- email
#shimmie on Freenode -- IRC


Licence
~~~~~~~
All code is GPL2; ie, if you give shimmie to someone else, you have to give
them the source (which should be easy, as PHP is an interpreted language...).
If you want to add customisations to your own site, then those customisations
belong to you, and you can do what you want with them.



