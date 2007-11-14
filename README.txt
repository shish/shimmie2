
             _________.__    .__                .__       ________  
            /   _____/|  |__ |__| _____   _____ |__| ____ \_____  \ 
            \_____  \ |  |  \|  |/     \ /     \|  |/ __ \ /  ____/ 
            /        \|   Y  \  |  Y Y  \  Y Y  \  \  ___//       \ 
           /_______  /|___|  /__|__|_|  /__|_|  /__|\___  >_______ \
                   \/      \/         \/      \/        \/        \/

Shimmie 2.1
~~~~~~~~~~~
RSS, BBCode + Emoticons, Wiki, Theme engine 2 + Danbooru theme, UTF8 support,
Word filter, LOTS of database optimisations, Removal of ConfigSaveEvent and
many small fixes and improvements \o/


Requirements
~~~~~~~~~~~~
MySQL 4.1+
PHP 5.0+
GD or ImageMagick

PHP 4 support has currently been dropped, because
a) It's a pain in the ass to support
b) Only one person has told me they want it, and they were a dick about it

If you want PHP 4 support, mail me, and I'll see if I can get it working for
version 2.2...


Installation
~~~~~~~~~~~~
1) Create a blank database
2) Unzip shimmie into a folder on the web host
3) Visit the folder with a web browser
4) Enter the location of the database, and choose login details for the first
   admin of the board
5) Click "install". Hopefully you'll end up at the configuration screen; if
   not, you should be given instructions on how to fix any errors~


Upgrade from 2.0.X
~~~~~~~~~~~~~~~~~~
Should be automatic, just unzip and copy across config.php, images and thumbs
folders from the old version. This includes automatically messing with the
database -- back it up first!


Upgrade from 0.8.4
~~~~~~~~~~~~~~~~~~
BIG NOTE: 0.8.4 is the only version the upgrader supports; please upgrade to
that before going any further! Feel free to try other versions, just don't
complain when it doesn't work :P

Upgrade process:
1) Make backups of everything. The most important things are your database
   data, and your images folder. config.php and the thumbs folder are also
   very helpful.
2) Check that your backups actually contain the important data, they aren't
   just empty files with the right names...
3) Create a new, blank database, separate from the old one
4) Unzip shimmie2 into a different folder than shimmie1
5) Visit the URL of shimmie2
6) Fill in the old database location, the new database location, and the full
   path to the old installation folder (the folder where the old "images" and
   "thumbs" can be found)
7) Click "upgrade"
8) Wait a couple of minutes while data is copied from the old install into the
   new one. You may wish to spend these minutes in prayer :P
9) Log in with an existing admin account and set things up to taste

The old installation can now be removed, but you may wish to keep it around
until you're sure everything in v2 is working properly~


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



