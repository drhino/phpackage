<?php

# save themes to json
    # php theme_listing.php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../Config.php';

$users   = ['BlackrockDigital'];
$exclude = [
    'BlackrockDigital/startbootstrap',
    'BlackrockDigital/startbootstrap-clean-blog-jekyll',
    'BlackrockDigital/startbootstrap-freelancer-jekyll',
];

function listRepos($user, $exclude = [])
{
    $curl = new Curl\Curl();
    $curl->setHeader('Accept', 'application/vnd.github.v3+json');
    $curl->get('https://api.github.com/users/' . $user . '/repos?per_page=100');
    $curl->close();

    if ($curl->error)
        die('ERROR: Curl - ' . $curl->error_code . PHP_EOL);

    $response = $curl->response;
    $response = json_decode($response, true);

    $output = [];

    foreach ($response as $item) {
        $return = [];
        $return['description'] = $item['description'];
        //$return['zip'] = $item['svn_url'] . '/archive/master.zip';
        $return['zip'] = $item['svn_url'] . '/zip/master';
        $return['zip'] = str_replace('https://github.com/', 'https://codeload.github.com/', $return['zip']);

        if (!in_array($item['full_name'], $exclude))
            $output[$item['full_name']] = $return;
    }

    if (!isset($GLOBALS['config']['themes_json']))
        die('ERROR: Please define themes.json location in Config.php (themes_json).' . PHP_EOL);

    if (!file_exists($GLOBALS['config']['themes_json']))
        touch($GLOBALS['config']['themes_json']);

    $themes = file_get_contents($GLOBALS['config']['themes_json']);
    $themes = json_decode($themes, true);
    is_array($themes) || $themes = [];
    $output = array_merge($themes, $output);
    $output = json_encode($output, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    file_put_contents($GLOBALS['config']['themes_json'], $output);

    return $output;
}

echo 'Listing themes ... ';

$output = "";

foreach ($users as $user)
    $output .= listRepos($user, $exclude);

echo 'DONE' . PHP_EOL;
//echo $output;
