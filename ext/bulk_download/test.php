<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkDownloadTest extends ShimmiePHPUnitTestCase
{
    public function testExportCreatesZipWithMetadata(): void
    {
        // Log in as admin
        self::log_in_as_admin();

        // Upload some test images
        $image_id_1 = self::create_post("tests/pbx_screenshot.jpg", "export test1");
        $image_id_2 = self::create_post("tests/favicon.png", "export test2");

        $image1 = Post::by_id_ex($image_id_1);
        $image2 = Post::by_id_ex($image_id_2);

        // Trigger bulk export
        send_event(new BulkActionEvent(
            "download",
            array_to_generator([$image1, $image2]),
            new QueryArray(["bulk_download_metadata" => "true"]),
        ));

        // Check that page is set to file mode
        self::assertEquals(PageMode::FILE, Ctx::$page->mode);
        self::assertEquals(MimeType::ZIP, Ctx::$page->mime);
        self::assertNotNull(Ctx::$page->file);
        self::assertTrue(Ctx::$page->file->is_file());

        // Open and verify the zip contents
        $zip = new \ZipArchive();
        self::assertTrue($zip->open(Ctx::$page->file->str()));
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }
        self::assertEquals([
            "$image_id_1 - export test1.jpg",
            "$image_id_2 - export test2.png",
            BulkDownload::EXPORT_INFO_FILE_NAME,
        ], $files);

        // Read and verify export.json content
        $json_content = $zip->getFromName(BulkDownload::EXPORT_INFO_FILE_NAME);
        self::assertNotFalse($json_content);
        $metadata = json_decode($json_content, true);
        self::assertIsArray($metadata);
        self::assertCount(2, $metadata);

        // Verify first image metadata
        self::assertArrayHasKey("hash", $metadata[0]);
        self::assertArrayHasKey("filename", $metadata[0]);
        self::assertArrayHasKey("tags", $metadata[0]);
        self::assertEquals($image1->hash, $metadata[0]["hash"]);

        // Verify second image metadata
        self::assertArrayHasKey("hash", $metadata[1]);
        self::assertArrayHasKey("filename", $metadata[1]);
        self::assertArrayHasKey("tags", $metadata[1]);
        self::assertEquals($image2->hash, $metadata[1]["hash"]);

        // Check that image files are in the zip
        self::assertNotFalse($zip->locateName($metadata[0]['_filename']));
        self::assertNotFalse($zip->locateName($metadata[1]['_filename']));

        $zip->close();
    }
}
