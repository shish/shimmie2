<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<MimeSystemTheme> */
final class MimeSystem extends Extension
{
    public const KEY = "mime";

    #[EventListener]
    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $event->replace('$ext', $event->image->get_ext());
        $event->replace('$mime', (string)$event->image->get_mime());
    }


    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // These upgrades are primarily for initializing mime types on upgrade, and for adjusting mime types whenever an
        // adjustment needs to be made to the mime types.

        if ($this->get_version() < 1) {
            if ($database->is_transaction_open()) {
                // Each of these commands could hit a lot of data, combining
                // them into one big transaction would not be a good idea.
                $database->commit();
            }
            $database->set_timeout(null); // These updates can take a little bit

            /** @var string[] $extensions */
            $extensions = $database->get_col("SELECT DISTINCT ext FROM images");

            foreach ($extensions as $ext) {
                $mime = MimeType::get_for_extension($ext);

                if (is_null($mime) || $mime->base === MimeType::OCTET_STREAM) {
                    throw new UserError("Unknown extension: $ext");
                }

                $normalized_extension = FileExtension::get_for_mime($mime);

                $database->execute(
                    "UPDATE images SET mime = :mime, ext = :new_ext WHERE ext = :ext AND (mime IS NULL OR mime != :mime OR ext != :new_ext)",
                    ["mime" => (string)$mime, "new_ext" => $normalized_extension, "ext" => $ext]
                );
            }

            $this->set_version(1);
            $database->begin_transaction();
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("File Types", $this->theme->get_help_html());
        }
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^ext[=:]([a-zA-Z0-9]+)$/i")) {
            $ext = strtolower($matches[1]);
            $event->add_querylet(new Querylet('images.ext = :ext', ["ext" => $ext]));
        } elseif ($matches = $event->matches("/^mime[=:](.+)$/i")) {
            $mime = strtolower($matches[1]);
            $event->add_querylet(new Querylet("images.mime = :mime", ["mime" => $mime]));
        }
    }
}
