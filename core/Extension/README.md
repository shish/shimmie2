Base classes for extensions to inherit from

* `Extension` - The most generic case, use for most things
* `AvatarExtension` - For providing avatars (eg `GravatarAvatar`, `PostAvatar`)
* `DataHandlerExtension` - For upload handlers, eg `HandleVideo`, `HandleMP3`, `HandleCBZ`
* `FormatterExtension` - For formatting user-provided text, eg `BBCode`

Utility bits

* `ExtensionInfo` - Simple static metadata used by the Extension Manager
  * `ExtensionCategory`
  * `ExtensionVisibility`
* `Enablable` - You shouldn't need to use this directly, it's a base class
  for other base classes (`Extension`, `Themelet`, `ConfigGroup`) which
  allows certain classes to be enabled or disabled as a group (eg enabling
  `Forum`, `ForumConfigGroup`, `ForumPermissions`, and `ForumTheme`)
