<?php

declare(strict_types=1);

namespace Shimmie2;

define("DATABASE_DSN", getenv("TEST_DSN") ?: "sqlite::memory:");
define("UNITTEST", true);
$_all_exts = glob('ext/*');
assert($_all_exts !== false);
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', $_all_exts)));

define("VERSION", 'unit-tests');
define("TIMEZONE", 'UTC');
define("SECRET", "asdfghjkl");
