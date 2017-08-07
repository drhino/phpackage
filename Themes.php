<?php

require __DIR__ . '/Doc.php';

/**
 * Base themes for your application.
 * ->listThemes()    List suggested themes.
 * ->downloadTheme() Clone git repository.
 */
class Themes extends Doc
{
    private $github = [
        'BlackrockDigital' => [
            'exclude' => [
                'BlackrockDigital/startbootstrap',
                'BlackrockDigital/startbootstrap-clean-blog-jekyll',
                'BlackrockDigital/startbootstrap-freelancer-jekyll',
            ]
        ],
        /*
        'myAccount' => [
            'exclude' => [
                'myAccount/repo',
            ],
        ],
        'myAccount' => [
            'include' => [
                'myAccount/repo',
            ],
        ],
        'myAccount',
        */
    ];

    function __construct()
    {
        $this->config();
    }

    /**
     * List suggested themes.
     *
     * @return Array [user/repository] => description
     */
    public function listThemes()
    {
        $themes = [];

        foreach ($this->github as $user => $repos) {
            $curl = new Curl\Curl;
            $curl->get('https://api.github.com/users/' . $user . '/repos?per_page=100');
            $curl->close();

            if ($curl->error)
                return 'Curl error: ' . $this->curl->error_code;

            foreach (json_decode($curl->response, true) as $theme)
                if (
                    (isset($repos['exclude']) && !in_array($theme['full_name'], $repos['exclude']))
                    ||
                    (isset($repos['include']) && in_array($theme['full_name'], $repos['include']))
                    ||
                    (!isset($repos['exclude']) && !isset($repos['include']))
                )
                    $themes[$theme['full_name']] = $theme['description'];
        }

        return $themes;
    }

    /**
     * Download any Github repository to the themes folder.
     *
     * @param String $author Github author
     * @param String $repo   Github repository
     * @param String $branch Repository branch
     */
    public function downloadTheme($author, $repo, $branch = 'master')
    {
        $git = new GitDownload($this->config['source_themes']);
        $git->clone($author, $repo, 'master');
    }
}
