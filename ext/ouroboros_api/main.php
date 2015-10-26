<?php

/*
 * Name: Ouroboros API
 * Author: Diftraku <diftraku[at]derpy.me>
 * Description: Ouroboros-like API for Shimmie
 * Version: 0.2
 * Documentation:
 *   Currently working features
 *   <ul>
 *     <li>Post:
 *       <ul>
 *         <li>Index/List</li>
 *         <li>Show</li>
 *         <li>Create</li>
 *       </ul>
 *     </li>
 *     <li>Tag:
 *       <ul>
 *         <li>Index/List</li>
 *       </ul>
 *     </li>
 *   </ul>
 *   Tested to work with CartonBox using "Danbooru 1.18.x" as site type.
 *   Does not work with Andbooru or Danbooru Gallery for reasons beyond me, took me a while to figure rating "u" is bad...
 *   Lots of Ouroboros/Danbooru specific values use their defaults (or what I gathered them to be default)
 *   and tons of stuff not supported directly in Shimmie is botched to work
 */


class _SafeOuroborosImage
{
    /**
     * Author
     */

    /**
     * Post author
     * @var string
     */
    public $author = '';
    /**
     * Post author user ID
     * @var integer
     */
    public $creator_id = null;

    /**
     * Image
     */

    /**
     * Image height
     * @var integer
     */
    public $height = null;
    /**
     * Image width
     * @var integer
     */
    public $width = null;
    /**
     * File Size in bytes
     * @var integer
     */
    public $file_size = null;
    /**
     * URL to the static file
     * @var string
     */
    public $file_url = '';
    /**
     * File MD5 hash
     * @var string
     */
    public $md5 = '';

    /**
     * Post Meta
     */

    /**
     * (Unknown) Change
     * @var integer
     */
    public $change = null;
    /**
     * Timestamp for post creation
     * @var integer
     */
    public $created_at = null;
    /**
     * Post ID
     * @var integer
     */
    public $id = null;
    /**
     * Parent post ID
     * @var integer
     */
    public $parent_id = null;
    /**
     * Post content rating
     * @var string
     */
    public $rating = 'q';
    /**
     * Post score
     * @var integer
     */
    public $score = 1;
    /**
     * Post source
     * @var string
     */
    public $source = '';
    /**
     * Post status
     * @var string
     */
    public $status = '';
    /**
     * Post tags
     * @var string
     */
    public $tags = 'tagme';
    /**
     * Flag if the post has child posts
     * @var bool
     */
    public $has_children = false;
    /**
     * Flag if the post has comments
     * @var bool
     */
    public $has_comments = false;
    /**
     * Flag if the post has notes
     * @var bool
     */
    public $has_notes = false;
    /**
     * Post description
     * @var string
     */
    public $description = '';

    /**
     * Thumbnail
     */

    /**
     * Thumbnail Height
     * @var integer
     */
    public $preview_height = null;
    /**
     * Thumbnail URL
     * @var string
     */
    public $preview_url = '';
    /**
     * Thumbnail Width
     * @var integer
     */
    public $preview_width = null;

    /**
     * Downscaled Image
     */

    /**
     * Downscaled image height
     * @var integer
     */
    public $sample_height = null;
    /**
     * Downscaled image
     * @var string
     */
    public $sample_url = '';
    /**
     * Downscaled image
     * @var integer
     */
    public $sample_width = null;

