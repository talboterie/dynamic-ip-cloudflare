<?php

return [
    'remove_strings_from_command' => [
        // Single quotes for Linux and MacOS
        "'".PHP_BINARY."'",
        "'dynamic-ip-cloudflare'",

        // Double quotes for Windows
        '"'.PHP_BINARY.'"',
        '"dynamic-ip-cloudflare"',
    ],
];
