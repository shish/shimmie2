# Upgrading old versions

## Get the new code (from git)

If you got shimmie from `git`, then `git pull` should fetch the latest code
(`git checkout master` then `git pull` if you're on an old non-master branch).

Once the new Shimmie code is ready, you'll need to make sure all the
dependencies are in place and up-to-date via `composer install`.


## Get the new code (.zip)

If you got shimmie from one of the .zip downloads, you'll need to download
new code, extract it, then copy across the `data` folder from the old install
into the new one.


# Update database schema

This should be automatic - next time the site is loaded, it'll see that the
current schema is out of date, and will update it.
