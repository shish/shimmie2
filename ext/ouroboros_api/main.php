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
        // author
        $author = $img->get_owner();
        $this->author = $author->name;
        $this->creator_id = intval($author->id);

        // file
        $this->height = intval($img->height);
        $this->width = intval($img->width);
        $this->file_ext = $img->get_ext();
        $this->file_size = intval($img->filesize);
        $this->file_url = (string)$img->get_image_link()->asAbsolute();
        $this->md5 = $img->hash;

        // meta
        $this->change = intval($img->id); //DaFug is this even supposed to do? ChangeID?
        // Should be JSON specific, just strip this when converting to XML
        $this->created_at = ['n' => 123456789, 's' => \Safe\strtotime($img->posted), 'json_class' => 'Time'];
        $this->id = intval($img->id);
        $this->parent_id = null;

        if (RatingsInfo::is_enabled() !== false) {
            // 'u' is not a "valid" rating
            if ($img['rating'] === 's' || $img['rating'] === 'q' || $img['rating'] === 'e') {
                $this->rating = $img['rating'];
            }
        }
        if (NumericScoreInfo::is_enabled() !== false) {
            $this->score = $img['numeric_score'];
        }

        $this->source = $img->source;
        $this->status = 'active'; //not supported in Shimmie... yet
        $this->tags = $img->get_tag_list();
        $this->has_children = false;
        $this->has_comments = false;
        $this->has_notes = false;

        // thumb
        $this->preview_height = Ctx::$config->get(ThumbnailConfig::HEIGHT);
        $this->preview_width = Ctx::$config->get(ThumbnailConfig::WIDTH);
        $this->preview_url = (string)$img->get_thumb_link()->asAbsolute();

        // sample (use the full image here)
        $this->sample_height = intval($img->height);
        $this->sample_width = intval($img->width);
        $this->sample_url = (string)$img->get_image_link()->asAbsolute();
    }
}

final class OuroborosPost extends _SafeOuroborosImage
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
     * @param array<string, mixed> $post
     */
    // @phpstan-ignore-next-line
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
                $post['rating'] === 's' ||
                $post['rating'] === 'q' ||
                $post['rating'] === 'e'
            );
            $this->rating = $post['rating'];
        }
        if (array_key_exists('source', $post)) {
            $this->file_url = false_throws(filter_var(
                urldecode($post['source']),
                FILTER_SANITIZE_URL
            ));
        }
        if (array_key_exists('sourceurl', $post)) {
            $this->source = false_throws(filter_var(
                urldecode($post['sourceurl']),
                FILTER_SANITIZE_URL
            ));
        }
        if (array_key_exists('description', $post)) {
            $this->description = $post['description'];
        }
        if (array_key_exists('is_rating_locked', $post)) {
            $this->is_rating_locked = bool_escape($post['is_rating_locked']);
        }
        if (array_key_exists('is_note_locked', $post)) {
            $this->is_note_locked = bool_escape($post['is_note_locked']);
        }
        if (array_key_exists('parent_id', $post)) {
            $this->parent_id = int_escape($post['parent_id']);
        }
    }
}

final class _SafeOuroborosTag
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

