#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$fw = \Base::instance();

define('BASE_URL', '');
define('BASE_DIR', __DIR__);

if (!is_file(BASE_DIR . '/configs/env.ini')) {
    die('Missing "/configs/env.ini" file');
}

$fw->config(BASE_DIR . '/configs/config.ini', true);
$fw->config(BASE_DIR . '/configs/env.ini');

$fw->set('DEBUG', 0);
$fw->set('LOGS', $fw->TEMP .'logs_cli/');

require_once BASE_DIR . '/App/helpers.php';
require_once BASE_DIR . '/App/bootstrap.php';

$fw->route('GET /test', function () use ($fw) {
    $fw->config(BASE_DIR . '/configs/routes.ini');

    // Envoke test handler here
});

$fw->run();