## Extension Context

Shimmie has historically used global variables to hold some things that pretty much every extension needs (config, database, logged in user, etc) - but that doesn't play well with automated testing or static analysis tools. As of 2.12 there is a new `Ctx` class which wraps the global Shimmie context in a _slightly_-less-bad way, so instead of `global $config; $config->get(...);` you can do `Ctx::$config->get(...);`.

## Base Classes for Extensions

There are several categories of extensions which have a lot in common, so some base classes have been added to make life easier:

* `Extension` - The most generic case, use for most things
* `AvatarExtension` - For providing avatars (eg `GravatarAvatar`, `PostAvatar`)
* `DataHandlerExtension` - For upload handlers, eg `HandleVideo`, `HandleMP3`, `HandleCBZ`
* `FormatterExtension` - For formatting user-provided text, eg `BBCode`

## Utility bits

* `ExtensionInfo` - Simple static metadata used by the Extension Manager
  * `ExtensionCategory`
  * `ExtensionVisibility`
* `Enablable` - You shouldn't need to use this directly, it's a base class
  for other base classes (`Extension`, `Themelet`, `ConfigGroup`) which
  allows certain classes to be enabled or disabled as a group (eg enabling
  `Forum`, `ForumConfigGroup`, `ForumPermissions`, and `ForumTheme`)
