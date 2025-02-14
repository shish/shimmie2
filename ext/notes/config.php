<?php

declare(strict_types=1);

namespace Shimmie2;

class NotesConfig extends ConfigGroup
{
    #[ConfigMeta("Notes per page", ConfigType::INT)]
    public const NOTES_PER_PAGE = "notesNotesPerPage";

    #[ConfigMeta("Requests per page", ConfigType::INT)]
    public const REQUESTS_PER_PAGE = "notesRequestsPerPage";

    #[ConfigMeta("Histories per page", ConfigType::INT)]
    public const HISTORIES_PER_PAGE = "notesHistoriesPerPage";
}
