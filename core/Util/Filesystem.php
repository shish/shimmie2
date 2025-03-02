<?php

declare(strict_types=1);

namespace Shimmie2;

class Filesystem
{
    /**
     * Generates the path to a file under the data folder based on the file's hash.
     * This process creates subfolders based on octet pairs from the file's hash.
     * The calculated folder follows this pattern data/$base/octet_pairs/$hash
     * @param string $base
     * @param string $hash
     * @param bool $create
     * @param int $splits The number of octet pairs to split the hash into. Caps out at strlen($hash)/2.
     * @return string
     */
    public static function warehouse_path(string $base, string $hash, bool $create = true, ?int $splits = null): string
    {
        if (is_null($splits)) {
            $splits = SysConfig::getWarehouseSplits();
        }
        $dirs = [DATA_DIR, $base];
        $splits = min($splits, strlen($hash) / 2);
        for ($i = 0; $i < $splits; $i++) {
            $dirs[] = substr($hash, $i * 2, 2);
        }
        $dirs[] = $hash;

        $pa = Filesystem::join_path(...$dirs);

        if ($create && !file_exists(dirname($pa))) {
            mkdir(dirname($pa), 0755, true);
        }
        return $pa;
    }

    /**
     * Determines the path to the specified file in the data folder.
     */
    public static function data_path(string $filename, bool $create = true): string
    {
        $filename = Filesystem::join_path("data", $filename);
        if ($create && !file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        return $filename;
    }

    /**
     * @return string[]
     */
    public static function path_to_tags(string $path): array
    {
        $matches = [];
        $tags = [];
        if (\Safe\preg_match("/\d+ - (.+)\.([a-zA-Z0-9]+)/", basename($path), $matches)) {
            $tags = explode(" ", $matches[1]);
        }

        $path = str_replace("\\", "/", $path);
        $path = str_replace(";", ":", $path);
        $path = str_replace("__", " ", $path);
        $path = dirname($path);
        if ($path == "\\" || $path == "/" || $path == ".") {
            $path = "";
        }

        $category = "";
        foreach (explode("/", $path) as $dir) {
            $category_to_inherit = "";
            foreach (explode(" ", $dir) as $tag) {
                $tag = trim($tag);
                if ($tag == "") {
                    continue;
                }
                if (substr_compare($tag, ":", -1) === 0) {
                    // This indicates a tag that ends in a colon,
                    // which is for inheriting to tags on the subfolder
                    $category_to_inherit = $tag;
                } else {
                    if ($category != "" && !str_contains($tag, ":")) {
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
     * @return string[]
     */
    public static function get_dir_contents(string $dir): array
    {
        assert(!empty($dir));

        if (!is_dir($dir)) {
            return [];
        }
        return array_diff(
            \Safe\scandir($dir),
            ['..', '.']
        );
    }

    public static function remove_empty_dirs(string $dir): bool
    {
        $result = true;

        $items = Filesystem::get_dir_contents($dir);
        ;
        foreach ($items as $item) {
            $path = Filesystem::join_path($dir, $item);
            if (is_dir($path)) {
                $result = $result && Filesystem::remove_empty_dirs($path);
            } else {
                $result = false;
            }
        }
        if ($result === true) {
            $result = rmdir($dir);
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public static function get_files_recursively(string $dir): array
    {
        $things = Filesystem::get_dir_contents($dir);

        $output = [];

        foreach ($things as $thing) {
            $path = Filesystem::join_path($dir, $thing);
            if (is_file($path)) {
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
     * @return array{"path": string, "total_files": int, "total_mb": string}
     */
    public static function scan_dir(string $path): array
    {
        $bytestotal = 0;
        $nbfiles = 0;

        $ite = new \RecursiveDirectoryIterator(
            $path,
            \FilesystemIterator::KEY_AS_PATHNAME |
            \FilesystemIterator::CURRENT_AS_FILEINFO |
            \FilesystemIterator::SKIP_DOTS
        );
        foreach (new \RecursiveIteratorIterator($ite) as $filename => $cur) {
            try {
                $filesize = $cur->getSize();
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
    public static function deltree(string $dir): void
    {
        $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        /** @var \SplFileInfo $file */
        foreach ($ri as $filename => $file) {
            $file->isDir() ? rmdir($filename) : unlink($filename);
        }
        rmdir($dir);
    }

    /**
     * Copy an entire file hierarchy
     *
     * from a comment on https://uk.php.net/copy
     */
    public static function full_copy(string $source, string $target): void
    {
        if (is_dir($source)) {
            @mkdir($target);

            $d = \Safe\dir($source);

            while (true) {
                $entry = $d->read();
                if ($entry === false) {
                    break;
                }
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    Filesystem::full_copy($Entry, $target . '/' . $entry);
                    continue;
                }
                copy($Entry, $target . '/' . $entry);
            }
            $d->close();
        } else {
            copy($source, $target);
        }
    }

    /**
     * Return a list of all the regular files in a directory and subdirectories
     *
     * @return string[]
     */
    public static function list_files(string $base, string $_sub_dir = ""): array
    {
        assert(is_dir($base));

        $file_list = [];

        $files = [];
        $dir = opendir("$base/$_sub_dir");
        if ($dir === false) {
            throw new UserError("Unable to open directory $base/$_sub_dir");
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
            $full_path = "$base/$_sub_dir/$filename";

            if (!is_link($full_path) && is_dir($full_path)) {
                if (!($filename == "." || $filename == "..")) {
                    //subdirectory found
                    $file_list = array_merge(
                        $file_list,
                        Filesystem::list_files($base, "$_sub_dir/$filename")
                    );
                }
            } else {
                $full_path = str_replace("//", "/", $full_path);
                $file_list[] = $full_path;
            }
        }

        return $file_list;
    }

    /**
     * Like glob, with support for matching very long patterns with braces.
     *
     * @return string[]
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
                return $r;
            } else {
                return [];
            }
        }
    }

    public static function stream_file(string $file, int $start, int $end): void
    {
        $fp = fopen($file, 'r');
        if (!$fp) {
            throw new \Exception("Failed to open $file");
        }
        try {
            fseek($fp, $start);
            $buffer = 1024 * 1024;
            while (!feof($fp) && ($p = ftell($fp)) <= $end) {
                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                    assert($buffer >= 1);
                }
                echo fread($fp, $buffer);
                flush_output();

                // After flush, we can tell if the client browser has disconnected.
                // This means we can start sending a large file, and if we detect they disappeared
                // then we can just stop and not waste any more resources or bandwidth.
                if (connection_status() != 0) {
                    break;
                }
            }
        } finally {
            fclose($fp);
        }
    }

    /**
     * Translates all possible directory separators to the appropriate one for the current system,
     * and removes any duplicate separators.
     */
    public static function sanitize_path(string $path): string
    {
        $r = \Safe\preg_replace('|[\\\\/]+|S', DIRECTORY_SEPARATOR, $path);
        return $r;
    }

    /**
     * Combines all path segments specified, ensuring no duplicate separators occur,
     * as well as converting all possible separators to the one appropriate for the current system.
     */
    public static function join_path(string ...$paths): string
    {
        $output = "";
        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }
            $path = Filesystem::sanitize_path($path);
            if (empty($output)) {
                $output = $path;
            } else {
                $output = rtrim($output, DIRECTORY_SEPARATOR);
                $path = ltrim($path, DIRECTORY_SEPARATOR);
                $output .= DIRECTORY_SEPARATOR . $path;
            }
        }
        return $output;
    }
}
