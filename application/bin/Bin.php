<?php

require __DIR__ . '/../../vendor/autoload.php';

class CLI
{
    private $github_users = ['BlackrockDigital'];
    private $exclude_repo = [
        'BlackrockDigital/startbootstrap',
        'BlackrockDigital/startbootstrap-clean-blog-jekyll',
        'BlackrockDigital/startbootstrap-freelancer-jekyll',
    ];

    private $config = [];

    private function error($msg)
    {
        die('ERROR: ' . $msg . PHP_EOL);
    }

    private function list($user, $exclude)
    {
        $curl = new Curl\Curl;
        $curl->setHeader('Accept', 'application/vnd.github.v3+json');
        $curl->get('https://api.github.com/users/' . $user . '/repos?per_page=100');
        $curl->close();

        if ($curl->error)
            $this->error('Curl - ' . $curl->error_code);

        $response = $curl->response;
        $response = json_decode($response, true);

        $themes = [];

        foreach ($response as $item) {
            $theme = [];
            $theme['description'] = $item['description'];
            $theme['zip']         = $item['svn_url'] . '/archive/master.zip';

            if (!in_array($item['full_name'], $exclude))
                $themes[$item['full_name']] = $theme;
        }

        return $themes;
    }

    public function listThemes()
    {
        $themes = [];

        foreach ($this->github_users as $user)
            $themes[] = $this->listThemes($user, $this->exclude_repo);
    }
}
