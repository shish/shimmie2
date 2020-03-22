# Development Info

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

Please tell me if those docs are lacking in any way, so that they can be
improved for the next person who uses them
