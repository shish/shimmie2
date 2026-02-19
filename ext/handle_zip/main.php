<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZipFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_zip";
    public const SUPPORTED_MIME = [MimeType::ZIP];
    public const EXPORT_INFO_FILE_NAME = "export.json";

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
        global $database;

        if (!$this->supported_mime($event->mime)) {
            return;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($event->tmpname->str())) {
            throw new UserError("Could not open zip archive");
        }

        $json_data = $this->get_export_data($zip);

        $total = 0;
        $skipped = 0;
        $failed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zip_filename = false_throws($zip->getNameIndex($i));
            try {
                // Avoid attempting to import the export.json file itself
                if ($zip_filename === self::EXPORT_INFO_FILE_NAME) {
                    continue;
                }

                // Get metadata from export.json, or empty
                $metadata =
                    $json_data[$zip_filename]
                    ?? new QueryArray();

                $tmpfile = shm_tempnam("bulk_import");
                $stream = false_throws($zip->getStreamIndex($i));
                $tmpfile->put_contents($stream);

                // If no hash in metadata, get hash from file data
                if (!isset($metadata["hash"])) {
                    $metadata["hash"] = $tmpfile->md5();
                }

                // If no tags in metadata, try to get tags from filename
                if (!isset($metadata["tags"])) {
                    $metadata["tags"] = Tag::implode(Filesystem::path_to_tags(new Path($zip_filename)));
                }

                // If no filename in metadata, use the zip filename
                if (!isset($metadata["filename"])) {
                    $metadata["filename"] = $zip_filename;
                }

                // @phpstan-ignore-next-line - we know this is a string from the checks above
                $image = Post::by_hash($metadata["hash"]);
                if ($image !== null) {
                    $skipped++;
                    Log::info(self::KEY, "$zip_filename already present, skipping");
                    continue;
                }

                $database->with_savepoint(function () use ($zip_filename, $metadata, $tmpfile, $event) {
                    $posts = send_event(new DataUploadEvent(
                        $tmpfile,
                        basename($metadata["filename"]),
                        0,
                        $metadata
                    ))->posts;

                    if (count($posts) === 0) {
                        throw new UserError("Unable to import file $zip_filename");
                    }
                    foreach ($posts as $post) {
                        $event->posts[] = $post;
                    }
                });

                $total++;
            } catch (\Exception $ex) {
                $failed++;
                Log::error(
                    self::KEY,
                    "Could not import $zip_filename: {$ex->getMessage()}",
                    "Could not import $zip_filename: {$ex->getMessage()}"
                );
            } finally {
                if (!empty($tmpfile) && $tmpfile->is_file()) {
                    $tmpfile->unlink();
                }
            }
        }

        Log::info(
            self::KEY,
            "Imported $total items, skipped $skipped, $failed failed",
            "Imported $total items, skipped $skipped, $failed failed"
        );
    }

    protected function media_check_properties(Post $image): ?MediaProperties
    {
        return null;
    }

    protected function check_contents(Path $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Post $image): bool
    {
        return false;
    }

    /**
     * Check if the .zip file contains post metadata, parse it if so
     *
     * Input (export.json format):
     * [
     *   [
     *     "hash" => "abc123",
     *     "tags" => ["tag1", "tag2"],
     *     "source" => "http://example.com/image.jpg",
     *     "filename" => "image.jpg",
     *     "title" => null,
     *   ],
     *   ...
     * ]
     *
     * Output (PostInfoSetEvent format):
     * [
     *   "abc123" => QueryArray([
     *     "hash" => "abc123",
     *     "tags" => "tag1 tag2",
     *     "source" => "http://example.com/image.jpg",
     *     "filename" => "image.jpg",
     *   ]),
     *   ...
     * ]
     *
     * @return array<string, QueryArray>
     */
    private function get_export_data(\ZipArchive $zip): array
    {
        $metas = [];
        $info = $zip->getStream(self::EXPORT_INFO_FILE_NAME);
        if ($info !== false) {
            try {
                $json_string = \Safe\stream_get_contents($info);
                $json_data = \Safe\json_decode($json_string, flags: JSON_OBJECT_AS_ARRAY);
                foreach ($json_data as $item) {
                    $meta = new QueryArray();
                    foreach ($item as $key => $value) {
                        if ($key === 'tags' && is_array($value)) {
                            $meta['tags'] = Tag::implode($value);
                        } elseif ($value !== null) {
                            $meta[$key] = (string)$value;
                        }
                    }
                    if (isset($item['_filename'])) {
                        $metas[$item['_filename']] = $meta;
                    } elseif (isset($item['hash'])) {
                        $metas[$item['hash']] = $meta;
                    }
                }
            } finally {
                fclose($info);
            }
        }
        return $metas;
    }
}