    /**
     * Constructor
     * @param Image $img
     */
    function __construct(Image $img)
    {
        global $config;
        // author
        $author = $img->get_owner();
        $this->author = $author->name;
        $this->creator_id = intval($author->id);

        // file
        $this->height = intval($img->height);
        $this->width = intval($img->width);
        $this->file_ext = $img->ext;
        $this->file_size = intval($img->filesize);
        $this->file_url = make_http($img->get_image_link());
        $this->md5 = $img->hash;

        // meta
        $this->change = intval($img->id); //DaFug is this even supposed to do? ChangeID?
        // Should be JSON specific, just strip this when converting to XML
        $this->created_at = array('n' => 123456789, 's' => strtotime($img->posted), 'json_class' => 'Time');
        $this->id = intval($img->id);
        $this->parent_id = null;
        if (defined('ENABLED_EXTS')) {
            if (strstr(ENABLED_EXTS, 'rating') !== false) {
                // 'u' is not a "valid" rating
                if ($img->rating == 's' || $img->rating == 'q' || $img->rating == 'e') {
                    $this->rating = $img->rating;
                }
            }
            if (strstr(ENABLED_EXTS, 'numeric_score') !== false) {
                $this->score = $img->numeric_score;
            }
        }
        $this->source = $img->source;
        $this->status = 'active'; //not supported in Shimmie... yet
        $this->tags = $img->get_tag_list();
        $this->has_children = false;
        $this->has_comments = false;
        $this->has_notes = false;

        // thumb
        $this->preview_height = $config->get_int('thumb_height');
        $this->preview_width = $config->get_int('thumb_width');
        $this->preview_url = make_http($img->get_thumb_link());

        // sample (use the full image here)
        $this->sample_height = intval($img->height);
        $this->sample_width = intval($img->width);
        $this->sample_url = make_http($img->get_image_link());
    }
}

class OuroborosPost extends _SafeOuroborosImage
{
    /**
     * Multipart File
     * @var array
     */
    public $file = array();

    /**
     * Create with rating locked
     * @var bool
     */
    public $is_rating_locked = false;

    /**
     * Create with notes locked
     * @var bool
     */
    public $is_note_locked = false;


    /**
     * Initialize an OuroborosPost for creation
     * Mainly just acts as a wrapper and validation layer
     * @param   array   $post
     * @param   string  $md5
     */
    public function __construct(array $post, $md5 = '')
    {
        if (array_key_exists('tags', $post)) {
            $this->tags = Tag::implode(
                array_map(
                    array('Tag', 'sanitise'),
                    Tag::explode(urldecode($post['tags']))
                )
            );
        }
        if (array_key_exists('file', $post)) {
            if (!is_null($post['file'])) {
                assert(is_array($post['file']));
                assert(array_key_exists('tmp_name', $post['file']));
                assert(array_key_exists('name', $post['file']));
                $this->file = $post['file'];
            }
        }
        if (array_key_exists('rating', $post)) {
            assert(
                $post['rating'] == 's' ||
                $post['rating'] == 'q' ||
                $post['rating'] == 'e'
            );
            $this->rating = $post['rating'];
        }
        if (array_key_exists('source', $post)) {
            $this->file_url = filter_var(
                urldecode($post['source']),
                FILTER_SANITIZE_URL
            );
        }
        if (array_key_exists('sourceurl', $post)) {
            $this->source = filter_var(
                urldecode($post['sourceurl']),
                FILTER_SANITIZE_URL
            );
        }
        if (array_key_exists('description', $post)) {
            $this->description = filter_var(
                $post['description'],
                FILTER_SANITIZE_STRING
            );
        }
        if (array_key_exists('is_rating_locked', $post)) {
            assert(
                $post['is_rating_locked'] == 'true' ||
                $post['is_rating_locked'] == 'false' ||
                $post['is_rating_locked'] == '1' ||
                $post['is_rating_locked'] == '0'
            );
            $this->is_rating_locked = $post['is_rating_locked'];
        }
        if (array_key_exists('is_note_locked', $post)) {
            assert(
                $post['is_note_locked'] == 'true' ||
                $post['is_note_locked'] == 'false' ||
                $post['is_note_locked'] == '1' ||
                $post['is_note_locked'] == '0'
            );
            $this->is_note_locked = $post['is_note_locked'];
        }
        if (array_key_exists('parent_id', $post)) {
            $this->parent_id = filter_var(
                $post['parent_id'],
                FILTER_SANITIZE_NUMBER_INT
            );
        }
    }
}

