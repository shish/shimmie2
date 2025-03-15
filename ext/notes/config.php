<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotesConfig extends ConfigGroup
{
    public const KEY = "notes";

    #[ConfigMeta("Notes per page", ConfigType::INT, default: 20)]
    public const NOTES_PER_PAGE = "notesNotesPerPage";

    #[ConfigMeta("Requests per page", ConfigType::INT, default: 20)]
    public const REQUESTS_PER_PAGE = "notesRequestsPerPage";

    #[ConfigMeta("Histories per page", ConfigType::INT, default: 20)]
    public const HISTORIES_PER_PAGE = "notesHistoriesPerPage";
}
