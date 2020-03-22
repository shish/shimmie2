```
     _________.__     .__                   .__         ________
    /   _____/|  |__  |__|  _____    _____  |__|  ____  \_____  \
    \_____  \ |  |  \ |  | /     \  /     \ |  |_/ __ \  /  ____/
    /        \|   Y  \|  ||  Y Y  \|  Y Y  \|  |\  ___/ /       \
   /_______  /|___|  /|__||__|_|  /|__|_|  /|__| \___  >\_______ \
           \/      \/           \/       \/          \/         \/

```

# Shimmie

[![Unit Tests](https://github.com/shish/shimmie2/workflows/Unit%20Tests/badge.svg)](https://github.com/shish/shimmie2/actions)
[![Code Quality](https://scrutinizer-ci.com/g/shish/shimmie2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shish/shimmie2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/shish/shimmie2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/shish/shimmie2/?branch=master)

This is the main branch of Shimmie, if you know anything at all about running
websites, this is the version to use.

Alternatively if you want a version that will never have significant changes,
check out one of the versioned branches.

# Requirements

- These are generally based on "whatever is in Debian Stable", because that's
  conservative without being TOO painfully out of date, and is a nice target
  for the unit test Docker build.
- A database: PostgreSQL 11+ / MariaDB 10.3+ / SQLite 3.27+
- [Stable PHP](https://en.wikipedia.org/wiki/PHP#Release_history) (7.3+ as of writing)
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
If you just want to run shimmie inside docker, there's a pre-built image
in dockerhub - `shish2k/shimmie2` - which can be used like:
```
docker run -p 8000 -v /my/hard/drive:/app/data shish2k/shimmie2
```

If you want to build your own image from source:
```
docker build -t shimmie .
```

There are various options settable with environment variables:
- `UID` / `GID` - which user ID to run as (default 1000/1000)
- `INSTALL_DSN` - specify a data source to install into, to skip the installer screen, eg
  `-e INSTALL_DSN="pgsql:user=shimmie;password=6y5erdfg;host=127.0.0.1;dbname=shimmie"`

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
