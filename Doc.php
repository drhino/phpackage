<?php

use DiDom\Document;
use DiDom\Element;

                                                                                           //require __DIR__ . '/Router.php';

/**
 * Build document from base theme.
 */
class Doc
                                                                                           /*class Doc extends Router*/
{
    // access within subclass
    protected $config;
    protected $package;

    // settings
    private $debug = false;
    private $theme = [
        'name' => 'GithubUser/Repository',
        'dir'  => 'source_themes/GithubUser/Repository/',
    ];

    // document
    private $head;
    private $body; // new Document; (body)
    private $sources = [
        'css' => [],
        'js'  => [],
    ];

    /**
     * Build document.
     *
     * @param String $theme GithubUser/Repository.
     * @param Bool   $debug Display debug information.
     */
    function __construct($theme = 'BlackrockDigital/startbootstrap-agency', $debug = true) // THEME depends on router ...
    {
        $this->config();
        $this->package();

        $this->debug         = $debug;
        $this->theme['name'] = $theme;
        $this->theme['dir']  = $this->config['source_themes'] . '/' . $theme . '/';

        $this->build();
    }

    /**
     * Config.php to class variables.
     */
    protected function config()
    {
        require __DIR__ . '/Config.php';
        $this->config = $config;
    }

    /**
     * Available packages in Yarn (package.json).
     */
    protected function package()
    {
        $this->package = file_get_contents($this->config['package_json']);
        $this->package = json_decode($this->package, true);
        $this->package = array_keys($this->package['dependencies']);
    }

    // ... build() ...



/*public function css()
{
    $link = $this->pack->css($this->config['public_html'], '/css/' . $this->config['output_css']);
    $html = '<link href="' . $link . '" rel="stylesheet">';

    // remote
    if (isset($this->sources['css']['remote']))
        foreach ($this->sources['css']['remote'] as $css)
            echo '<link href="' . $css . '" rel="stylesheet">' . PHP_EOL;

    // bundled (should contain skipped)
    echo $html . PHP_EOL;
} 

private function js()
{
    // bundled (should contain skipped)
    return $this->pack->js($this->config['public_html'], '/js/' . $this->config['output_js']);
}*/

    /**
     * Build document based on theme.
     */
    private function build()
    {
        $inc = $this->include();
        $dom = new Document($this->theme['dir'] . $inc, true);

        $css = $dom->find('link[rel="stylesheet"]');
        $js  = $dom->find('script[src]');

        $this->source($css, 'css', 'href');
        $this->source($js,  'js',  'src');

        $pack = new PHPackage(
            $this->config['package_json'],
            $this->config['node_modules'],
            $this->config['source_css'],
            $this->config['source_js']
        );

        // head
        // ...

        // body
        $this->body = $dom->find('body')[0];
        
        // css
        $urls = $this->sources['css']['remote'] ?? [];
        $urls[] = $pack->css($this->config['public_html'], '/css/' . $this->config['output_css']);
        foreach ($urls as $url) {
            $link = new Element('link');
            $link->setAttribute('href', $url);
            $link->setAttribute('rel', 'stylesheet');
            $this->body->appendChild($link);
        }

        // js
        $urls = $this->sources['js']['remote'] ?? [];
        $urls[] = $pack->js($this->config['public_html'], '/js/' . $this->config['output_js']);
        foreach ($urls as $url) {
            $script = new Element('script');
            $script->setAttribute('src', $url);
            $this->body->appendChild($script);
        }

        // fonts
        $pack->fonts($this->config['public_html'] . '/fonts/');

        // images
        $this->images();
    }

    private function include()
    {
        // search theme
        is_dir($this->theme['dir']) || $this->notfound('Theme not found.');

        // search include
        if (isset($_GET['q'])) {
            if (file_exists($this->theme['dir'] . $_GET['q']))
                return $_GET['q'];
        } else {
            if (file_exists($this->theme['dir'] . 'index.php'))
                return 'index.php';
            elseif (file_exists($this->theme['dir'] . 'index.html'))
                return 'index.html';
        }

        // not found
        $this->notfound('Theme include file not found.');
    }

    /**
     * Don't process theme if an error occured.
     */
    private function notfound($msg)
    {
        die($msg);
    }

    /**
     * Theme specific resources. Bundled or remote.
     *
     * @param Array  $objects DiDom HTML objects.
     * @param String $type    Source file extension. ['css', 'js']
     * @param String $attr    HTML attribute that contains the source URL. ['href', 'src']
     */
    private function source($objects, $type, $attr)
    {
        // create possible skipped
        $possible = [];
        foreach ($this->package as $plugin) {
            $possible[] = $plugin . '.min.' . $type;
            $possible[] = $plugin . '.' . $type;
        }

        // section => search
        $sections = [
            'bundled' => $type . '/',
            'remote'  => 'http',
        ];

        foreach ($objects as $o) {
            // get source url
            $value = $o->getAttribute($attr);
            // default section
            $part  = 'skipped';

            // not skipped, maybe bundled or remote?
            if (!in_array(basename($value), $possible)) {
                foreach ($sections as $section => $search) {
                    if (
                        $part === 'skipped' // stop searching if found
                        &&
                        substr($value, 0, strlen($search)) === $search
                    ) {
                        // bundled|remote
                        $part = $section;

                        // compile css|js
                        if ($section === 'bundled')
                            $this->config['source_' . $type][] = $this->theme['dir'] . $value;
                    }
                }
            }

            // save in calculated section
            $this->sources[$type][$part][] = $value;

            // remove from dom
            $o->remove();
        }
    }

    /**
     * Find & copy theme images.
     */
    private function images()
    {
        $dirs = [
            'img',
            'images',
        ];

        foreach ($dirs as $dir)
            if (is_dir($this->theme['dir'] . $dir))
                $this->move($this->theme['dir'] . $dir, $this->config['public_html'] . '/' . $dir);
    }

    /**
     * Move folder. Equivalent of unix 'mv'.
     *
     * @param String $from Source path.
     * @param String $to   Destination path.
     */
    private function move($from, $to)
    {
        is_dir($to) || mkdir($to);

        foreach (glob($from . '/*') as $src) {
            // replace path (replace first occurrence)
            $dst = substr_replace($src, $to, strpos($src, $from), strlen($from));

            if (is_dir($src))
                $this->move($src, $dst);
            else
                copy($src, $dst);
        }
    }

    /**
     * Output document.
     */
    function __destruct()
    {
        $this->output();
        $this->debug();
    }

    /**
     * Output document.
     */
    private function output()
    {
// don't display body in cli
if (empty($this->body))
    return;

        // output document
        echo $this->body->toDocument()->html();
        echo PHP_EOL . '</html>' . PHP_EOL;
    }

    /**
     * Display debug information in console.
     */
    private function debug()
    {
        if (!$this->debug)
            return;

        echo PHP_EOL;
        echo "<script>";
            echo "var pack = JSON.parse('" . json_encode($this->package) . "');";
            echo "var css  = JSON.parse('" . json_encode($this->sources['css'], JSON_UNESCAPED_SLASHES) . "');";
            echo "var js   = JSON.parse('" . json_encode($this->sources['js'],  JSON_UNESCAPED_SLASHES) . "');";

            echo "console.log('');";
            echo "console.log('###############');";
            echo "console.log('# THEME DEBUG #');";
            echo "console.log('###############');";
            echo "console.log('');";
            echo "console.log('Theme:');";
            echo "console.log('" . $this->theme['name'] . "');";
            echo "console.log('');";
            echo "console.log('package.json:');";
            echo "for (var plugin in pack) {";
                echo "console.log('- ' + pack[plugin]);";
            echo "}";
            echo "console.log('');";
            echo "console.log('CSS:');";
            echo "for (var group in css) {";
                echo "console.log('• ' + group);";
                echo "css[group].forEach(function(link){";
                    echo "console.log('  - ' + link);";
                echo "});";
            echo "}";
            echo "console.log('');";
            echo "console.log('JS:');";
            echo "for (var group in js) {";
                echo "console.log('• ' + group);";
                echo "js[group].forEach(function(script){";
                    echo "console.log('  - ' + script);";
                echo "});";
            echo "}";
            echo "console.log('');";
            echo "console.log('###############');";
            echo "console.log('');";
        echo "</script>";
        echo PHP_EOL;
    }
}
