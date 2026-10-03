#!/bin/env php
<?php

// Check install is valid and dirs exist
if (!is_dir("/app/data")) {
    mkdir("/app/data", 0755);
}
chown("/app/data", 'shimmie');
chgrp("/app/data", 'shimmie');

// Get php.ini settings from PHP_INI_XXX environment variables
$php_ini = [
    'max_file_uploads' => '100',
    'upload_max_filesize' => '100M',
];
foreach (getenv() as $key => $value) {
    if (str_starts_with($key, 'PHP_INI_')) {
        $php_ini_key = str_replace("_dot_", ".", strtolower(substr($key, 8)));
        $php_ini[$php_ini_key] = $value;
    }
}
// this one needs to be calculated for the web server itself
$php_ini['post_max_size'] ??= (string)(
    ini_parse_quantity($php_ini['upload_max_filesize']) *
    intval($php_ini['max_file_uploads'])
);

@include_once "data/config/shimmie.conf.php";
$php_version = preg_replace('/^(\d+\.\d+).*$/', '$1', phpversion());
$date = date("c");

$client_max_body_size = ini_parse_quantity($php_ini['post_max_size']) * 1.1;
$wh_splits = defined("WH_SPLITS") ? constant("WH_SPLITS") : 1;
$wh_split_path = match ($wh_splits) {
    1 => '/{re.images.1}/{re.images.2}/{re.images.2}{re.images.3}{re.images.4}',
    2 => '/{re.images.1}/{re.images.2}/{re.images.3}/{re.images.2}{re.images.3}{re.images.4}',
    default => throw new \Exception("Invalid WH_SPLITS value"),
};

$php_ini_str = implode("\n", array_map(
    fn ($k, $v) => "php_ini $k $v",
    array_keys($php_ini),
    $php_ini
)) . "\n";

file_put_contents(
    '/etc/frankenphp/Caddyfile',
    <<<EOD
{
    frankenphp {
        num_threads 2
        max_threads auto
        $php_ini_str
    }
    servers {
        trusted_proxies static 172.16.0.0/12
        client_ip_headers X-Forwarded-For X-Real-IP
    }
}

:8000 {
    request_body {
		max_size $client_max_body_size
	}

	@images_hash path_regexp images ^/_(images|thumbs)/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{28}).*$
	handle @images_hash {
        rewrite * $wh_split_path
        root * /app/data
        header Cache-Control "public, max-age=2592000"
        file_server
	}

    root * /app
    php_server
}
EOD
);

chdir("/");
pcntl_exec('/usr/bin/frankenphp', [
    'run',
    '--config', '/etc/frankenphp/Caddyfile',
    '--adapter', 'caddyfile'
]);
