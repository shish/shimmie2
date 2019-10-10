<?php

class DanbooruApi extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("api") && ($event->get_arg(0) == 'danbooru')) {
            $this->api_danbooru($event);
        }
    }

    // Danbooru API
    private function api_danbooru(PageRequestEvent $event)
    {
        global $page;
        $page->set_mode(PageMode::DATA);

        if (($event->get_arg(1) == 'add_post') || (($event->get_arg(1) == 'post') && ($event->get_arg(2) == 'create.xml'))) {
            // No XML data is returned from this function
            $page->set_type("text/plain");
            $this->api_add_post();
        } elseif (($event->get_arg(1) == 'find_posts') || (($event->get_arg(1) == 'post') && ($event->get_arg(2) == 'index.xml'))) {
            $page->set_type("application/xml");
            $page->set_data($this->api_find_posts());
        } elseif ($event->get_arg(1) == 'find_tags') {
            $page->set_type("application/xml");
            $page->set_data($this->api_find_tags());
        }

        // Hackery for danbooruup 0.3.2 providing the wrong view url. This simply redirects to the proper
        // Shimmie view page
        // Example: danbooruup says the url is http://shimmie/api/danbooru/post/show/123
        // This redirects that to http://shimmie/post/view/123
        elseif (($event->get_arg(1) == 'post') && ($event->get_arg(2) == 'show')) {
            $fixedlocation = make_link("post/view/" . $event->get_arg(3));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect($fixedlocation);
        }
    }

    /**
     * Turns out I use this a couple times so let's make it a utility function
     * Authenticates a user based on the contents of the login and password parameters
     * or makes them anonymous. Does not set any cookies or anything permanent.
     */
    private function authenticate_user()
    {
        global $config, $user;

        if (isset($_REQUEST['login']) && isset($_REQUEST['password'])) {
            // Get this user from the db, if it fails the user becomes anonymous
            // Code borrowed from /ext/user
            $name = $_REQUEST['login'];
            $pass = $_REQUEST['password'];
            $duser = User::by_name_and_pass($name, $pass);
            if (!is_null($duser)) {
                $user = $duser;
            } else {
                $user = User::by_id($config->get_int("anon_id", 0));
            }
            send_event(new UserLoginEvent($user));
        }
    }

    /**
     * find_tags()
     * Find all tags that match the search criteria.
     *
     * Parameters
     * - id: A comma delimited list of tag id numbers.
     * - name: A comma delimited list of tag names.
     * - tags: any typical tag query. See Tag#parse_query for details.
     * - after_id: limit results to tags with an id number after after_id. Useful if you only want to refresh
     */
    private function api_find_tags(): string
    {
        global $database;
        $results = [];
        if (isset($_GET['id'])) {
            $idlist = explode(",", $_GET['id']);
            foreach ($idlist as $id) {
                $sqlresult = $database->get_all(
                    "SELECT id,tag,count FROM tags WHERE id = ?",
                    [$id]
                );
                foreach ($sqlresult as $row) {
                    $results[] = [$row['count'], $row['tag'], $row['id']];
                }
            }
        } elseif (isset($_GET['name'])) {
            $namelist = explode(",", $_GET['name']);
            foreach ($namelist as $name) {
                $sqlresult = $database->get_all($database->scoreql_to_sql(
                    "SELECT id,tag,count FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"),
                    [$name]
                );
                foreach ($sqlresult as $row) {
                    $results[] = [$row['count'], $row['tag'], $row['id']];
                }
            }
        }
        // Currently disabled to maintain identical functionality to danbooru 1.0's own "broken" find_tags
        elseif (false && isset($_GET['tags'])) {
            $start = isset($_GET['after_id']) ? int_escape($_GET['offset']) : 0;
            $tags = Tag::explode($_GET['tags']);
        } else {
            $start = isset($_GET['after_id']) ? int_escape($_GET['offset']) : 0;
            $sqlresult = $database->get_all(
                "SELECT id,tag,count FROM tags WHERE count > 0 AND id >= ? ORDER BY id DESC",
                [$start]
            );
            foreach ($sqlresult as $row) {
                $results[] = [$row['count'], $row['tag'], $row['id']];
            }
        }

        // Tag results collected, build XML output
        $xml = "<tags>\n";
        foreach ($results as $tag) {
            $xml .= xml_tag("tag", [
                "type" => "0",
                "counts" => $tag[0],
                "name" => $tag[1],
                "id" => $tag[2],
            ]);
        }
        $xml .= "</tags>";
        return $xml;
    }

    /**
     * find_posts()
     * Find all posts that match the search criteria. Posts will be ordered by id descending.
     *
     * Parameters:
     * - md5: md5 hash to search for (comma delimited)
     * - id: id to search for (comma delimited)
     * - tags: what tags to search for
     * - limit: limit
     * - page: page number
     * - after_id: limit results to posts added after this id
     *
     * #return string
     */
    private function api_find_posts()
    {
        $results = [];

        $this->authenticate_user();
        $start = 0;

        if (isset($_GET['md5'])) {
            $md5list = explode(",", $_GET['md5']);
            foreach ($md5list as $md5) {
                $results[] = Image::by_hash($md5);
            }
            $count = count($results);
        } elseif (isset($_GET['id'])) {
            $idlist = explode(",", $_GET['id']);
            foreach ($idlist as $id) {
                $results[] = Image::by_id($id);
            }
            $count = count($results);
        } else {
            $limit = isset($_GET['limit']) ? int_escape($_GET['limit']) : 100;

            // Calculate start offset.
            if (isset($_GET['page'])) { // Danbooru API uses 'page' >= 1
                $start = (int_escape($_GET['page']) - 1) * $limit;
            } elseif (isset($_GET['pid'])) { // Gelbooru API uses 'pid' >= 0
                $start = int_escape($_GET['pid']) * $limit;
            } else {
                $start = 0;
            }

            $tags = isset($_GET['tags']) ? Tag::explode($_GET['tags']) : [];
            $count = Image::count_images($tags);
            $results = Image::find_images(max($start, 0), min($limit, 100), $tags);
        }

        // Now we have the array $results filled with Image objects
        // Let's display them
        $xml = "<posts count=\"{$count}\" offset=\"{$start}\">\n";
        foreach ($results as $img) {
            // Sanity check to see if $img is really an image object
            // If it isn't (e.g. someone requested an invalid md5 or id), break out of the this
            if (!is_object($img)) {
                continue;
            }
            $taglist = $img->get_tag_list();
            $owner = $img->get_owner();
            $previewsize = get_thumbnail_size($img->width, $img->height);
            $xml .= xml_tag("post", [
                "id" => $img->id,
                "md5" => $img->hash,
                "file_name" => $img->filename,
                "file_url" => $img->get_image_link(),
                "height" => $img->height,
                "width" => $img->width,
                "preview_url" => $img->get_thumb_link(),
                "preview_height" => $previewsize[1],
                "preview_width" => $previewsize[0],
                "rating" => "?",
                "date" => $img->posted,
                "is_warehoused" => false,
                "tags" => $taglist,
                "source" => $img->source,
                "score" => 0,
                "author" => $owner->name
            ]);
        }
        $xml .= "</posts>";
        return $xml;
    }

    /**
     * add_post()
     * Adds a post to the database.
     *
     * Parameters:
     * - login: login
     * - password: password
     * - file: file as a multipart form
     * - source: source url
     * - title: title **IGNORED**
     * - tags: list of tags as a string, delimited by whitespace
     * - md5: MD5 hash of upload in hexadecimal format
     * - rating: rating of the post. can be explicit, questionable, or safe. **IGNORED**
     *
     * Notes:
     * - The only necessary parameter is tags and either file or source.
     * - If you want to sign your post, you need a way to authenticate your account, either by supplying login and password, or by supplying a cookie.
     * - If an account is not supplied or if it doesnt authenticate, he post will be added anonymously.
     * - If the md5 parameter is supplied and does not match the hash of whats on the server, the post is rejected.
     *
     * Response
     * The response depends on the method used:
     * Post:
     * - X-Danbooru-Location set to the URL for newly uploaded post.
     * Get:
     * - Redirected to the newly uploaded post.
     */
    private function api_add_post()
    {
        global $user, $page;
        $danboorup_kludge = 1;            // danboorup for firefox makes broken links out of location: /path

        // Check first if a login was supplied, if it wasn't check if the user is logged in via cookie
        // If all that fails, it's an anonymous upload
        $this->authenticate_user();
        // Now we check if a file was uploaded or a url was provided to transload
        // Much of this code is borrowed from /ext/upload

        if (!$user->can(Permissions::CREATE_IMAGE)) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: authentication error");
            return;
        }

        if (isset($_FILES['file'])) {    // A file was POST'd in
            $file = $_FILES['file']['tmp_name'];
            $filename = $_FILES['file']['name'];
            // If both a file is posted and a source provided, I'm assuming source is the source of the file
            if (isset($_REQUEST['source']) && !empty($_REQUEST['source'])) {
                $source = $_REQUEST['source'];
            } else {
                $source = null;
            }
        } elseif (isset($_FILES['post'])) {
            $file = $_FILES['post']['tmp_name']['file'];
            $filename = $_FILES['post']['name']['file'];
            if (isset($_REQUEST['post']['source']) && !empty($_REQUEST['post']['source'])) {
                $source = $_REQUEST['post']['source'];
            } else {
                $source = null;
            }
        } elseif (isset($_REQUEST['source']) || isset($_REQUEST['post']['source'])) {    // A url was provided
            $source = isset($_REQUEST['source']) ? $_REQUEST['source'] : $_REQUEST['post']['source'];
            $file = tempnam("/tmp", "shimmie_transload");
            $ok = transload($source, $file);
            if (!$ok) {
                $page->set_code(409);
                $page->add_http_header("X-Danbooru-Errors: fopen read error");
                return;
            }
            $filename = basename($source);
        } else {    // Nothing was specified at all
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: no input files");
            return;
        }

        // Get tags out of url
        $posttags = Tag::explode(isset($_REQUEST['tags']) ? $_REQUEST['tags'] : $_REQUEST['post']['tags']);

        // Was an md5 supplied? Does it match the file hash?
        $hash = md5_file($file);
        if (isset($_REQUEST['md5']) && strtolower($_REQUEST['md5']) != $hash) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: md5 mismatch");
            return;
        }
        // Upload size checking is now performed in the upload extension
        // It is also currently broken due to some confusion over file variable ($tmp_filename?)

        // Does it exist already?
        $existing = Image::by_hash($hash);
        if (!is_null($existing)) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: duplicate");
            $existinglink = make_link("post/view/" . $existing->id);
            if ($danboorup_kludge) {
                $existinglink = make_http($existinglink);
            }
            $page->add_http_header("X-Danbooru-Location: $existinglink");
            return;
        }

        // Fire off an event which should process the new file and add it to the db
        $fileinfo = pathinfo($filename);
        $metadata = [];
        $metadata['filename'] = $fileinfo['basename'];
        if (array_key_exists('extension', $fileinfo)) {
            $metadata['extension'] = $fileinfo['extension'];
        }
        $metadata['tags'] = $posttags;
        $metadata['source'] = $source;
        //log_debug("danbooru_api","========== NEW($filename) =========");
        //log_debug("danbooru_api", "upload($filename): fileinfo(".var_export($fileinfo,TRUE)."), metadata(".var_export($metadata,TRUE).")...");

        try {
            $nevent = new DataUploadEvent($file, $metadata);
            //log_debug("danbooru_api", "send_event(".var_export($nevent,TRUE).")");
            send_event($nevent);
            // If it went ok, grab the id for the newly uploaded image and pass it in the header
            $newimg = Image::by_hash($hash);        // FIXME: Unsupported file doesn't throw an error?
            $newid = make_link("post/view/" . $newimg->id);
            if ($danboorup_kludge) {
                $newid = make_http($newid);
            }

            // Did we POST or GET this call?
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $page->add_http_header("X-Danbooru-Location: $newid");
            } else {
                $page->add_http_header("Location: $newid");
            }
        } catch (UploadException $ex) {
            // Did something screw up?
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: exception - " . $ex->getMessage());
        }
    }
}
