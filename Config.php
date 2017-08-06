<?php

$config = [];

// composer.json
// vendor/
// Application.php (?)
// application/bin/

$config['package-json']  = __DIR__ . '/package.json';            // yarn package
$config['node_modules']  = __DIR__ . '/node_modules';            // yarn vendor

$config['source_css']    = [
                            // custom css
                            __DIR__ . '/application/css/*'
];
$config['source_js']     = [
                            // custom js
                            __DIR__ . '/application/js/*'
];

$config['source_themes'] = __DIR__ . '/application/themes';      // themes download folder
$config['themes_json']   = __DIR__ . '/application/themes.json'; // themes listing, creates 'theme' file with the current theme

$config['public_html']   = __DIR__ . '/public_html';             // webroot

$config['output_css']    = 'bundle.min.css';                     // output file
$config['output_js']     = 'bundle.min.js';                      // output file
