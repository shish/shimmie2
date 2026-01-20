<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtensionAuthor
{
    public function __construct(
        public string $name,
        public ?string $email
    ) {
    }
}

/** @extends Extension<ExtManagerTheme> */
final class ExtManager extends Extension
{
    public const KEY = "ext_manager";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("ext_manager/set", method: "POST", permission: ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
            if (is_writable("data/config")) {
                $extras = $event->POST->getAll("extensions");
                $infos = ExtensionInfo::get_all();
                $extras = array_filter($extras, fn ($x) => array_key_exists($x, $infos) && !$infos[$x]->core);
                $this->write_extensions_conf($extras);
                Ctx::$page->set_redirect(make_link("ext_manager"));
            } else {
                throw new ServerError("The config file (data/config/extensions.conf.php) isn't writable by the web server :(");
            }
        } elseif ($event->page_matches("ext_manager", method: "GET", permission: ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
            $this->theme->display_table($this->get_extensions());
        }

        if ($event->page_matches("ext_doc/{ext}")) {
            $ext = $event->get_arg('ext');
            $info = ExtensionInfo::get_all()[$ext];
            $this->theme->display_doc($info);
        }

        // For https://github.com/shish/shimmie2/issues/2010
        // Using the "internal" namespace as this is a prototype of a blind
        // attempt to support clients which I'm not aware of, and may need
        // to be changed without notice
        if ($event->page_matches("api/internal/extensions")) {
            $can_manage = Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST);
            $enabled = Extension::get_enabled_extensions();
            $all_infos = ExtensionInfo::get_all();

            $result = [];
            foreach ($all_infos as $key => $info) {
                // Skip based on visibility
                if ($info->visibility === ExtensionVisibility::HIDDEN) {
                    continue;
                }
                if ($info->visibility === ExtensionVisibility::ADMIN && !$can_manage) {
                    continue;
                }

                // Only include if enabled
                if (in_array($key, $enabled)) {
                    $result[] = $key;
                }
            }

            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode($result));
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('ext:disable-all')
            ->setDescription('Disable all extensions')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                \Safe\unlink("data/config/extensions.conf.php");
                return Command::SUCCESS;
            });
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
                $event->add_nav_link(make_link('ext_manager'), "Extension Manager", "ext_manager");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
            $event->add_link("Extension Manager", make_link("ext_manager"));
        }
    }

    /**
     * @param string[] $extras
     */
    private function write_extensions_conf(array $extras): void
    {
        \Safe\file_put_contents(
            "data/config/extensions.conf.php",
            "<?php\ndefine(\"EXTRA_EXTS\", " . \Safe\json_encode($extras) . ");\n"
        );
        // force PHP to re-read extensions.conf.php on the next request,
        // otherwise it will use an old version for a few seconds
        opcache_reset();
        Log::warning("ext_manager", "Active extensions changed", "Active extensions changed");
    }

    /**
     * @return ExtensionInfo[]
     */
    private function get_extensions(): array
    {
        $extensions = array_values(ExtensionInfo::get_all());
        usort($extensions, function ($a, $b) {
            if ($a->category->name !== $b->category->name) {
                return $a->category->name <=> $b->category->name;
            }
            if ($a->beta !== $b->beta) {
                return $a->beta <=> $b->beta;
            }
            return strcmp($a->name, $b->name);
        });
        return $extensions;
    }
}
