<?php

use DiDom\Document;
use DiDom\Element;

class Application
{
    protected $config;
    private $assets;
    private $public;
    private $theme;
    private $body;
    private $theme_css;
    private $theme_js;

    function __construct()
    {
        require __DIR__ . '/Config.php';
        $this->config = $config;
        $this->defaults();
        $this->theme();
    }

    private function defaults()
    {
        isset($this->config['package-json']) ?? $this->config['package-json'] = false;
        isset($this->config['node_modules']) ?? $this->config['node_modules'] = false;
        isset($this->config['source_css'])   ?? $this->config['source_css']   = [];
        isset($this->config['source_js'])    ?? $this->config['source_js']    = [];

        if (!is_array($this->config['source_css']))
            $this->config['source_css'] = [];
        if (!is_array($this->config['source_js']))
            $this->config['source_js'] = [];
    }

    private function build()
    {
        $this->assets = new PHPackage($this->config['package-json'], $this->config['node_modules'], $this->config['source_css'], $this->config['source_js']);

        if (!isset($this->config['public_html']) || !is_dir($this->config['public_html'])) {
            $public = __DIR__ . '/public_html';

            if (!is_dir($public))
                die('Please define webroot (public_html) in Config.php config.');
            else
                $this->public = $public;
        } else {
            $this->public = $this->config['public_html'];
        }

        $this->assets->fonts($this->public . '/fonts/');
    }

    public function css()
    {
        if (!isset($this->config['output_css']))
            $this->config['output_css'] = 'bundle.min.css';

        $link = $this->assets->css($this->public, '/css/' . $this->config['output_css']);
        $html = '<link href="' . $link . '" rel="stylesheet">';

        echo '<!--' . PHP_EOL;
        echo PHP_EOL . '#################';
        for ($i = -2; $i < strlen($this->theme); $i++)
            echo '#';
        echo PHP_EOL . '# Current Theme: ' . $this->theme . ' #' . PHP_EOL;
        echo '#################';
        for ($i = -2; $i < strlen($this->theme); $i++)
            echo '#';
        echo PHP_EOL . PHP_EOL . '---------------------';
        echo PHP_EOL . '# Theme Stylesheets #';
        echo PHP_EOL . '---------------------' . PHP_EOL . PHP_EOL;
        print_r($this->theme_css);
        echo PHP_EOL . '---------------------';
        echo PHP_EOL . '# Theme Javascript #';
        echo PHP_EOL . '--------------------' . PHP_EOL . PHP_EOL;
        print_r($this->theme_js);
        echo PHP_EOL . '-->' . PHP_EOL . PHP_EOL;

        // remote
        if (isset($this->theme_css['remote']))
            foreach ($this->theme_css['remote'] as $css)
                echo '<link href="' . $css . '" rel="stylesheet">' . PHP_EOL;

        // bundled (should contain skipped)
        echo $html . PHP_EOL;
    } 

    private function js()
    {
        if (!isset($this->config['output_js']))
            $this->config['output_js'] = 'bundle.min.js';

        // bundled (should contain skipped)
        return $this->assets->js($this->public, '/js/' . $this->config['output_js']);
    }

    private function theme()
    {
        if (!isset($this->config['source_themes']) || !is_dir($this->config['source_themes'])) {
            $dir = __DIR__ . '/application/themes';
            is_dir($dir) || mkdir($dir);
        } else {
            $dir = $this->config['source_themes'];
        }

        // search for _api folder
// ...
        // download / install if not present

        // theme listing
        if (!file_exists($this->config['themes_json']))
            file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/_api/theme_listing.php');

        $themes = file_get_contents($this->config['themes_json']);
        $themes = json_decode($themes, true);
        $themes = array_keys($themes);

        $file  = dirname($this->config['themes_json']) . '/theme';
        $theme = '';
        if (file_exists($file)) {
            $theme = file_get_contents($file);
            $theme = trim($theme);
        } else {
            $theme = isset($themes[0]) ? $themes[0] : '';
            file_put_contents($file, $theme);
        }

        if (empty($theme) && !in_array($theme, $themes))
            $theme = isset($themes[0]) ? $themes[0] : '';

        if (empty($theme))
            die('Empty theme.');

        $theme_dir = $dir . '/' . $theme . '/';

        // download theme
        if (!is_dir($theme_dir)) 
            file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/_api/theme_download.php?theme=' . $theme);

        $include = [];
        if (isset($_GET['q']))
            $include[] = $_GET['q'];
        $include[] = 'index.html';
        $include[] = 'index.php';

        $index = false;
        foreach ($include as $possible)
            if (!$index && file_exists($theme_dir . $possible))
                $index = $theme_dir . $possible;

        if (!$index)
            die('Error: index(.html|.php) not found for theme: ' . $theme . ' in directory: ' . $theme_dir);

        $dom = new Document($index, true);

        $body = $dom->find('body')[0];
        $css  = $dom->find('link[rel="stylesheet"]');
        $js   = $dom->find('script[src]');

        foreach ($css as $c) {
            $href = $c->getAttribute('href');

            $custom = 'css/';
            $remote = 'http';
            if (substr($href, 0, strlen($custom)) === $custom) {
                $this->config['source_css'][] = $theme_dir . $href;
                $this->theme_css['bundled'][] = $theme_dir . $href;
            } elseif (substr($href, 0, strlen($remote)) === $remote) {
                $this->theme_css['remote'][] = $href;
            } else {
                $this->theme_css['skipped'][] = (string)$c->html();
            }
        }

        foreach ($js as $j) {
            $src = $j->getAttribute('src');

            $custom = 'js/';
            if (substr($src, 0, strlen($custom)) === $custom) {
                $this->config['source_js'][] = $theme_dir . $src;
                $this->theme_js['bundled'][] = $theme_dir . $src;
            } else {
                $this->theme_js['skipped'][] = (string)$j->html();
            }

            $j->remove();
        }

        $this->build();

        $script = new Element('script');
        $script->setAttribute('src', $this->js());
        $body->appendChild($script);

        $this->body  = $body;
        $this->theme = $theme;

        $this->images($theme_dir);
    }

    private function images($theme_dir)
    {
        $dirs = [
            $theme_dir . 'img',
            $theme_dir . 'images',
        ];

        foreach ($dirs as $dir)
            if (is_dir($dir))
                $this->recurse_copy($dir, $this->public . '/' . basename($dir));
    }

    private function recurse_copy($src, $dst) { 
        $dir = opendir($src); 
        @mkdir($dst); 
        while (false !== ($file = readdir($dir))) { 
            if ($file != '.' && $file != '..') { 
                if (is_dir($src . '/' . $file)) { 
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file); 
                } 
                else { 
                    copy($src . '/' . $file, $dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir); 
    }

    public function output()
    {
        echo $this->body->toDocument()->format()->html() . PHP_EOL;
    }
}
