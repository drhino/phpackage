<?php

# download theme
    # php theme_download.php BlackrockDigital/startbootstrap-agency

# download all themes
    # php theme_download.php

# overwrite / re-download / update theme
    # php theme_download.php BlackrockDigital/startbootstrap-agency force

# overwrite / re-download / update all themes
    # php theme_download.php all

require __DIR__ . '/theme_listing.php';
$output = json_decode($output, true);

isset($config['source_themes']) || die('ERROR: No themes folder set in Config.php (source_themes).' . PHP_EOL);

function download($theme)
{
    $output = $GLOBALS['output'];

    if (!isset($output[$theme]))
        die('ERROR: Theme not found.' . PHP_EOL);

    if (!isset($output[$theme]['zip']))
        die('ERROR: No zip folder.' . PHP_EOL);

    $curl = new Curl\Curl();
    $curl->get($output[$theme]['zip']);
    $curl->close();

    if ($curl->error)
        die('ERROR: Curl - ' . $curl->error_code . PHP_EOL);

    $folder = $GLOBALS['config']['source_themes'] . '/' . $theme . '/';
    is_dir($folder) || mkdir($folder, 0755, true);

    $file = $folder . basename($theme) . '.zip';
    file_put_contents($file, $curl->response);
    unzip($file);
}

function unzip($file)
{
    $zip = new ZipArchive;
    $res = $zip->open($file);
    $dir = dirname($file);

    if (!$res)
      die('ERROR: Extract zip failed: ' . $file  . PHP_EOL);

    $zip->extractTo($dir);
    $zip->close();

    unlink($file);

    $source = $dir . '/' . basename($dir) . '-master';
    rmove($source, $dir);
}

/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function rmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) {
            rmove($f->getRealPath(), "$dest/$f");
            empty($r) || unlink($r);
        }
    }

    if (is_dir($src))
        rmdir($src);
    else
        unlink($src);
}

function rmrfdir($dir)
{
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

function cli($theme) {
    $folder = $GLOBALS['config']['source_themes'] . '/' . $theme . '/';
    echo 'Downloading theme: ' . $theme . ' ... ';
    if (!is_dir($folder)) {
        download($theme);
        echo 'DONE' . PHP_EOL;
    } else {
        echo 'ALREADY EXISTS - update? run: php theme_update.php YOUR/THEME' . PHP_EOL;
    }
}

if (isset($argv[1]) && $argv[1] !== 'all') { // download requested theme
    $themedir = $config['source_themes'] . '/' .$argv[1];
    if (isset($argv[2]) && $argv[2] === 'force') // update
        !is_dir($themedir) || rmrfdir($themedir);

    cli($argv[1]);
}
elseif (is_array($output)) { // download all themes
    if (isset($argv[1]) && $argv[1] === 'all') { // force update
        foreach ($output as $theme => $properties) {
            $themedir = $config['source_themes'] . '/' . $theme;
            !is_dir($themedir) || rmrfdir($themedir);
        }
    }

    echo 'Downloading ALL themes' . PHP_EOL;
    foreach ($output as $theme => $properties)
        cli($theme);
}
