<?php

declare(strict_types=1);

namespace Shimmie2;

enum ExtensionCategory: string
{
    case GENERAL = "General";
    case ADMIN = "Admin";
    case MODERATION = "Moderation";
    case FILE_HANDLING = "File Handling";
    case OBSERVABILITY = "Observability";
    case INTEGRATION = "Integration";
    case FEATURE = "Feature";
    case METADATA = "Metadata";
}
