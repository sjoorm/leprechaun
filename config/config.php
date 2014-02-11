<?php
/** Default config for staging/production environment. */

return [
    'applicationName' => 'Leprechaun Application',
    'namespace' => 'leprechaun',
    'userModel' => 'leprechaun\\User',
    'siteUrl' => 'http://leprechaun.local/',
    'baseUrl' => '',
    'staticUrl' => 'static/',
    'include' => [
        'components/*',
        'models/*',
        'models/forms/*',
        'controllers/*',
    ],
    'cache' => [
        'class' => 'leprechaun\\Redis',
    ],
];
