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

Once this file exists, if the extension is enabled, you should see a "My Extension" section in the Board Config screen, with a "Display Foos" checkbox.

Note that a general "extension is active" option is unnecessary (Eg putting a "show comments on posts" option on the "Post Comments" extension) - extensions should be enabled or disabled as a whole, via the Extension Manager.

Once the config exists, you can access it in your extension code with `get`:

```php
<?php
$displayFoos = Ctx::$config->get(MyExtensionConfig::DISPLAY_FOOS);
```

(Note that `get` is typed as `mixed`, but we have a `phpstan` plugin which gets the appropriate type from the config metadata, eg `$displayFoos` above will have the type `bool`)

## Advanced Features

`ConfigGroup` subclasses can override `get_config_fields()` to add additional config options at runtime, eg the `transcode` extension will add a `from`/`to` converter for each currently supported filetype.

Extensions can call `set($key, $value)` to manually set things in the global config, but using the default Board Config screen is preferred. An example of where this is useful is the "Featured Image" extension having a "feature this" button on each image, which will call

```php
<?php
Ctx::$config->set(FeaturedImageConfig::FEATURED_IMAGE_ID, $image->id);
```
