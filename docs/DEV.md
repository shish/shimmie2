# Development Info

## Themes

Theme customisation is done by creating files in `themes/<theme name>`.

The general idea with Shimmie theming is that each `Extension` will add a
set of `Block`s to the `Page`, then the `Page` is in charge of deciding
how they should be laid out, what they should look like, etc.

The overall layout is controlled by `page.class.php`, where the `render()`
function will take a look at all of the separate `Block`s and turn them
into the final rendered HTML.

Individual `Extension`s will render their content by calling functions
in `ext/<extension name>/theme.php` - for example the code in
`ext/comment/main.php` will display a list of comments by calling
`display_comment_list()` from `ext/comment/theme.php`.

If a theme wants to customise how the comment list is rendered, it would
do so by creating an override file in `themes/<theme name>/comment.theme.php`
with contents like:

```
class CustomCommentTheme extends CommentTheme {
    public function display_comment_list(
		array $images,
		int $page_number,
		int $total_pages,
		bool $can_post
	) {
	    [... render the comment list however you like here ...]
	}
}
```


## Events and Extensions

An event is a little blob of data saying "something happened", possibly
"something happened, here's the specific data". Events are sent with the
`send_event()` function. Since events can store data, they can be used to
return data to the extension which sent them, for example:

```
$tfe = send_event(new TextFormattingEvent($original_text));
$formatted_text = $tfe->formatted;
```

An extension is something which is capable of reacting to events.


### Useful Variables

There are a few global variables which are pretty essential to most extensions:

* $config -- some variety of Config subclass
* $database -- a Database object used to get raw SQL access
* $page -- a Page to holds all the loose bits of extension output
* $user -- the currently logged in User
* $cache -- an optional cache for fast key / value lookups (eg Memcache)

Each of these can be imported at the start of a function with eg "global $page, $user;"


### The Hello World Extension

Here's a simple extension which listens for `PageRequestEvent`s, and each time
it sees one, it sends out a `HelloEvent`.

```
// ext/hello/main.php
public class HelloEvent extends Event {
    public function __construct($username) {
        $this->username = $username;
    }
}

public class Hello extends Extension {
    public function onPageRequest(PageRequestEvent $event) {   // Every time a page request is sent
        global $user;                                          // Look at the global "currently logged in user" object
        send_event(new HelloEvent($user->name));               // Broadcast a signal saying hello to that user
    }
    public function onHello(HelloEvent $event) {               // When the "Hello" signal is recieved
        $this->theme->display_hello($event->username);         // Display a message on the web page
    }
}
```

```
// ext/hello/theme.php
public class HelloTheme extends Themelet {
    public function display_hello($username) {
        global $page;
        $h_user = html_escape($username);                     // Escape the data before adding it to the page
        $block = new Block("Hello!", "Hello there $h_user");  // HTML-safe variables start with "h_"
        $page->add_block($block);                             // Add the block to the page
    }
}
```

```
// themes/mytheme/hello.theme.php
public class CustomHelloTheme extends HelloTheme {     // CustomHelloTheme overrides HelloTheme
    public function display_hello($username) {         // the display_hello() function is customised
        global $page;
        $h_user = html_escape($username);
        $page->add_block(new Block(
            "Hello!",
            "Hello there $h_user, look at my snazzy custom theme!"
        );
    }
}
```


## Cookies

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


## Fin

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them
