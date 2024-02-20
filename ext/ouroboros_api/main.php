<?php

declare(strict_types=1);

namespace Shimmie2;

class _SafeOuroborosImage
{
    /**
     * Author
     */
    public string $author = '';
    public ?int $creator_id = null;
    public ?int $height = null;
    public ?int $width = null;
    public string $file_ext = '';
    public ?int $file_size = null;
    public string $file_url = '';
    public string $md5 = '';

    /**
     * Post Meta
     */
    public ?int $change = null;
    /** @var array{n:int,s:int,json_class:string} */
    public ?array $created_at = null;
    public ?int $id = null;
    public ?int $parent_id = null;
    public string $rating = 'q';
    public int $score = 1;
    public ?string $source = '';
    public string $status = '';
    public string $tags = 'tagme';
    public bool $has_children = false;
    public bool $has_comments = false;
    public bool $has_notes = false;
    public string $description = '';

    /**
     * Thumbnail
     */
    public ?int $preview_height = null;
    public string $preview_url = '';
    public ?int $preview_width = null;

    /**
     * Downscaled Image
     */
    public ?int $sample_height = null;
    public string $sample_url = '';
    public ?int $sample_width = null;

    public function __construct(Image $img)
    {
        global $config;
        // author
        $author = $img->get_owner();
        $this->author = $author->name;
        $this->creator_id = intval($author->id);

        // file
        $this->height = intval($img->height);
        $this->width = intval($img->width);
        $this->file_ext = $img->get_ext();
        $this->file_size = intval($img->filesize);
        $this->file_url = make_http($img->get_image_link());
        $this->md5 = $img->hash;

        // meta
        $this->change = intval($img->id); //DaFug is this even supposed to do? ChangeID?
        // Should be JSON specific, just strip this when converting to XML
        $this->created_at = ['n' => 123456789, 's' => \Safe\strtotime($img->posted), 'json_class' => 'Time'];
        $this->id = intval($img->id);
        $this->parent_id = null;

        if (Extension::is_enabled(RatingsInfo::KEY) !== false) {
            // 'u' is not a "valid" rating
            if ($img['rating'] == 's' || $img['rating'] == 'q' || $img['rating'] == 'e') {
                $this->rating = $img['rating'];
            }
        }
        if (Extension::is_enabled(NumericScoreInfo::KEY) !== false) {
            $this->score = $img['numeric_score'];
        }

        $this->source = $img->source;
        $this->status = 'active'; //not supported in Shimmie... yet
        $this->tags = $img->get_tag_list();
        $this->has_children = false;
        $this->has_comments = false;
        $this->has_notes = false;

        // thumb
        $this->preview_height = $config->get_int(ImageConfig::THUMB_HEIGHT);
        $this->preview_width = $config->get_int(ImageConfig::THUMB_WIDTH);
        $this->preview_url = make_http($img->get_thumb_link());

        // sample (use the full image here)
        $this->sample_height = intval($img->height);
        $this->sample_width = intval($img->width);
        $this->sample_url = make_http($img->get_image_link());
    }
}

class OuroborosPost extends _SafeOuroborosImage
{
    /** @var array{tmp_name:string,name:string} */
    public ?array $file = null;
    public bool $is_rating_locked = false;
    public bool $is_note_locked = false;

    /**
     * Initialize an OuroborosPost for creation
     * Mainly just acts as a wrapper and validation layer
     * @noinspection PhpMissingParentConstructorInspection
     *
     * @param array<string,mixed> $post
     */
    public function __construct(array $post)
    {
        if (array_key_exists('tags', $post)) {
            // implode(explode()) to resolve aliases and sanitise
            $this->tags = Tag::implode(Tag::explode(urldecode($post['tags'])));
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
            $this->file_url = filter_var_ex(
                urldecode($post['source']),
                FILTER_SANITIZE_URL
            );
        }
        if (array_key_exists('sourceurl', $post)) {
            $this->source = filter_var_ex(
                urldecode($post['sourceurl']),
                FILTER_SANITIZE_URL
            );
        }
        if (array_key_exists('description', $post)) {
            $this->description = filter_var_ex(
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
            $this->parent_id = int_escape($post['parent_id']);
        }
    }
}

class _SafeOuroborosTag
{
    public bool $ambiguous = false;
    public int $count = 0;
    public int $id = 0;
    public string $name = '';
    public int $type = 0;

