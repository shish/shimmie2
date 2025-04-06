<?php

declare(strict_types=1);

namespace Shimmie2;

final class Filesystem
{
    /**
     * Generates the path to a file under the data folder based on the file's hash.
     * This process creates subfolders based on octet pairs from the file's hash.
     * The calculated folder follows this pattern data/$base/octet_pairs/$hash
     * @param string $base
     * @param hash-string $hash
     * @param bool $create
     * @param int $splits The number of octet pairs to split the hash into. Caps out at strlen($hash)/2.
     */
    public static function warehouse_path(string $base, string $hash, bool $create = true, ?int $splits = null): Path
    {
        if (is_null($splits)) {
            $splits = SysConfig::getWarehouseSplits();
        }
        $dirs = ["data", $base];
        $splits = min($splits, strlen($hash) / 2);
        for ($i = 0; $i < $splits; $i++) {
            $dirs[] = substr($hash, $i * 2, 2);
        }
        $dirs[] = $hash;

        $pa = Filesystem::join_path(...$dirs);

        if ($create && !$pa->dirname()->exists()) {
            $pa->dirname()->mkdir(0755, true);
        }
        return $pa;
    }

    /**
     * Determines the path to the specified file in the data folder.
     */
    public static function data_path(string|Path $filename, bool $create = true): Path
    {
        $filename = Filesystem::join_path("data", $filename);
        if ($create && !$filename->dirname()->exists()) {
            $filename->dirname()->mkdir(0755, true);
        }
        return $filename;
    }

    /**
     * @return tag-string[]
     */
    public static function path_to_tags(Path $path): array
    {
        $matches = [];
        $tags = [];
        if (\Safe\preg_match("/\d+ - (.+)\.([a-zA-Z0-9]+)/", $path->basename()->str(), $matches)) {
            $tags = Tag::explode($matches[1]);
        }

        $path = $path->str();
        $path = str_replace("\\", "/", $path);
        $path = str_replace(";", ":", $path);
        $path = str_replace("__", " ", $path);
        $path = dirname($path);
        if ($path === "\\" || $path === "/" || $path === ".") {
            $path = "";
        }

        $category = "";
        foreach (explode("/", $path) as $dir) {
            $category_to_inherit = "";
            foreach (explode(" ", $dir) as $tag) {
                $tag = trim($tag);
                if ($tag === "") {
                    continue;
                }
                if (substr_compare($tag, ":", -1) === 0) {
                    // This indicates a tag that ends in a colon,
                    // which is for inheriting to tags on the subfolder
                    $category_to_inherit = $tag;
                } else {
                    if ($category !== "" && !str_contains($tag, ":")) {
                        // This indicates that category inheritance is active,
                        // and we've encountered a tag that does not specify a category.
                        // So we attach the inherited category to the tag.
                        $tag = $category.$tag;
                    }
                    $tags[] = $tag;
                }
            }
            // Category inheritance only works on the immediate subfolder,
            // so we hold a category until the next iteration, and then set
            // it back to an empty string after that iteration
            $category = $category_to_inherit;
        }

        return $tags;
    }

    /**
     * @return Path[]
     */
    public static function get_dir_contents(Path $path): array
    {
        if (!$path->is_dir()) {
            return [];
        }
        return array_map(
            fn ($p) => new Path($p),
            array_diff(
                \Safe\scandir($path->str()),
                ['..', '.']
            )
        );
    }

    public static function remove_empty_dirs(Path $dir): bool
    {
        $children_deleted = true;

        $items = Filesystem::get_dir_contents($dir);

        foreach ($items as $item) {
            $path = Filesystem::join_path($dir, $item);
            if ($path->is_dir()) {
                $children_deleted = $children_deleted && Filesystem::remove_empty_dirs($path);
            } else {
                $children_deleted = false;
            }
        }
        if ($children_deleted === true) {
            try {
                $dir->rmdir();
            } catch (\Exception $e) {
                $children_deleted = false;
            }
        }
        return $children_deleted;
    }

    /**
     * @return Path[]
     */
    public static function get_files_recursively(Path $dir): array
    {
        $things = Filesystem::get_dir_contents($dir);

        $output = [];

        foreach ($things as $thing) {
            $path = Filesystem::join_path($dir, $thing);
            if ($path->is_file()) {
                $output[] = $path;
            } else {
                $output = array_merge($output, Filesystem::get_files_recursively($path));
            }
        }

        return $output;
    }

    /**
     * Returns amount of files & total size of dir.
     *
     * @return array{"path": Path, "total_files": int, "total_mb": string}
     */
    public static function scan_dir(Path $path): array
    {
        $bytestotal = 0;
        $nbfiles = 0;

        $ite = new \RecursiveDirectoryIterator(
            $path->str(),
            \FilesystemIterator::KEY_AS_PATHNAME |
            \FilesystemIterator::CURRENT_AS_FILEINFO |
            \FilesystemIterator::SKIP_DOTS
        );
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($ite) as $_filename => $file) {
            try {
                $filesize = $file->getSize();
                $bytestotal += $filesize;
                $nbfiles++;
            } catch (\RuntimeException $e) {
                // This usually just means that the file got eaten by the import
                continue;
            }
        }

