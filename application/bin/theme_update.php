<?php

# update theme
    # php theme_update.php BlackrockDigital/startbootstrap-1-col-portfolio

# update all themes
    # php theme_update.php

isset($argv[1]) || die('ERROR: Please define a theme.' . PHP_EOL);

if ($argv[1] === 'all') {
    echo 'Updating ALL themes ... ';
    exec('php ' . __DIR__ . '/theme_download.php all');
    echo 'DONE';
}
else {
    echo 'Updating theme: ' . $argv[1] . ' ... ' . PHP_EOL;
    echo exec('php ' . __DIR__ . '/theme_download.php ' . $argv[1] . ' force');
}
echo PHP_EOL;