final class OuroborosAPI extends Extension
{
    public const KEY = "ouroboros_api";
    private string $type;

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
        if (\Safe\preg_match("%(.*)\.(xml|json)$%", implode('/', $event->args), $matches)) {
            $event_args = $matches[1];
            $this->type = $matches[2];

            if ($event_args === 'post/create') {
                // Create
                $this->tryAuth();
                if (Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
                    $md5 = isset($_REQUEST['md5']) && \Safe\preg_match("%^[0-9A-Fa-f]{32}$%", $_REQUEST['md5']) ? strtolower($_REQUEST['md5']) : null;
                    $this->postCreate(new OuroborosPost($_REQUEST['post']), $md5);
                } else {
                    $this->sendResponse(403, 'You cannot create new posts');
                }
            } elseif ($event_args === 'post/show') {
                // Show
                $this->tryAuth();
                $id = int_escape(@$_REQUEST['id']);
                $this->postShow($id);
            } elseif ($event_args === 'post/index' || $event_args === 'post/list') {
                // List
                $this->tryAuth();
                $limit = int_escape(@$_REQUEST['limit']);
                if ($limit <= 0) {
                    $limit = 45;
                }
                $p = int_escape(@$_REQUEST['page']);
                if ($p <= 0) {
                    $p = 1;
                }
                $tags = Tag::explode(@$_REQUEST['tags'] ?: '');
                $this->postIndex($limit, $p, $tags);
            } elseif ($event_args === 'tag/index' || $event_args === 'tag/list') {
                $this->tryAuth();
                $limit = int_escape(@$_REQUEST['limit']);
                if ($limit <= 0) {
                    $limit = 50;
                }
                $p = int_escape(@$_REQUEST['page']);
                if ($p <= 0) {
                    $p = 1;
                }
                $order = @$_REQUEST['order'];
                if (!in_array($order, ['date', 'count', 'name'])) {
                    $order = 'date';
                }
                $name = @$_REQUEST['name'] ?: '';
                $name_pattern = @$_REQUEST['name_pattern'] ?: '';
                $this->tagIndex($limit, $p, $order, $name, $name_pattern);
            }
        } elseif ($event->page_matches('post/show')) {
            Ctx::$page->set_redirect(make_link(str_replace('post/show', 'post/view', implode('/', $event->args))));
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
        $handler = Ctx::$config->get(UploadConfig::COLLISION_HANDLER);
        if (!empty($md5) && !($handler === 'merge')) {
            $img = Image::by_hash($md5);
            if (!is_null($img)) {
                $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                return;
            }
        }
        $meta = new QueryArray([]);
        $meta['tags'] = $post->tags;
        $meta['source'] = $post->source ?? '';
        if (RatingsInfo::is_enabled() !== false) {
            $meta['rating'] = $post->rating;
        }
        // Check where we should try for the file
        if (empty($post->file) && !empty($post->file_url)) {
            // Transload from source
            $meta['file'] = shm_tempnam('transload_' . Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE))->str();
            $meta['filename'] = basename($post->file_url);
            try {
                Network::fetch_url($post->file_url, new Path($meta->req('file')));
            } catch (FetchException $e) {
                $this->sendResponse(500, "Transloading failed: $e");
                return;
            }
            $meta['hash'] = \Safe\md5_file($meta->req('file'));
        } else {
            // Use file
            assert(!is_null($post->file));
            $meta['file'] = $post->file['tmp_name'];
            $meta['filename'] = $post->file['name'];
            $meta['hash'] = \Safe\md5_file($meta->req('file'));
        }
        if (!empty($md5) && $md5 !== $meta['hash']) {
            $this->sendResponse(420, self::ERROR_POST_CREATE_MD5);
            return;
        }
        // @phpstan-ignore-next-line
        $img = Image::by_hash($meta->req('hash'));
        if (!is_null($img)) {
            $handler = Ctx::$config->get(UploadConfig::COLLISION_HANDLER);
            if ($handler === 'merge') {
                $postTags = Tag::explode($post->tags);
                $merged = array_merge($postTags, $img->get_tag_array());
                send_event(new TagSetEvent($img, $merged));

                // This is really the only thing besides tags we should care
                if (!empty($meta['source'])) {
                    send_event(new SourceSetEvent($img, $meta['source']));
                }
                $this->sendResponse(200, self::OK_POST_CREATE_UPDATE . ' ID: ' . $img->id);
                return;
            } else {
                $this->sendResponse(420, self::ERROR_POST_CREATE_DUPE);
                return;
            }
        }
        try {
            $image = Ctx::$database->with_savepoint(function () use ($meta) {
                $dae = send_event(new DataUploadEvent(
                    new Path($meta->req('file')),
                    basename($meta->req('file')),
                    0,
                    $meta
                ));
                return $dae->images[0];
            });
            $this->sendResponse(200, (string)make_link('post/view/' . $image->id), true);
        } catch (UploadException $e) {
            // Cleanup in case shit hit the fan
            $this->sendResponse(500, $e->getMessage());
        }
    }

    /**
     * Wrapper for getting a single post
     */
    protected function postShow(?int $id = null): void
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
     * @param list<tag-string> $tags
     */
    protected function postIndex(int $limit, int $page, array $tags): void
    {
        $start = ($page - 1) * $limit;
        $results = Search::find_images(max($start, 0), min($limit, 100), $tags);
        $posts = [];
        foreach ($results as $img) {
            $posts[] = new _SafeOuroborosImage($img);
        }
        $this->sendData('post', $posts, max($start, 0));
    }

    /**
     * Tag
     */

    protected function tagIndex(int $limit, int $page, string $order, string $name, string $name_pattern): void
    {
        global $database;

        // This class will only exist if the tag map plugin is enabled
        $tags_min = Ctx::$config->get(TagMapConfig::TAGS_MIN);

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
                    ['tags_min' => $tags_min, 'start' => $start, 'max_items' => $limit]
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
                    ['tags_min' => $tags_min, 'start' => $start, 'max_items' => $limit]
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
        $success = ($code === 200);
        if (empty($reason)) {
            if (defined("self::MSG_HTTP_{$code}")) {
                $reason = constant("self::MSG_HTTP_{$code}");
            } else {
                $reason = self::MSG_HTTP_418;
            }
        }
        if ($code !== 200) {
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
        if ($this->type === 'json') {
            if ($location !== false) {
                $response['location'] = $response['reason'];
                unset($response['reason']);
            }
            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode($response));
        } elseif ($this->type === 'xml') {
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
            Ctx::$page->set_data(MimeType::XML, $xml->outputMemory(true));
            unset($xml);
        } else {
            throw new \Exception("Unsupported response type: {$this->type}");
        }
    }

    /**
     * @param list<_SafeOuroborosTag>|list<_SafeOuroborosImage> $data
     */
    private function sendData(string $type = '', array $data = [], int $offset = 0): void
    {
        $response = '';
        if ($this->type === 'json') {
            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode($data));
        } elseif ($this->type === 'xml') {
            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->startDocument('1.0', 'utf-8');

            $xml->startElement($type . 's');
            if ($type === 'post') {
                $xml->writeAttribute('count', (string)count($data));
                $xml->writeAttribute('offset', (string)$offset);
            }
            if ($type === 'tag') {
                $xml->writeAttribute('type', 'array');
            }
            foreach ($data as $item) {
                $this->createItemXML($xml, $type, $item);
            }
            $xml->endElement();

            $xml->endDocument();
            Ctx::$page->set_data(MimeType::XML, $xml->outputMemory(true));
            unset($xml);
        }
    }

    private function createItemXML(\XMLWriter $xml, string $type, _SafeOuroborosTag|_SafeOuroborosImage $item): void
    {
        $xml->startElement($type);
        foreach (json_decode(\Safe\json_encode($item)) as $key => $val) {
            if ($key === 'created_at' && $type === 'post') {
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
        $user = Ctx::$user;

        if (isset($_REQUEST['user']) && isset($_REQUEST['session'])) {
            //Auth by session data from query
            $name = $_REQUEST['user'];
            $session = $_REQUEST['session'];
            $user = User::by_session($name, $session) ?? User::get_anonymous();
            send_event(new UserLoginEvent($user));
        } elseif (isset($_COOKIE[SysConfig::getCookiePrefix() . '_' . 'session']) &&
            isset($_COOKIE[SysConfig::getCookiePrefix() . '_' . 'user'])
        ) {
            //Auth by session data from cookies
            $session = $_COOKIE[SysConfig::getCookiePrefix() . '_' . 'session'];
            $user = $_COOKIE[SysConfig::getCookiePrefix() . '_' . 'user'];
            $user = User::by_session($user, $session) ?? User::get_anonymous();
            send_event(new UserLoginEvent($user));
        }
    }
}
