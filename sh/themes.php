<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Command line interfaces for listing & downloading themes.
 *
 * (suggested list)  php themes.php
 * (download theme)  php themes.php GithubUser/Repository
 * (download themes) php themes.php all
 */
class ThemesCLI
{
    private $themes; // new Themes;
    private $passed; // first argument ($argv[1]) || get parameter ($_GET['theme'])

    /**
     * Download all themes.
     * • php themes.php all
     *
     * Download specific theme.
     * • php themes.php GithubUser/Repository
     *
     * List suggested themes.
     * • php themes.php 
     */
    function __construct($passed)
    {
        $this->themes = new Themes;

        if ($passed) {
            $download = ($passed === 'all') ? array_keys($this->themes->listThemes()) : [$passed];

            foreach ($download as $theme)
                $this->downloadTheme($theme);
        }
        else {
            echo PHP_EOL;
            echo '### Suggested themes: ###';
            echo PHP_EOL;

            foreach ($this->themes->listThemes() as $theme => $description) {
                echo PHP_EOL;
                echo $theme;
                echo PHP_EOL;
                echo $description;
                echo PHP_EOL;
            }
        }
    }

    /**
     * End with an empty new line.
     */
    function __destruct()
    {
        echo PHP_EOL;
    }

    /**
     * Download & unpack a theme to the configured themes directory.
     *
     * @param String $theme GithubUser/Repository
     */
    private function downloadTheme($theme)
    {
        echo PHP_EOL;
        echo 'Downloading theme: ' . $theme . ' ... ';

        $expl = explode('/', $theme);
        $this->themes->downloadTheme($expl[0], $expl[1]);

        echo 'DONE';
        echo PHP_EOL;
    }
}

new ThemesCLI($argv[1] ?? $_GET['theme'] ?? false);
