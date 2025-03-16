<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A signal that extensions should check their database tables and update
 * them if needed. By default this is sent at the start of every request,
 * but there is a speed_hax option to disable this (which requires that
 * the admin manually trigger the upgrade via CLI after each software
 * update)
 */
final class DatabaseUpgradeEvent extends Event
{
}
