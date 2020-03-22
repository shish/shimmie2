# Custom Configuration

Various aspects of Shimmie can be configured to suit your site specific needs
via the file `data/config/shimmie.conf.php` (created after installation).

Take a look at `core/sys_config.php` for the available options that can
be used.


# Custom User Classes

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
