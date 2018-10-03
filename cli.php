#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$fw = \Base::instance();

define('BASE_URL', '');
define('BASE_DIR', __DIR__);
define('WEB_DIR', BASE_DIR . '/www');

if (!is_file(BASE_DIR . '/configs/env.ini')) {
    die('Missing "/configs/env.ini" file');
}

$fw->config(BASE_DIR . '/configs/config.ini', true);
$fw->config(BASE_DIR . '/configs/env.ini');

$fw->set('DEBUG', 0);
$fw->set('SEED', 'cli_' . $fw->SEED);
$fw->set('LOGS', $fw->TEMP .'logs_cli/');

require_once BASE_DIR . '/App/helpers.php';
require_once BASE_DIR . '/App/bootstrap.php';

$fw->run();