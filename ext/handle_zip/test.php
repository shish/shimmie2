<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZipFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testImportZipWithFilenamesOnly(): void
    {
        // Log in as admin to have import permissions
        self::log_in_as_admin();

        // Create a temporary zip file with tags in filenames
        $zip_path = shm_tempnam("test_import");
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zip_path->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $test_image_1 = new Path("tests/pbx_screenshot.jpg");
        $test_image_2 = new Path("tests/favicon.png");
        $zip->addFile($test_image_1->str(), "foo/bar/123 - baz qux.jpg");
        $zip->addFile($test_image_2->str(), "456 - foo bar.png");
        $zip->close();

        // Upload the zip file
        $dae = send_event(new DataUploadEvent(
            $zip_path,
            "test_import.zip",
            0,
            new QueryArray()
        ));

        // Should have imported 2 posts
        self::assertCount(2, $dae->posts);

        // Check first image - should have tags from filename
        $image1 = Post::by_hash($test_image_1->md5());
        self::assertNotNull($image1, "First image should be imported");
        self::assertEqualsCanonicalizing(["foo", "bar", "baz", "qux"], $image1->get_tag_array(), "First image should have tags from filename");

        // Check second image - should have tags from filename
        $image2 = Post::by_hash($test_image_2->md5());
        self::assertNotNull($image2, "Second image should be imported");
        self::assertEqualsCanonicalizing(["foo", "bar"], $image2->get_tag_array(), "Second image should have tags from filename");

        // Cleanup
        $zip_path->unlink();
    }

    public function testImportZipWithMetadata(): void
    {
        // Log in as admin to have import permissions
        self::log_in_as_admin();

        // Create a temporary zip file
        $zip_path = shm_tempnam("test_import_meta");
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zip_path->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        // Add test files
        $test_image_1 = new Path("tests/pbx_screenshot.jpg");
        $test_image_2 = new Path("tests/favicon.png");

        $hash1 = $test_image_1->md5();
        $hash2 = $test_image_2->md5();

        $zip->addFile($test_image_1->str(), $hash1);
        $zip->addFile($test_image_2->str(), $hash2);

        // Create export.json with metadata
        $metadata = [
            [
                "hash" => $hash1,
                "tags" => ["test", "metadata", "screenshot"],
                "source" => "http://example.com/image1.jpg",
                "filename" => "screenshot.jpg",
                "title" => "Test Screenshot",
            ],
            [
                "hash" => $hash2,
                "tags" => ["test", "metadata", "favicon"],
                "source" => "http://example.com/image2.png",
                "filename" => "favicon.png",
                "title" => null,
            ],
        ];

        $zip->addFromString(ZipFileHandler::EXPORT_INFO_FILE_NAME, \Safe\json_encode($metadata, JSON_PRETTY_PRINT));
        $zip->close();

        // Upload the zip file
        $dae = send_event(new DataUploadEvent(
            $zip_path,
            "test_import_with_metadata.zip",
            0,
            new QueryArray()
        ));

        // Should have imported 2 posts
        self::assertCount(2, $dae->posts);

        // Check first image - should have tags and metadata from export.json
        $image1 = Post::by_hash($hash1);
        self::assertNotNull($image1, "First image should be imported");
        $tags1 = $image1->get_tag_array();
        self::assertContains("test", $tags1);
        self::assertContains("metadata", $tags1);
        self::assertContains("screenshot", $tags1);
        self::assertEquals("http://example.com/image1.jpg", $image1->source);
        self::assertEquals("screenshot.jpg", $image1->filename);

        // Check second image - should have tags and metadata from export.json
        $image2 = Post::by_hash($hash2);
        self::assertNotNull($image2, "Second image should be imported");
        $tags2 = $image2->get_tag_array();
        self::assertContains("test", $tags2);
        self::assertContains("metadata", $tags2);
        self::assertContains("favicon", $tags2);
        self::assertEquals("http://example.com/image2.png", $image2->source);
        self::assertEquals("favicon.png", $image2->filename);

        // Cleanup
        $zip_path->unlink();
    }

    public function testImportZipSkipsDuplicates(): void
    {
        $test_image = "tests/pbx_screenshot.jpg";

        // Log in as admin
        self::log_in_as_admin();

        // First, upload an image directly
        $image_id = self::create_post($test_image, "original");
        $image = Post::by_id_ex($image_id);
        $original_hash = $image->hash;

        // Now create a zip with the same image
        $zip_path = shm_tempnam("test_import_dup");
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zip_path->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFile($test_image, "duplicate.jpg");
        $zip->close();

        // Upload the zip file
        $dae = send_event(new DataUploadEvent(
            $zip_path,
            "test_import_duplicate.zip",
            0,
            new QueryArray()
        ));

        // Should not have imported any new images (duplicate was skipped)
        self::assertCount(0, $dae->posts);

        // Original image should still exist with original tags
        $image_check = Post::by_hash($original_hash);
        self::assertNotNull($image_check);
        $tags = $image_check->get_tag_array();
        self::assertContains("original", $tags);

        // Cleanup
        $zip_path->unlink();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        // Clean up any temporary files created during tests
        foreach (\Safe\glob("data/temp/test_import*") as $file) {
            @unlink($file);
        }
    }
}
