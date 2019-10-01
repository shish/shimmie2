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
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/shish/shimmie2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shish/shimmie2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/shish/shimmie2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/shish/shimmie2/?branch=master)
(master)

This is the main branch of Shimmie, if you know anything at all about running
websites, this is the version to use.

Alternatively if you want a version that will never have significant changes,
check out one of the versioned branches.

# Requirements

- MySQL/MariaDB 5.1+ (with experimental support for PostgreSQL 9+ and SQLite 3)
- [Stable PHP](https://en.wikipedia.org/wiki/PHP#Release_history) (7.1+ as of writing)
- GD or ImageMagick

# Installation

1. Download the latest release under [Releases](https://github.com/shish/shimmie2/releases).
2. Unzip shimmie into a folder on the web host
3. Create a blank database
4. Visit the folder with a web browser
5. Enter the location of the database
6. Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~

# Installation (Development)

1. Download shimmie via the "Download Zip" button on the [master](https://github.com/shish/shimmie2/tree/master) branch.
2. Unzip shimmie into a folder on the web host
3. Install [Composer](https://getcomposer.org/). (If you don't already have it)
4. Run `composer install` in the shimmie folder.
5. Follow instructions noted in "Installation" starting from step 3.

# Docker

Useful for testing in a known-good environment, this command will build a
simple debian image and run all the unit tests inside it:

```
docker build -t shimmie .
```

Once you have an image which has passed all tests, you can then run it to get
a live system:

```
docker run -p 0.0.0.0:8123:8000 shimmie
```

Then you can visit your server on port 8123 to see the site.

Note that the docker image is entirely self-contained and has no persistence
(assuming you use the sqlite database); each `docker run` will give a clean
un-installed image.

### Upgrade from earlier versions

I very much recommend going via each major release in turn (eg, 2.0.6
-> 2.1.3 -> 2.2.4 -> 2.3.0 rather than 2.0.6 -> 2.3.0).

While the basic database and file formats haven't changed *completely*, it's
different enough to be a pain.


## Custom Configuration

Various aspects of Shimmie can be configured to suit your site specific needs
via the file `data/config/shimmie.conf.php` (created after installation).

Take a look at `core/sys_config.php` for the available options that can
be used.


#### Custom User Classes

User classes can be added to or altered by placing them in
`data/config/user-classes.conf.php`.

For example, one can override the default anonymous "allow nothing"
permissions like so:

```php
new UserClass("anonymous", "base", [
	Permissions::CREATE_COMMENT => True,
	Permissions::EDIT_IMAGE_TAG => True,
	Permissions::EDIT_IMAGE_SOURCE => True,
	Permissions::CREATE_IMAGE_REPORT => True,
]);
```

For a moderator class, being a regular user who can delete images and comments:

```php
new UserClass("moderator", "user", [
	Permissions::DELETE_IMAGE => True,
	Permissions::DELETE_COMMENT => True,
]);
```

For a list of permissions, see `core/permissions.php`


# Development Info

ui-\* cookies are for the client-side scripts only; in some configurations
(eg with varnish cache) they will be stripped before they reach the server

shm-\* CSS classes are for javascript to hook into; if you're customising
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

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them


# Contact

Email: webmaster at shishnet.org

Issue/Bug tracker: http://github.com/shish/shimmie2/issues


# Licence

All code is released under the [GNU GPL Version 2](http://www.gnu.org/licenses/gpl-2.0.html) unless mentioned otherwise.

If you give shimmie to someone else, you have to give them the source (which
should be easy, as PHP is an interpreted language...). If you want to add
customisations to your own site, then those customisations belong to you,
and you can do what you want with them.
