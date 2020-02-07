Notes for any sites which require extra performance
===================================================

Image Serving
-------------
Firstly, make sure your webserver is configured properly and nice URLs are
enabled, so that images will be served straight from disk by the webserver
instead of via PHP. If you're serving images via PHP, then your site might
melt under the load of 5 concurrent users...

`SPEED_HAX`
-----------
Setting this to true will make a bunch of changes which reduce the correctness
of the software and increase admin workload for the sake of speed. You almost
certainly don't want to set this, but if you do (eg you're trying to run a
site with 10,000 concurrent users on a single server), it can be a huge help.

Notable behaviour changes:

- Database schema upgrades are no longer automatic; you'll need to run
  `php index.php db-upgrade` from the CLI each time you update the code.
- Mapping from Events to Extensions is cached - you'll need to delete
  `data/cache/shm_event_listeners.php` after each code change, and after
  enabling or disabling any extensions.
- Tag lists (eg alphabetic, popularity, map) are cached and you'll need
  to delete them manually when you feel like it
- Anonymous users can only search for 3 tags at once
- We only show the first 500 pages of results for any query, except for
  the most simple (no tags, or one positive tag)
- We only ever show the first 5,000 results for complex queries
- `ParseLinkTemplateEvent` is disabled
- Only comments from the past 24 hours show up in /comment/list
- Web crawlers are blocked from creating too many nonsense searches
- The first 10 pages in the index get extra caching
- RSS is limited to 10 pages
- HTML for thumbnails is cached

`WH_SPLITS`
-----------
Store files as `images/ab/cd/...` instead of `images/ab/...`, which can
reduce filesystem load when you have millions of images.

Multiple Image Servers
----------------------
Image links don't have to be `/images/$hash.$ext` on the local server, they
can be full URLs, and include weighted random parts, eg:

`https://{fred=3,leo=1}.mysite.com/images/$hash.$ext` - the software will then
use consistent hashing to map 75% of the files to `fred.mysite.com` and 25% to
`leo.mysite.com` - then you can install Varnish or Squid or something as a
caching reverse-proxy.

Profiling
---------
`define()`'ing `TRACE_FILE` to a filename and `TRACE_THRESHOLD` to a number
of seconds will result in JSON event traces being dumped into that file
whenever a page takes longer than the threshold to load. These traces can
then be loaded into the chrome trace viewer (chrome://tracing/) and you'll
get a breakdown of page performance by extension, event, database, and cache
queries.
