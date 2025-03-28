# Users and Permissions in Shimmie

## Site Admin

Users are grouped into "classes" (User, Admin, Moderator, etc), and each class has a set of permissions assigned via the Permission Manager. A user's class can be changed by an admin via their profile page.

User Classes form a tree, where each class will inherit permissions from its parent if it doesn't have its own permission defined. For example, "Moderator" might be defined as "User, plus the ability to delete posts".

There are two built-in and un-editable classes: "Base" (which has no access to anything, not even able to sign up for an account), and "Admin" (which has access to everything).

## Dev Guide

Permissions are defined in each extension's `permissions.php` file, like:

```php
<?php

declare(strict_types=1);

namespace Shimmie2;

final class MyExtensionPermission extends PermissionGroup
{
    // KEY will be used to group this config with other parts of the
    // extension (permissions, theme, etc)
    public const KEY = "my_extension";

    // PermissionMeta is used to build the Permission Manager screen.
    // The const name (DELETE_FOOS) is how to refer to the config in code.
    // The string name can be anything, but needs to be globally unique.
    #[PermissionMeta("Admin")]
    public const DELETE_FOOS = "my_extension_delete_foos";

    // ... more consts with PermissionMeta annotations here ...
}
```

Shimmie sets a `$user` variable containing a `User` object, which is mostly useful for finding out what the currently-logged-in user can do, eg

```php
<?php
if(Ctx::$user->can(MyExtensionPermission::DELETE_FOOS)) {
    // show a "delete foo" button
}
```
