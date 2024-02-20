<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc functions                                                            *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Add a directory full of images
 *
 * @param string $base
 * @param string[] $extra_tags
 * @return UploadResult[]
 */
function add_dir(string $base, array $extra_tags = []): array
{
    global $database;
    $results = [];

    foreach (list_files($base) as $full_path) {
        $short_path = str_replace($base, "", $full_path);
        $filename = basename($full_path);

        $tags = array_merge(path_to_tags($short_path), $extra_tags);
        try {
            $more_results = $database->with_savepoint(function () use ($full_path, $filename, $tags) {
                $dae = send_event(new DataUploadEvent($full_path, basename($full_path), 0, [
                    'filename' => pathinfo($filename, PATHINFO_BASENAME),
                    'tags' => Tag::implode($tags),
                ]));
                $results = [];
                foreach($dae->images as $image) {
                    $results[] = new UploadSuccess($filename, $image->id);
                }
                return $results;
            });
            $results = array_merge($results, $more_results);
        } catch (UploadException $ex) {
            $results[] = new UploadError($filename, $ex->getMessage());
        }
    }

    return $results;
}

function get_file_ext(string $filename): ?string
{
    return pathinfo($filename)['extension'] ?? null;
}

/**
 * Given a full size pair of dimensions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact.
 * Optionally uses the High-DPI scaling setting to adjust the final resolution.
 *
 * @param int $orig_width
 * @param int $orig_height
 * @param bool $use_dpi_scaling Enables the High-DPI scaling.
 * @return array{0: int, 1: int}
 */
function get_thumbnail_size(int $orig_width, int $orig_height, bool $use_dpi_scaling = false): array
{
    global $config;

    $fit = $config->get_string(ImageConfig::THUMB_FIT);

    if (in_array($fit, [
            Media::RESIZE_TYPE_FILL,
            Media::RESIZE_TYPE_STRETCH,
            Media::RESIZE_TYPE_FIT_BLUR,
            Media::RESIZE_TYPE_FIT_BLUR_PORTRAIT
        ])) {
        return [$config->get_int(ImageConfig::THUMB_WIDTH), $config->get_int(ImageConfig::THUMB_HEIGHT)];
    }

    if ($orig_width === 0) {
        $orig_width = 192;
    }
    if ($orig_height === 0) {
        $orig_height = 192;
    }

    if ($orig_width > $orig_height * 5) {
        $orig_width = $orig_height * 5;
    }
    if ($orig_height > $orig_width * 5) {
        $orig_height = $orig_width * 5;
    }


    if ($use_dpi_scaling) {
        list($max_width, $max_height) = get_thumbnail_max_size_scaled();
    } else {
        $max_width = $config->get_int(ImageConfig::THUMB_WIDTH);
        $max_height = $config->get_int(ImageConfig::THUMB_HEIGHT);
    }

    $output = get_scaled_by_aspect_ratio($orig_width, $orig_height, $max_width, $max_height);

    if ($output[2] > 1 && $config->get_bool('thumb_upscale')) {
        return [(int)$orig_width, (int)$orig_height];
    } else {
        return $output;
    }
}

/**
 * @return array{0: int, 1: int, 2: float}
 */
function get_scaled_by_aspect_ratio(int $original_width, int $original_height, int $max_width, int $max_height): array
{
    $xscale = ($max_width / $original_width);
    $yscale = ($max_height / $original_height);

    $scale = ($yscale < $xscale) ? $yscale : $xscale ;

    return [(int)($original_width * $scale), (int)($original_height * $scale), $scale];
}

/**
 * Fetches the thumbnails height and width settings and applies the High-DPI scaling setting before returning the dimensions.
 *
 * @return array{0: int, 1: int}
 */
function get_thumbnail_max_size_scaled(): array
{
    global $config;

    $scaling = $config->get_int(ImageConfig::THUMB_SCALING);
    $max_width  = $config->get_int(ImageConfig::THUMB_WIDTH) * ($scaling / 100);
    $max_height = $config->get_int(ImageConfig::THUMB_HEIGHT) * ($scaling / 100);
    return [$max_width, $max_height];
}


function create_image_thumb(Image $image, string $engine = null): void
{
    global $config;
    create_scaled_image(
        $image->get_image_filename(),
        $image->get_thumb_filename(),
        get_thumbnail_max_size_scaled(),
        $image->get_mime(),
        $engine,
        $config->get_string(ImageConfig::THUMB_FIT)
    );
}


/**
 * @param array{0: int, 1: int} $tsize
 */
function create_scaled_image(
    string $inname,
    string $outname,
    array $tsize,
    string $mime,
    ?string $engine = null,
    ?string $resize_type = null
): void {
    global $config;
    if (empty($engine)) {
        $engine = $config->get_string(ImageConfig::THUMB_ENGINE);
    }
    if (empty($resize_type)) {
        $resize_type = $config->get_string(ImageConfig::THUMB_FIT);
    }

    $output_mime = $config->get_string(ImageConfig::THUMB_MIME);

    send_event(new MediaResizeEvent(
        $engine,
        $inname,
        $mime,
        $outname,
        $tsize[0],
        $tsize[1],
        $resize_type,
        $output_mime,
        $config->get_string(ImageConfig::THUMB_ALPHA_COLOR),
        $config->get_int(ImageConfig::THUMB_QUALITY),
        true,
        true
    ));
}

function redirect_to_next_image(Image $image, ?string $search = null): void
{
    global $page;

    if (!is_null($search)) {
        $search_terms = Tag::explode($search);
        $query = "search=" . url_escape($search);
    } else {
        $search_terms = [];
        $query = null;
    }

    $target_image = $image->get_next($search_terms);

    if ($target_image === null) {
        $redirect_target = referer_or(search_link(), ['post/view']);
    } else {
        $redirect_target = make_link("post/view/{$target_image->id}", null, $query);
    }

    $page->set_mode(PageMode::REDIRECT);
    $page->set_redirect($redirect_target);
}
