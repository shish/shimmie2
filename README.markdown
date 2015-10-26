```
     _________.__     .__                   .__         ________   
    /   _____/|  |__  |__|  _____    _____  |__|  ____  \_____  \  
    \_____  \ |  |  \ |  | /     \  /     \ |  |_/ __ \  /  ____/  
    /        \|   Y  \|  ||  Y Y  \|  Y Y  \|  |\  ___/ /       \  
   /_______  /|___|  /|__||__|_|  /|__|_|  /|__| \___  >\_______ \ 
           \/      \/           \/       \/          \/         \/ 
                                                                
```

# Shimmie

[![Build Status](https://travis-ci.org/shish/shimmie2.svg?branch=master)](https://travis-ci.org/shish/shimmie2)

This is the main branch of Shimmie, if you know anything at all about running
websites, this is the version to use.

Alternatively if you want a version that will never have significant changes,
check out one of the versioned branches.

# Requirements

- MySQL/MariaDB 5.1+ (with experimental support for PostgreSQL 9+ and SQLite 3)
- PHP 5.4.8+
- GD or ImageMagick

# Installation

1. Create a blank database
2. Unzip shimmie into a folder on the web host
3. Visit the folder with a web browser
4. Enter the location of the database
5. Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~

## Upgrade from 2.3.X

1. Backup your current files and database!
2. Unzip into a clean folder
3. Copy across the images, thumbs, and data folders
4. Move `old/config.php` to `new/data/config/shimmie.conf.php`
5. Edit `shimmie.conf.php` to use the new database connection format:

OLD Format:
```php
$database_dsn = "<proto>://<username>:<password>@<host>/<database>";
```

NEW Format:
```php
define("DATABASE_DSN", "<proto>:user=<username>;password=<password>;host=<host>;dbname=<database>");
```

The rest should be automatic~

If there are any errors with the upgrade process, `in_upgrade=true` will
be left in the config table and the process will be paused for the admin
to investigate.

Deleting this config entry and refreshing the page should continue the upgrade from where it left off.


### Upgrade from earlier versions

I very much recommend going via each major release in turn (eg, 2.0.6
-> 2.1.3 -> 2.2.4 -> 2.3.0 rather than 2.0.6 -> 2.3.0).

While the basic database and file formats haven't changed *completely*, it's different
enough to be a pain.


## Custom Configuration

Various aspects of Shimmie can be configured to suit your site specific needs
via the file `data/config/shimmie.conf.php` (created after installation).

Take a look at `core/sys_config.inc.php` for the available options that can
be used.


#### Custom User Classes

User classes can be added to or altered by placing them in
`data/config/user-classes.conf.php`.

For example, one can override the default anonymous "allow nothing" permissions like so:

```php
new UserClass("anonymous", "base", array(
	"create_comment" => True,
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"create_image_report" => True,
));
```

For a moderator class, being a regular user who can delete images and comments:

```php
new UserClass("moderator", "user", array(
	"delete_image" => True,
	"delete_comment" => True,
));
```

For a list of permissions, see `core/userclass.class.php`


# Development Info

ui-* cookies are for the client-side scripts only; in some configurations
(eg with varnish cache) they will be stripped before they reach the server

shm-* CSS classes are for javascript to hook into; if you're customising
themes, be careful with these, and avoid styling them, eg:

- shm-thumb = outermost element of a thumbnail
   * data-tags
   * data-post-id
- shm-toggler = click this to toggle elements that match the selector
  * data-toggle-sel
- shm-unlocker = click this to unlock elements that match the selector
  * data-unlock-sel
- shm-clink = a link to a comment, flash the target element when clicked
  * data-clink-sel

Documentation: http://shimmie.shishnet.org/doc/

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them


# Contact

IRC: `#shimmie` on [Freenode](irc.freenode.net)

Email: webmaster at shishnet.org

Issue/Bug tracker: http://github.com/shish/shimmie2/issues


# Licence

All code is released under the [GNU GPL Version 2](http://www.gnu.org/licenses/gpl-2.0.html) unless mentioned otherwise.

If you give shimmie to someone else, you have to give them the source (which should be easy, as PHP
is an interpreted language...). If you want to add customisations to your own
site, then those customisations belong to you, and you can do what you want
with them.
