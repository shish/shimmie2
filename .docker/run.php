#!/bin/env php
<?php
// Check install is valid and dirs exist
if (!is_dir('/app/data')) {
    mkdir('/app/data', 0755, true);
}
chown('/app/data', 'shimmie');
chgrp('/app/data', 'shimmie');

// Get php.ini settings from PHP_INI_XXX environment variables
$php_ini = [];
foreach(getenv() as $key => $value) {
    if (strpos($key, 'PHP_INI_') === 0) {
        $php_ini_key = strtolower(substr($key, 8));
        $php_ini[$php_ini_key] = $value;
    }
}
// deprecated one-off special configs
$php_ini['max_file_uploads'] ??= getenv('MAX_FILE_UPLOADS') ?: "100";
$php_ini['upload_max_filesize'] ??= getenv('UPLOAD_MAX_FILESIZE') ?: '100M';
// this one needs to be calculated for the web server itself
$php_ini['post_max_size'] ??= (string)(
    ini_parse_quantity($php_ini['upload_max_filesize']) *
    intval($php_ini['max_file_uploads'])
);

// Generate a config file for whatever web server we are using today
$config = [
    "listeners" => [
        "*:8000" => [
            "pass" => "routes",
            "forwarded" => [
                "client_ip" => "X-Forwarded-For",
                "recursive" => false,
                "source" => [
                    "172.17.0.0/16"
                ]
            ]
        ]
    ],
    "routes" => [
        [
            "match" => [
                "uri" => "~/_(thumbs|images)/.*"
            ],
            "action" => [
                "share" => [
                    '`/app/data/${uri.replace(/_(thumbs|images)\\/(..)(..)(.*?)\\/.*/, "$1/$2/$3/$2$3$4")}`',
                    '`/app/data/${uri.replace(/_(thumbs|images)\\/(..)(.*?)\\/.*/, "$1/$2/$2$3")}`'
                ],
                "response_headers" => [
                    "Cache-Control" => "public, max-age=31556926"
                ]
            ]
        ],
        [
            "action" => [
                "share" => '/app/$uri',
                "types" => [
                    "image/*",
                    "application/javascript",
                    "text/css",
                    "application/sourcemap",
                    "!"
                ],
                "response_headers" => [
                    "Cache-Control" => "public, max-age=31556926"
                ],
                "fallback" => [
                    "pass" => "applications/shimmie"
                ]
            ]
        ]
    ],
    "applications" => [
        "shimmie" => [
            "type" => "php",
            "user" => "shimmie",
            "root" => "/app/",
            "script" => "index.php",
            "working_directory" => "/app/",
            "options" => [
                "admin" => $php_ini
            ],
            "processes" => [
                "max" => 8,
                "spare" => 2,
                "idle_timeout" => 60
            ]
        ]
    ],
    "settings" => [
        "http" => [
            "max_body_size" => ini_parse_quantity($php_ini['post_max_size']),
            "static" => [
                "mime_types" => [
                    "application/sourcemap" => [".map"]
                ]
            ]
        ]
    ]
];
file_put_contents(
    '/var/lib/unit/conf.json',
    json_encode($config, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
);

// Start the web server
pcntl_exec('/usr/sbin/unitd', [
    '--no-daemon',
    '--control', 'unix:/var/run/control.unit.sock',
    '--log', '/dev/stderr'
]);