    /**
     * @param array{id:int,tag:string,count:int} $tag
     */
    public function __construct(array $tag)
    {
        $this->count = $tag['count'];
        $this->id = $tag['id'];
        $this->name = $tag['tag'];
    }
}

class OuroborosAPI extends Extension
{
    private ?PageRequestEvent $event;
    private ?string $type;

    public const HEADER_HTTP_200 = 'OK';
    public const MSG_HTTP_200 = 'Request was successful';

    public const HEADER_HTTP_403 = 'Forbidden';
    public const MSG_HTTP_403 = 'Access denied';

    public const HEADER_HTTP_404 = 'Not found';
    public const MSG_HTTP_404 = 'Not found';

    public const HEADER_HTTP_418 = 'I\'m a teapot';
    public const MSG_HTTP_418 = 'Short and stout';

    public const HEADER_HTTP_420 = 'Invalid Record';
    public const MSG_HTTP_420 = 'Record could not be saved';

    public const HEADER_HTTP_421 = 'User Throttled';
    public const MSG_HTTP_421 = 'User is throttled, try again later';

    public const HEADER_HTTP_422 = 'Locked';
    public const MSG_HTTP_422 = 'The resource is locked and cannot be modified';

    public const HEADER_HTTP_423 = 'Already Exists';
    public const MSG_HTTP_423 = 'Resource already exists';

    public const HEADER_HTTP_424 = 'Invalid Parameters';
    public const MSG_HTTP_424 = 'The given parameters were invalid';

    public const HEADER_HTTP_500 = 'Internal Server Error';
    public const MSG_HTTP_500 = 'Some unknown error occurred on the server';

    public const HEADER_HTTP_503 = 'Service Unavailable';
    public const MSG_HTTP_503 = 'Server cannot currently handle the request, try again later';

