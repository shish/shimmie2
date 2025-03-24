# Config in Shimmie

If you want your extension to be configurable, add a `config.php` in your extension folder, with contents like:

```php
<?php

declare(strict_types=1);

namespace Shimmie2;

final class MyExtensionConfig extends ConfigGroup
{
    // KEY will be used to group this config with other parts of the
    // extension (permissions, theme, etc)
    public const KEY = "my_extension";

    // ConfigMeta is used to build the Board Config screen.
    // The const name (DISPLAY_FOOS) is how to refer to the config in code.
    // The string name can be anything, but needs to be globally unique.
    #[ConfigMeta("Display Foos", ConfigType::BOOLEAN, default: true)]
    public const DISPLAY_FOOS = "my_extension_display_foos";

    // ... more consts with ConfigMeta annotations here ...
}
```

Once this file exists, if the extension is enabled, you should see a "My Extension" section in the board config screen, with a "Display Foos" checkbox.

Note that a general "extension is active" option is unnecessary (Eg putting a "show comments on posts" option on the "Post Comments" extension) - extensions should be enabled or disabled as a whole, via the Extension Manager.

Once the config exists, you can access it in your extension code like this:

```php
<?php
$displayFoos = Ctx::$config->get_bool(MyExtensionConfig::DISPLAY_FOOS);
```

There are a family of functions to get config values, following the format

```
$config->{get|req}_{bool|int|string|array}($key);
```

This family of functions will:
- if the config exists, return the value (cast to the appropriate type)
- else if the `ConfigMeta` has a default value, return that
- else `get` will return null, `req` will throw an exception

## Advanced Features

`ConfigGroup` subclasses can override `get_config_fields()` to add additional config options at runtime, eg the `transcode` extension will add a `from`/`to` converter for each currently supported filetype.

Extensions can call `set_{bool|int|string|array}($key, $value)` to manually set things in the global config, but using the default Board Config screen is preferred. An example of where this is useful is the "Featured Image" extension having a "feature this" button on each image, which will call

```php
<?php
Ctx::$config->set_int(FeaturedImageConfig::FEATURED_IMAGE_ID, $image->id);
```