class _SafeOuroborosTag
{
    public $ambiguous = false;
    public $count = 0;
    public $id = 0;
    public $name = '';
    public $type = 0;

    function __construct(array $tag)
    {
        $this->count = $tag['count'];
        $this->id = $tag['id'];
        $this->name = $tag['tag'];
    }
}

class OuroborosAPI extends Extension
{
    private $event;
    private $type;

    const HEADER_HTTP_200 = 'OK';
    const MSG_HTTP_200 = 'Request was successful';

    const HEADER_HTTP_403 = 'Forbidden';
    const MSG_HTTP_403 = 'Access denied';

    const HEADER_HTTP_404 = 'Not found';
    const MSG_HTTP_404 = 'Not found';

    const HEADER_HTTP_418 = 'I\'m a teapot';
    const MSG_HTTP_418 = 'Short and stout';

    const HEADER_HTTP_420 = 'Invalid Record';
    const MSG_HTTP_420 = 'Record could not be saved';

    const HEADER_HTTP_421 = 'User Throttled';
    const MSG_HTTP_421 = 'User is throttled, try again later';

    const HEADER_HTTP_422 = 'Locked';
    const MSG_HTTP_422 = 'The resource is locked and cannot be modified';

    const HEADER_HTTP_423 = 'Already Exists';
    const MSG_HTTP_423 = 'Resource already exists';

    const HEADER_HTTP_424 = 'Invalid Parameters';
    const MSG_HTTP_424 = 'The given parameters were invalid';

    const HEADER_HTTP_500 = 'Internal Server Error';
    const MSG_HTTP_500 = 'Some unknown error occurred on the server';

    const HEADER_HTTP_503 = 'Service Unavailable';
    const MSG_HTTP_503 = 'Server cannot currently handle the request, try again later';

