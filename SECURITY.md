# Security Policy

## Supported Versions

* The largest-numbered version from https://github.com/shish/shimmie2/releases
  * Not necessarily "the most recent release" - it's possible that eg 2.11.X gets a new patch release if somebody pays me to backport a fix; but if 2.12.X exists, 2.12.X is still "the supported version".
* The current `main` branch and associated nightly builds

## Reporting a Vulnerability

Please email `shish+security@shishnet.org`, and be aware that I am one person, not a corporation. I do my best to respond ASAP, but can't offer guarantees, and I have zero budget to pay bug bounties.

## Security Goals

(A first draft of "things which are _definitely_ considered security issues", but this is not an exhaustive list - other things may also be security issues)

* The first user to sign up to a site is marked as "admin" by default, other users are "user" by default. It is expected that admins have full control over the site, including the power to break it, equivalent to somebody with shell access to the server. Users should not have access to any admin features unless explicitly granted by an existing admin via the permission system.
* No user should be able to execute arbitrary PHP code or system commands on the server
* No user should be able to read or write arbitrary files on the server
  * Writes should be limited to OS-specific temporary directories (eg `/tmp` on Unix-like systems) and Shimmie's own `data` directory
  * If a sys-admin manually creates a symlink in the data directory, shimmie will follow it, by design - this allows admins to eg have `data/cache` stored on a different hard drive to `data/images`
* No user should be able to write non-standard files (symlinks, device nodes, pipes) anywhere on the filesystem
* No user should be able to execute arbitrary SQL queries on the server
* No user should be able to post HTML, CSS, or JavaScript to the server and have it rendered as part of the site's content
* Nothing a user posts should be able to permanently break any part of the site (eg, posting a badly formatted comment shouldn't cause the entire forum subsystem to return 503 errors)
* If somebody with sys-admin access changes the code (eg by adding a custom extension, or by altering the database schema), all bets are off

## Known Weaknesses / Compromises

* Be aware that shimmie was originally designed for fully-public-by-default image galleries, and the option to mark certain posts as "hidden" (eg "only show 18+ rated content to logged-in users") was bolted on later, without a ground-up redesign. While post-hiding _generally_ works well to prevent casual browsers from seeing hidden content by accident, this should not be considered a security feature -- if you are dealing with sensitive content (legal documents, medical records, etc), you should have two separate instances of shimmie running: one for public content, and one for private content (locked behind a web server login or VPN), rather than mixing public and private content on the same instance.
  * Post attachments are stored in a content-addressable-storage directory. This means that if somebody _already has_ a given file, they can take the hash of the file, and check `https://server/_images/<hash>` to see if that file has been uploaded to the server, even if the post which the file is attached to was flagged as "hidden" in the database.
  * Requests to files in `data/images` are not authenticated - if somebody with permission to view a given post shares the URL of the attached image, other people can use that URL to view the image, even if the post it is attached to is hidden.
  * Various parts of the interface count total numbers of posts (eg "Bob has uploaded 42 posts", "there are 123 posts tagged with 'cat'") - these counts will generally include hidden posts, so if an attacker sees "there are 123 cat posts" but only sees 120 search results, they can infer the existence of 3 hidden cat posts.
* Post IDs are sequential - if an attacker sees that post #42 is followed by post #44, they can infer that post #43 existed at some point.
  * Knowing that a post "existed at some point" shouldn't tell the attacker anything about the _content_ of that post
  * Manually visiting "/post/view/43" should show a 404 error, whether the post is deleted or hidden.
* Post attachments are hashed with md5, which is known to be a weak hash
  * The _way_ that shimmie uses md5 is believed to be safe, as it isn't used in any security context. It is used to generate unique filenames for uploads. In the event of a hash collision, the system fails closed -- the new file will be rejected, existing files will be left alone.
* User profiles and basic data (eg the user's avatar, a count of how many posts they've uploaded) are public
  * Personal data like email address should only be visible to the user themselves and admins
* Rate-limiting is expected to be enforced at a higher level than the application (ie at the firewall, load balancer, or web server).
  * While no single request should be able to use more than a small amount of resources (CPU, memory, etc.), shimmie contains nothing to prevent an attacker from overwhelming the server with many requests at once.
* Passwords are hashed with bcrypt, but all other data is plaintext
  * It is assumed that the operating system will provide disk encryption, and the web server will provide network encryption.
  * It is assumed that OS level security policies are in place (eg a shared web host should be configured so that users can't browse each other's files)