        $size_mb = $bytestotal / 1048576; // to mb
        $size_mb = number_format($size_mb, 2, '.', '');
        return ['path' => $path, 'total_files' => $nbfiles, 'total_mb' => $size_mb];
    }

    /**
     * Delete an entire file heirachy
     */
    public static function deltree(Path $dir): void
    {
        $di = new \RecursiveDirectoryIterator($dir->str(), \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        /** @var string $filename */
        /** @var \SplFileInfo $file */
        foreach ($ri as $filename => $file) {
            $file->isDir() ? rmdir($filename) : unlink($filename);
        }
        $dir->rmdir();
    }

    /**
     * Copy an entire file hierarchy
     *
     * from a comment on https://uk.php.net/copy
     */
    public static function full_copy(Path $source, Path $target): void
    {
        if ($source->is_dir()) {
            @$target->mkdir();

            $d = \Safe\dir($source->str());

            while (true) {
                $entry = $d->read();
                if ($entry === false) {
                    break;
                }
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $entry = $source->str() . '/' . $entry;
                if (is_dir($entry)) {
                    Filesystem::full_copy(
                        new Path($entry),
                        new Path($target->str() . '/' . $entry)
                    );
                    continue;
                }
                copy($entry, $target->str() . '/' . $entry);
            }
            $d->close();
        } else {
            $source->copy($target);
        }
    }

    /**
     * Return a list of all the regular files in a directory and subdirectories
     *
     * @return Path[]
     */
    public static function list_files(Path $base, string $_sub_dir = ""): array
    {
        assert($base->is_dir());

        $file_list = [];

        $files = [];
        $dir = opendir("{$base->str()}/$_sub_dir");
        if ($dir === false) {
            throw new UserError("Unable to open directory {$base->str()}/$_sub_dir");
        }
        try {
            while ($f = readdir($dir)) {
                $files[] = $f;
            }
        } finally {
            closedir($dir);
        }
        sort($files);

        foreach ($files as $filename) {
            $full_path = "{$base->str()}/$_sub_dir/$filename";

            if (!is_link($full_path) && is_dir($full_path)) {
                if (!($filename === "." || $filename === "..")) {
                    //subdirectory found
                    $file_list = array_merge(
                        $file_list,
                        Filesystem::list_files($base, "$_sub_dir/$filename")
                    );
                }
            } else {
                $full_path = str_replace("//", "/", $full_path);
                $file_list[] = new Path($full_path);
            }
        }

        return $file_list;
    }

    /**
     * Like glob, with support for matching very long patterns with braces.
     *
     * @return Path[]
     */
    public static function zglob(string $pattern): array
    {
        $results = [];
        if (\Safe\preg_match('/(.*)\{(.*)\}(.*)/', $pattern, $matches)) {
            $braced = explode(",", $matches[2]);
            foreach ($braced as $b) {
                $sub_pattern = $matches[1].$b.$matches[3];
                $results = array_merge($results, Filesystem::zglob($sub_pattern));
            }
            return $results;
        } else {
            $r = glob($pattern);
            if ($r) {
                return array_map(fn ($p) => new Path($p), $r);
            } else {
                return [];
            }
        }
    }

    /**
     * Stream a file to the client
     */
    public static function stream_file(Path $file, int $start, int $end): void
    {
        $fp = fopen($file->str(), 'r');
        if (!$fp) {
            throw new \Exception("Failed to open {$file->str()}");
        }
        try {
            fseek($fp, $start);
            $buffer = 1024 * 1024;
            while (!feof($fp) && ($p = \Safe\ftell($fp)) <= $end) {
                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                    assert($buffer >= 1);
                }
                echo fread($fp, $buffer);
                flush_output();

                // After flush, we can tell if the client browser has disconnected.
                // This means we can start sending a large file, and if we detect they disappeared
                // then we can just stop and not waste any more resources or bandwidth.
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        } finally {
            fclose($fp);
        }
    }

    /**
     * Combines all path segments specified, ensuring no duplicate separators occur,
     * as well as converting all possible separators to the one appropriate for the current system.
     */
    public static function join_path(string|Path ...$paths): Path
    {
        $output = "";
        foreach ($paths as $path) {
            if ($path instanceof Path) {
                $path = $path->str();
            }
            if (empty($path)) {
                continue;
            }
            $path = \Safe\preg_replace('|[\\\\/]+|S', DIRECTORY_SEPARATOR, $path);
            if (empty($output)) {
                $output = $path;
            } else {
                $output = rtrim($output, DIRECTORY_SEPARATOR);
                $path = ltrim($path, DIRECTORY_SEPARATOR);
                $output .= DIRECTORY_SEPARATOR . $path;
            }
        }
        assert(!empty($output));
        return new Path($output);
    }
}
