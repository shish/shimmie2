<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\HR;
use function MicroHTML\P;
use function MicroHTML\UL;
use function MicroHTML\{emptyHTML, joinHTML};

class MimeSystemTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        $mimes = DataHandlerExtension::get_all_supported_mimes();
        $exts = array_map(fn ($mime) => FileExtension::get_for_mime($mime), $mimes);
        return emptyHTML(
            P("Search for posts by extension"),
            SHM_COMMAND_EXAMPLE("ext=jpg", "Returns posts with the extension 'jpg'"),
            P("These extensions are available in the system:"),
            UL(joinHTML(", ", $exts)),
            HR(),
            P("Search for posts by MIME type"),
            SHM_COMMAND_EXAMPLE("mime=image/jpeg", "Returns posts that have the MIME type 'image/jpeg'"),
            P("These MIME types are available in the system:"),
            UL(joinHTML(", ", $mimes)),
        );
    }
}
