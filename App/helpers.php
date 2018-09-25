<?php

/**
 * Return DB instance. Create it when accessed first time
 * 
 * db($tables) is equal to db()->table($tables)
 * 
 * https://github.com/usmanhalalit/pixie
 * 
 * @param string|array|null $tables
 * 
 * @return \App\Handlers\DbQueryHandler
 */
function db($tables = null)
{
    $fw = \Base::instance();

    if (!$fw->exists('_HELPERS.qb', $qb)) {
        $connection = new \Pixie\Connection($fw->get('DB.config.driver'), $fw->get('DB.config'));
        $qb = new \App\Handlers\DbQueryHandler($connection);
        $fw->set('_HELPERS.qb', $qb);
    }

    return $tables ? $qb->table($tables) : $qb;
}

/**
 * Pretty print var_dump()
 *
 * @param mixed ...$args
 */
if (!function_exists('dd')) {
    function dd()
    {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        echo '</pre>';
        die;
    }
}

/**
 * Render template
 *
 * @param string $template
 * @param array $data
 * @return string
 */
function view($template, array $data = [])
{
    $fw = \Base::instance();
    $fw->set('v.content', $template);

    foreach ($data as $key => $value) {
        $fw->set($key, $value);
    }

    $view = $fw->get('APP.LAYOUT') && !$fw->ajax() ? $fw->get('APP.LAYOUT') : $template;

    return \Template::instance()->render($view);
}

/**
 * Checking if link is active and returning "active" class for menu
 *
 * @param string $url - url without query params
 * @param string $activeClass
 * @param array $matchQuery - if "/url?query=param" should be active then ['query'=>'param']
 * @return string
 */
function check_active_link($url, array $matchQuery = [], $activeClass = 'active')
{
    if ($url === BASE_URL) {
        $url = '/';
    } else {
        $url = strtr($url, [BASE_URL => '']);
    }
    $fw = \Base::instance();

    if ($fw->PATH == $url && array_intersect_assoc($matchQuery, $fw->GET) == $matchQuery) {
        return $activeClass;
    }
    return '';
}

/**
 * Combine CSS or JS files to the one file and add timestamp to the url.
 *
 * @see \App\Handlers\AssetsHandler::getCombineLink() for examples
 * @return string
 */
function combine()
{
    return \App\Handlers\AssetsHandler::getCombineLink(func_get_args());
}