    const ERROR_POST_CREATE_MD5 = 'MD5 mismatch';
    const ERROR_POST_CREATE_DUPE = 'Duplicate';
    const OK_POST_CREATE_UPDATE = 'Updated';

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if (preg_match("%\.(xml|json)$%", implode('/', $event->args), $matches) === 1) {
            $this->event = $event;
            $this->type = $matches[1];
            if ($this->type == 'json') {
                $page->set_type('application/json; charset=utf-8');
            } elseif ($this->type == 'xml') {
                $page->set_type('text/xml; charset=utf-8');
            }
            $page->set_mode('data');
            $this->tryAuth();

            if ($event->page_matches('post')) {
                if ($this->match('create')) {
                    // Create
                    if ($user->can("create_image")) {
                        $md5 = !empty($_REQUEST['md5']) ? filter_var($_REQUEST['md5'], FILTER_SANITIZE_STRING) : null;
                        $this->postCreate(new OuroborosPost($_REQUEST['post']), $md5);
                    } else {
                        $this->sendResponse(403, 'You cannot create new posts');
                    }

                } elseif ($this->match('update')) {
                    // Update
                    //@todo add post update
                } elseif ($this->match('show')) {
                    // Show
                    $id = !empty($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : null;
                    $this->postShow($id);
                } elseif ($this->match('index') || $this->match('list')) {
                    // List
                    $limit = !empty($_REQUEST['limit']) ? intval(
                        filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 45;
                    $p = !empty($_REQUEST['page']) ? intval(
                        filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 1;
                    $tags = !empty($_REQUEST['tags']) ? filter_var($_REQUEST['tags'], FILTER_SANITIZE_STRING) : array();
                    if (!empty($tags)) {
                        $tags = Tag::explode($tags);
                    }
                    $this->postIndex($limit, $p, $tags);
                }
            } elseif ($event->page_matches('tag')) {
                if ($this->match('index') || $this->match('list')) {
                    $limit = !empty($_REQUEST['limit']) ? intval(
                        filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 50;
                    $p = !empty($_REQUEST['page']) ? intval(
                        filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 1;
                    $order = (!empty($_REQUEST['order']) && ($_REQUEST['order'] == 'date' || $_REQUEST['order'] == 'count' || $_REQUEST['order'] == 'name')) ? filter_var(
                        $_REQUEST['order'],
                        FILTER_SANITIZE_STRING
                    ) : 'date';
                    $id = !empty($_REQUEST['id']) ? intval(
                        filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT)
                    ) : null;
                    $after_id = !empty($_REQUEST['after_id']) ? intval(
                        filter_var($_REQUEST['after_id'], FILTER_SANITIZE_NUMBER_INT)
                    ) : null;
                    $name = !empty($_REQUEST['name']) ? filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING) : '';
                    $name_pattern = !empty($_REQUEST['name_pattern']) ? filter_var(
                        $_REQUEST['name_pattern'],
                        FILTER_SANITIZE_STRING
                    ) : '';
                    $this->tagIndex($limit, $p, $order, $id, $after_id, $name, $name_pattern);
                }
            }
        } elseif ($event->page_matches('post/show')) {
            $page->set_mode('redirect');
            $page->set_redirect(make_link(str_replace('post/show', 'post/view', implode('/', $event->args))));
            $page->display();
            die();
        }

    }

    /**
     * Post
     */

    /**
     * Wrapper for post creation
     * @param OuroborosPost $post
     * @param string $md5
     */
    protected function postCreate(OuroborosPost $post, $md5 = '')
    {
        global $config;
        $handler = $config->get_string("upload_collision_handler");
        if (!empty($md5) && !($handler == 'merge')) {
            $img = Image::by_hash($md5);
            if (!is_null($img)) {
                $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                return;
            }
        }
        $meta = array();
        $meta['tags'] = $post->tags;
        $meta['source'] = $post->source;
        if (defined('ENABLED_EXTS')) {
            if (strstr(ENABLED_EXTS, 'rating') !== false) {
                $meta['rating'] = $post->rating;
            }
        }
        // Check where we should try for the file
        if (empty($post->file) && !empty($post->file_url) && filter_var(
                $post->file_url,
                FILTER_VALIDATE_URL
            ) !== false
        ) {
            // Transload from source
            $meta['file'] = tempnam('/tmp', 'shimmie_transload_' . $config->get_string('transload_engine'));
            $meta['filename'] = basename($post->file_url);
            if (!transload($post->file_url, $meta['file'])) {
                $this->sendResponse(500, 'Transloading failed');
                return;
            }
            $meta['hash'] = md5_file($meta['file']);
        } else {
            // Use file
            $meta['file'] = $post->file['tmp_name'];
            $meta['filename'] = $post->file['name'];
            $meta['hash'] = md5_file($meta['file']);
        }
        if (!empty($md5) && $md5 !== $meta['hash']) {
            $this->sendResponse(420, self::ERROR_POST_CREATE_MD5);
            return;
        }
        if (!empty($meta['hash'])) {
            $img = Image::by_hash($meta['hash']);
            if (!is_null($img)) {
                $handler = $config->get_string("upload_collision_handler");
                if($handler == "merge") {
                    $merged = array_merge(Tag::explode($post->tags), $img->get_tag_array());
                    send_event(new TagSetEvent($img, $merged));

                    // This is really the only thing besides tags we should care
                    if(isset($meta['source'])){
                        send_event(new SourceSetEvent($img, $meta['source']));
                    }
                    $this->sendResponse(200, self::OK_POST_CREATE_UPDATE . ' ID: ' . $img->id);
                    return;
                }
                else {
                    $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                    return;
                }
            }
        }
        $meta['extension'] = pathinfo($meta['filename'], PATHINFO_EXTENSION);
        try {
            $upload = new DataUploadEvent($meta['file'], $meta);
            send_event($upload);
            $image = Image::by_hash($meta['hash']);
            if (!is_null($image)) {
                $this->sendResponse(200, make_link('post/view/' . $image->id), true);
                return;
            } else {
                // Fail, unsupported file?
                $this->sendResponse(500, 'Unknown error');
                return;
            }
        } catch (UploadException $e) {
            // Cleanup in case shit hit the fan
            $this->sendResponse(500, $e->getMessage());
            return;
        }
    }

    /**
     * Wrapper for getting a single post
     * @param int $id
     */
    protected function postShow($id = null)
    {
        if (!is_null($id)) {
            $post = new _SafeOuroborosImage(Image::by_id($id));
            $this->sendData('post', $post);
        } else {
            $this->sendResponse(424, 'ID is mandatory');
        }
    }

    /**
     * Wrapper for getting a list of posts
     * @param $limit
     * @param $page
     * @param $tags
     */
    protected function postIndex($limit, $page, $tags)
    {
        $start = ($page - 1) * $limit;
        $results = Image::find_images(max($start, 0), min($limit, 100), $tags);
        $posts = array();
        foreach ($results as $img) {
            if (!is_object($img)) {
                continue;
            }
            $posts[] = new _SafeOuroborosImage($img);
        }
        $this->sendData('post', $posts, max($start, 0));
    }

    /**
     * Tag
     */

    /**
     * Wrapper for getting a list of tags
     * @param $limit
     * @param $page
     * @param $order
     * @param $id
     * @param $after_id
     * @param $name
     * @param $name_pattern
     */
    protected function tagIndex($limit, $page, $order, $id, $after_id, $name, $name_pattern)
    {
        global $database, $config;
        $start = ($page - 1) * $limit;
        $tag_data = array();
        switch ($order) {
            case 'name':
                $tag_data = $database->get_col(
                    $database->scoreql_to_sql(
                        "
                                                        SELECT DISTINCT
                                                            id, SCORE_STRNORM(substr(tag, 1, 1)), count
                                                        FROM tags
                                                        WHERE count >= :tags_min
                                                        ORDER BY SCORE_STRNORM(substr(tag, 1, 1)) LIMIT :start, :max_items
                                                    "
                    ),
                    array('tags_min' => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit)
                );
                break;
            case 'count':
                $tag_data = $database->get_all(
                    "
                                                    SELECT id, tag, count
                                                    FROM tags
                                                    WHERE count >= :tags_min
                                                    ORDER BY count DESC, tag ASC LIMIT :start, :max_items
                                                    ",
                    array('tags_min' => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit)
                );
                break;
            case 'date':
                $tag_data = $database->get_all(
                    "
                                                    SELECT id, tag, count
                                                    FROM tags
                                                    WHERE count >= :tags_min
                                                    ORDER BY count DESC, tag ASC LIMIT :start, :max_items
                                                    ",
                    array('tags_min' => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit)
                );
                break;
        }
        $tags = array();
        foreach ($tag_data as $tag) {
            if (!is_array($tag)) {
                continue;
            }
            $tags[] = new _SafeOuroborosTag($tag);
        }
        $this->sendData('tag', $tags, $start);
    }

    /**
     * Utility methods
     */

    /**
     * Sends a simple {success,reason} message to browser
     *
     * @param int $code HTTP equivalent code for the message
     * @param string $reason Reason for the code
     * @param bool $location Is $reason a location? (used mainly for post/create)
     */
    private function sendResponse($code = 200, $reason = '', $location = false)
    {
        global $page;
        if ($code == 200) {
            $success = true;
        } else {
            $success = false;
        }
        if (empty($reason)) {
            if (defined("self::MSG_HTTP_{$code}")) {
                $reason = constant("self::MSG_HTTP_{$code}");
            } else {
                $reason = self::MSG_HTTP_418;
            }
        }
        if ($code != 200) {
            $proto = $_SERVER['SERVER_PROTOCOL'];
            if (defined("self::HEADER_HTTP_{$code}")) {
                $header = constant("self::HEADER_HTTP_{$code}");
            } else {
                // I'm a teapot!
                $code = 418;
                $header = self::HEADER_HTTP_418;
            }
            header("{$proto} {$code} {$header}", true);
        }
        $response = array('success' => $success, 'reason' => $reason);
        if ($this->type == 'json') {
            if ($location !== false) {
                $response['location'] = $response['reason'];
                unset($response['reason']);
            }
            $response = json_encode($response);
        } elseif ($this->type == 'xml') {
            // Seriously, XML sucks...
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->startDocument('1.0', 'utf-8');
            $xml->startElement('response');
            $xml->writeAttribute('success', var_export($success, true));
            if ($location !== false) {
                $xml->writeAttribute('location', $reason);
            } else {
                $xml->writeAttribute('reason', $reason);
            }
            $xml->endElement();
            $xml->endDocument();
            $response = $xml->outputMemory(true);
            unset($xml);
        }
        $page->set_data($response);
    }

    /**
     * Send data to the browser
     * @param string $type
     * @param mixed $data
     * @param int $offset
     */
    private function sendData($type = '', $data = array(), $offset = 0)
    {
        global $page;
        $response = '';
        if ($this->type == 'json') {
            $response = json_encode($data);
        } elseif ($this->type == 'xml') {
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->startDocument('1.0', 'utf-8');
            if (array_key_exists(0, $data)) {
                $xml->startElement($type . 's');
                if ($type == 'post') {
                    $xml->writeAttribute('count', count($data));
                    $xml->writeAttribute('offset', $offset);
                }
                if ($type == 'tag') {
                    $xml->writeAttribute('type', 'array');
                }
                foreach ($data as $item) {
                    $this->createItemXML($xml, $type, $item);
                }
                $xml->endElement();
            } else {
                $this->createItemXML($xml, $type, $data);
            }
            $xml->endDocument();
            $response = $xml->outputMemory(true);
            unset($xml);
        }
        $page->set_data($response);
    }

    private function createItemXML(XMLWriter &$xml, $type, $item)
    {
        $xml->startElement($type);
        foreach ($item as $key => $val) {
            if ($key == 'created_at' && $type == 'post') {
                $xml->writeAttribute($key, $val['s']);
            } else {
                if (is_bool($val)) {
                    $val = $val ? 'true' : 'false';
                }
                $xml->writeAttribute($key, $val);
            }
        }
        $xml->endElement();
    }

    /**
     * Try to figure who is uploading
     *
     * Currently checks for either user & session in request or cookies
     * and initializes a global User
     * @param void
     * @return void
     */
    private function tryAuth()
    {
        global $config, $user;

        if (isset($_REQUEST['user']) && isset($_REQUEST['session'])) {
            //Auth by session data from query
            $name = $_REQUEST['user'];
            $session = $_REQUEST['session'];
            $duser = User::by_session($name, $session);
            if (!is_null($duser)) {
                $user = $duser;
            } else {
                $user = User::by_id($config->get_int("anon_id", 0));
            }
        } elseif (isset($_COOKIE[$config->get_string('cookie_prefix', 'shm') . '_' . 'session']) &&
            isset($_COOKIE[$config->get_string('cookie_prefix', 'shm') . '_' . 'user'])
        ) {
            //Auth by session data from cookies
            $session = $_COOKIE[$config->get_string('cookie_prefix', 'shm') . '_' . 'session'];
            $user = $_COOKIE[$config->get_string('cookie_prefix', 'shm') . '_' . 'user'];
            $duser = User::by_session($user, $session);
            if (!is_null($duser)) {
                $user = $duser;
            } else {
                $user = User::by_id($config->get_int("anon_id", 0));
            }
        }
    }

    /**
     * Helper for matching API methods from event
     * @param $page
     * @return bool
     */
    private function match($page)
    {
        return (preg_match("%{$page}\.(xml|json)$%", implode('/', $this->event->args), $matches) === 1);
    }
}
