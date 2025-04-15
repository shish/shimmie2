# Shimmie HTTP Responses

## Page

The class handling HTTP responses is `Page` (which ideally would've been called `Response`, but hindsight is 20/20) - it has several modes:

* The default mode is `PAGE` which generates an HTML page by merging all the `Block`s according to the current theme's layout code
  * When in page mode, several parts of the page can be customised:
    * `$page->set_title($text)` (this will also set the heading if the heading is empty, so that you can avoid needing to call both functions every time)
    * `$page->set_heading($text)`
    * `$page->set_layout($name)`
    * `$page->add_block($block)`
* `$page->set_redirect($url)` redirects the user to the given URL
* `$page->set_data($type, $data)` returns raw data
* `$page->set_file($type, $filename)` will output the given file from disk
* There are some other modes for niche and internal use-cases, but those cover 99% of what you need to know when writing extensions


## Themes

Theme customisation is done by creating files in `themes/<theme name>`.

The general idea with Shimmie theming is that each `Extension` will add a
set of `Block`s to the `Page`, then the `Page` is in charge of deciding
how they should be laid out, what they should look like, etc.

The overall layout is controlled by `page.class.php`, where the `body_html()`
function will take a look at all of the separate `Block`s and turn them
into the final rendered HTML.

Individual `Extension`s will render their content by calling functions
in `ext/<extension name>/theme.php` - for example the code in
`ext/comment/main.php` will display a list of comments by calling
`display_comment_list()` from `ext/comment/theme.php`.

If a theme wants to customise how the comment list is rendered, it would
do so by creating an override file in `themes/<theme name>/comment.theme.php`
with contents like:

```php
<?php
// themes/theme_name/comment.theme.php
class ThemeNameCommentTheme extends CommentTheme {
    public function display_comment_list(
        array $images,
        int $page_number,
        int $total_pages,
        bool $can_post
    ) {
        /* render the comment list however you like here */
    }
}
```
