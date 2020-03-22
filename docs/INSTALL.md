# Requirements

- These are generally based on "whatever is in Debian Stable", because that's
  conservative without being TOO painfully out of date, and is a nice target
  for the unit test Docker build.
- A database: PostgreSQL 11+ / MariaDB 10.3+ / SQLite 3.27+
- [Stable PHP](https://en.wikipedia.org/wiki/PHP#Release_history) (7.3+ as of writing)
- GD or ImageMagick

# Get the Code

Two main options:

1. Via Git (allows easiest updates via `git pull`):
  * `git clone https://github.com/shish/shimmie2`
  * Install [Composer](https://getcomposer.org/). (If you don't already have it)
  * Run `composer install` in the shimmie folder.
2. Via Stable Release:
  * Download the latest release under [Releases](https://github.com/shish/shimmie2/releases).

# Install

1. Create a blank database
2. Visit the install folder with a web browser
3. Enter the location of the database
4. Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~