    public const ERROR_POST_CREATE_MD5 = 'MD5 mismatch';
    public const ERROR_POST_CREATE_DUPE = 'Duplicate';
    public const OK_POST_CREATE_UPDATE = 'Updated';

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if (preg_match("%\.(xml|json)$%", implode('/', $event->args), $matches) === 1) {
            $this->event = $event;
            $this->type = $matches[1];
            if ($this->type == 'json') {
                $page->set_mime('application/json; charset=utf-8');
            } elseif ($this->type == 'xml') {
                $page->set_mime('text/xml; charset=utf-8');
            }
            $page->set_mode(PageMode::DATA);
            $this->tryAuth();

            if ($event->page_matches('post')) {
                if ($this->match('create')) {
                    // Create
                    if ($user->can(Permissions::CREATE_IMAGE)) {
                        $md5 = !empty($_REQUEST['md5']) ? filter_var_ex($_REQUEST['md5'], FILTER_SANITIZE_STRING) : null;
                        $this->postCreate(new OuroborosPost($_REQUEST['post']), $md5);
                    } else {
                        $this->sendResponse(403, 'You cannot create new posts');
                    }
                } elseif ($this->match('update')) {
                    throw new ServerError("update not implemented");
                } elseif ($this->match('show')) {
                    // Show
                    $id = !empty($_REQUEST['id']) ? (int)filter_var_ex($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : null;
                    $this->postShow($id);
                } elseif ($this->match('index') || $this->match('list')) {
                    // List
                    $limit = !empty($_REQUEST['limit']) ? intval(
                        filter_var_ex($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 45;
                    $p = !empty($_REQUEST['page']) ? intval(
                        filter_var_ex($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 1;
                    $tags = !empty($_REQUEST['tags']) ? filter_var_ex($_REQUEST['tags'], FILTER_SANITIZE_STRING) : [];
                    if (is_string($tags)) {
                        $tags = Tag::explode($tags);
                    }
                    $this->postIndex($limit, $p, $tags);
                }
            } elseif ($event->page_matches('tag')) {
                if ($this->match('index') || $this->match('list')) {
                    $limit = !empty($_REQUEST['limit']) ? intval(
                        filter_var_ex($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 50;
                    $p = !empty($_REQUEST['page']) ? intval(
                        filter_var_ex($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)
                    ) : 1;
                    $order = (!empty($_REQUEST['order']) && ($_REQUEST['order'] == 'date' || $_REQUEST['order'] == 'count' || $_REQUEST['order'] == 'name')) ? filter_var_ex(
                        $_REQUEST['order'],
                        FILTER_SANITIZE_STRING
                    ) : 'date';
                    $id = !empty($_REQUEST['id']) ? intval(
                        filter_var_ex($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT)
                    ) : null;
                    $after_id = !empty($_REQUEST['after_id']) ? intval(
                        filter_var_ex($_REQUEST['after_id'], FILTER_SANITIZE_NUMBER_INT)
                    ) : null;
                    $name = !empty($_REQUEST['name']) ? filter_var_ex($_REQUEST['name'], FILTER_SANITIZE_STRING) : '';
                    $name_pattern = !empty($_REQUEST['name_pattern']) ? filter_var_ex(
                        $_REQUEST['name_pattern'],
                        FILTER_SANITIZE_STRING
                    ) : '';
                    $this->tagIndex($limit, $p, $order, $id, $after_id, $name, $name_pattern);
                }
            }
        } elseif ($event->page_matches('post/show')) {
            $page->set_mode(PageMode::REDIRECT);
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
     */
    protected function postCreate(OuroborosPost $post, ?string $md5 = ''): void
    {
        global $config, $database;
        $handler = $config->get_string(ImageConfig::UPLOAD_COLLISION_HANDLER);
        if (!empty($md5) && !($handler == ImageConfig::COLLISION_MERGE)) {
            $img = Image::by_hash($md5);
            if (!is_null($img)) {
                $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                return;
            }
        }
        $meta = [];
        $meta['tags'] = $post->tags;
        $meta['source'] = $post->source;
        if (Extension::is_enabled(RatingsInfo::KEY) !== false) {
            $meta['rating'] = $post->rating;
        }
        // Check where we should try for the file
        if (empty($post->file) && !empty($post->file_url) && filter_var_ex(
            $post->file_url,
            FILTER_VALIDATE_URL
        ) !== false
        ) {
            // Transload from source
            $meta['file'] = shm_tempnam('transload_' . $config->get_string(UploadConfig::TRANSLOAD_ENGINE));
            $meta['filename'] = basename($post->file_url);
            try {
                fetch_url($post->file_url, $meta['file']);
            } catch (FetchException $e) {
                $this->sendResponse(500, "Transloading failed: $e");
                return;
            }
            $meta['hash'] = \Safe\md5_file($meta['file']);
        } else {
            // Use file
            $meta['file'] = $post->file['tmp_name'];
            $meta['filename'] = $post->file['name'];
            $meta['hash'] = \Safe\md5_file($meta['file']);
        }
        if (!empty($md5) && $md5 !== $meta['hash']) {
            $this->sendResponse(420, self::ERROR_POST_CREATE_MD5);
            return;
        }
        if (!empty($meta['hash'])) {
            $img = Image::by_hash($meta['hash']);
            if (!is_null($img)) {
                $handler = $config->get_string(ImageConfig::UPLOAD_COLLISION_HANDLER);
                if ($handler == ImageConfig::COLLISION_MERGE) {
                    $postTags = Tag::explode($post->tags);
                    $merged = array_merge($postTags, $img->get_tag_array());
                    send_event(new TagSetEvent($img, $merged));

                    // This is really the only thing besides tags we should care
                    if (isset($meta['source'])) {
                        send_event(new SourceSetEvent($img, $meta['source']));
                    }
                    $this->sendResponse(200, self::OK_POST_CREATE_UPDATE . ' ID: ' . $img->id);
                    return;
                } else {
                    $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                    return;
                }
            }
        }
        try {
            $image = $database->with_savepoint(function () use ($meta) {
                $dae = send_event(new DataUploadEvent($meta['file'], basename($meta['file']), 0, $meta));
                return $dae->images[0];
            });
            $this->sendResponse(200, make_link('post/view/' . $image->id), true);
        } catch (UploadException $e) {
            // Cleanup in case shit hit the fan
            $this->sendResponse(500, $e->getMessage());
        }
    }

    /**
     * Wrapper for getting a single post
     */
    protected function postShow(int $id = null): void
    {
        if (!is_null($id)) {
            $post = new _SafeOuroborosImage(Image::by_id_ex($id));
            $this->sendData('post', [$post]);
        } else {
            $this->sendResponse(424, 'ID is mandatory');
        }
    }

    /**
     * Wrapper for getting a list of posts
     * @param string[] $tags
     */
    protected function postIndex(int $limit, int $page, array $tags): void
    {
        $start = ($page - 1) * $limit;
        $results = Search::find_images(max($start, 0), min($limit, 100), $tags);
        $posts = [];
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

    protected function tagIndex(int $limit, int $page, string $order, int $id, int $after_id, string $name, string $name_pattern): void
    {
        global $database, $config;
        $start = ($page - 1) * $limit;
        switch ($order) {
            case 'name':
                $tag_data = $database->get_col(
                    "
                        SELECT DISTINCT
                            id, LOWER(substr(tag, 1, 1)), count
                        FROM tags
                        WHERE count >= :tags_min
                        ORDER BY LOWER(substr(tag, 1, 1)) LIMIT :start, :max_items
                    ",
                    ['tags_min' => $config->get_int(TagListConfig::TAGS_MIN), 'start' => $start, 'max_items' => $limit]
                );
                break;
            case 'count':
            default:
                $tag_data = $database->get_all(
                    "
                        SELECT id, tag, count
                        FROM tags
                        WHERE count >= :tags_min
                        ORDER BY count DESC, tag ASC LIMIT :start, :max_items
                    ",
                    ['tags_min' => $config->get_int(TagListConfig::TAGS_MIN), 'start' => $start, 'max_items' => $limit]
                );
                break;
        }
        $tags = [];
        foreach ($tag_data as $tag) {
            $tags[] = new _SafeOuroborosTag($tag);
        }
        $this->sendData('tag', $tags, $start);
    }

    /**
     * Utility methods
     */

    /**
     * Sends a simple {success,reason} message to browser
     */
    private function sendResponse(int $code = 200, string $reason = '', bool $location = false): void
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
        $response = ['success' => $success, 'reason' => $reason];
        if ($this->type == 'json') {
            if ($location !== false) {
                $response['location'] = $response['reason'];
                unset($response['reason']);
            }
            $response = \Safe\json_encode($response);
        } elseif ($this->type == 'xml') {
            // Seriously, XML sucks...
            $xml = new \XMLWriter();
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
     * @param list<_SafeOuroborosTag>|list<_SafeOuroborosImage> $data
     */
    private function sendData(string $type = '', array $data = [], int $offset = 0): void
    {
        global $page;
        $response = '';
        if ($this->type == 'json') {
            $response = \Safe\json_encode($data);
        } elseif ($this->type == 'xml') {
            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->startDocument('1.0', 'utf-8');

            $xml->startElement($type . 's');
            if ($type == 'post') {
                $xml->writeAttribute('count', (string)count($data));
                $xml->writeAttribute('offset', (string)$offset);
            }
            if ($type == 'tag') {
                $xml->writeAttribute('type', 'array');
            }
            foreach ($data as $item) {
                $this->createItemXML($xml, $type, $item);
            }
            $xml->endElement();

            $xml->endDocument();
            $response = $xml->outputMemory(true);
            unset($xml);
        }
        $page->set_data($response);
    }

    private function createItemXML(\XMLWriter $xml, string $type, _SafeOuroborosTag|_SafeOuroborosImage $item): void
    {
        $xml->startElement($type);
        foreach (json_decode(\Safe\json_encode($item)) as $key => $val) {
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
     */
    private function tryAuth(): void
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
            send_event(new UserLoginEvent($user));
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
            send_event(new UserLoginEvent($user));
        }
    }

    /**
     * Helper for matching API methods from event
     */
    private function match(string $page): bool
    {
        return (preg_match("%{$page}\.(xml|json)$%", implode('/', $this->event->args), $matches) === 1);
    }
}
