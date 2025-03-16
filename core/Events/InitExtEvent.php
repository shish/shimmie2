<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A wake-up call for extensions.
 *
 * This event is sent before $user is set to anything
 */
final class InitExtEvent extends Event
{
}
