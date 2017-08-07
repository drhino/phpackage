<?php

$config = [];

// composer.json  // composer package
// vendor/        // composer vendor

// Document.php
// Themes.php

// sh/            // command line interface

$config['public_html']   = __DIR__ . '/public_html';        // webroot

$config['source_css']    = [
                           __DIR__ . '/application/css/*'   // custom css directory
];
$config['source_js']     = [
                           __DIR__ . '/application/js/*'    // custom js directory
];

$config['source_themes'] = __DIR__ . '/application/themes'; // themes download folder

$config['package_json']  = __DIR__ . '/package.json';       // yarn package
$config['node_modules']  = __DIR__ . '/node_modules';       // yarn vendor

$config['output_css']    = 'bundle.min.css';                // output file
$config['output_js']     = 'bundle.min.js';                 // output file
