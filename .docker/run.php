#!/bin/env php
<?php
// Check install is valid and dirs exist
if (!is_dir('/app/data')) {
    mkdir('/app/data', 0755, true);
}
chown('/app/data', 'shimmie');
chgrp('/app/data', 'shimmie');

// Look at docker environment variables
$MAX_FILE_UPLOADS = getenv('MAX_FILE_UPLOADS') ?: "100";
$UPLOAD_MAX_FILESIZE = getenv('UPLOAD_MAX_FILESIZE') ?: '100M';
$MAX_TOTAL_UPLOAD = ini_parse_quantity($UPLOAD_MAX_FILESIZE) * intval($MAX_FILE_UPLOADS);

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
                "admin" => [
                    "memory_limit" => "256M",
                    "max_file_uploads" => "$MAX_FILE_UPLOADS",
                    "upload_max_filesize" => "$UPLOAD_MAX_FILESIZE",
                    "post_max_size" => "$MAX_TOTAL_UPLOAD",
                ]
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
            "max_body_size" => $MAX_TOTAL_UPLOAD,
            "static" => [
                "mime_types" => [
                    "application/sourcemap" => [".map"]
                ]
            ]
        ]
    ]
];
file_put_contents('/var/lib/unit/conf.json', json_encode($config, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

// Start the web server
pcntl_exec('/usr/sbin/unitd', ['--no-daemon', '--control', 'unix:/var/run/control.unit.sock', '--log', '/dev/stderr']);
