<?php
// ---------- Some bootstrapping logic here ----------



/**
 * =======================================================
 * 
 * It's just EXAMPLE for provided default layout
 * You can safely DELETE it if you'll create your own layout
 * 
 * =======================================================
 */

$fw = \Base::instance();

// Default SEO tags
$fw->set('v.title', 'Your app default title');
$fw->set('v.meta.description', 'Default description');
$fw->set('v.meta.keywords', 'default,keywords');

// Default header for <include /> tag
$fw->set('v.header', 'layouts/header.html');

/**
 * @return array
 */
function top_menu()
{
    $fw = \Base::instance();

    return [
        'Home' => BASE_URL,
        'Form' => '/form',
        'Json' => '/json',
        '404 page' => '/some-non-existing-link',
        'Without layout' => '/no-layout',
    ];
}
