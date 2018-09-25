<?php
require __DIR__ . '/../vendor/autoload.php';
$fw = \Base::instance();

$port = in_array($fw->PORT, [80, 443]) ? '' : ':' . $fw->PORT;
define('BASE_URL', $fw->SCHEME . '://' . $fw->HOST . $fw->BASE . $port);
define('BASE_DIR', dirname(__DIR__));
define('WEB_DIR', __DIR__);

if (!is_file(BASE_DIR . '/configs/env.ini')) {
    die('Missing "/configs/env.ini" file');
}

$fw->config(BASE_DIR . '/configs/config.ini', true);
$fw->config(BASE_DIR . '/configs/env.ini');
$fw->config(BASE_DIR . '/configs/routes.ini');

require_once BASE_DIR . '/App/helpers.php';
require_once BASE_DIR . '/App/bootstrap.php';

$fw->run();