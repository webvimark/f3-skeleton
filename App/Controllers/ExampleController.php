<?php
namespace App\Controllers;

use Base;

class ExampleController
{
    /**
     * Basic view example
     *
     * @param Base $fw
     */
    public function index(Base $fw)
    {
        if ($fw->exists('SESSION._flash', $flash)) {
            $fw->clear('SESSION._flash');
        }

        $fw->set('v.title', 'Custom page title');
        $fw->set('v.meta.description', 'Custom page description');

        echo view('example/index.html', [
            'randomText' => \Web::instance()->filler(3, 5, false),
            'flash' => $flash,
        ]);
    }

    /**
     * Form submission example
     *
     * @param Base $fw
     */
    public function form(Base $fw)
    {
        $fw->clear('v.header'); // example without header or you can set another header

        $errors = [];

        if ($fw->VERB === 'POST') {
            if (!($url = $fw->get('POST.url')) || !\Audit::instance()->url($url)) {
                $errors[] = 'Please enter valid url';
            }

            if (!$errors) {
                $fw->set('SESSION._flash', 'You submitted this url - ' . $url);
                $fw->reroute('/');
            }
        }

        echo view('example/form.html', compact('errors'));
    }
    
    /**
     * View without layout
     *
     * @param Base $fw
     */
    public function withoutLayout(Base $fw)
    {
        $fw->clear('APP.LAYOUT');
        echo view('example/index.html');
    }

    /**
     * Working with database - db() function
     * 
     * https://github.com/webvimark/pixie
     *
     * @param Base $fw
     */
    public function dbExample(Base $fw)
    {
        $result = db('some_table')
            ->where('name', 'test user')
            ->orWhere('age', '>', 3)
            ->get();

        $anotherResult = db()->table('another_table')->where('field', 'value')->get();
        
        // dd - pretty var_dump() and die()
        dd($result);
    }

    /**
     * Json response example
     *
     * @param Base $fw
     */
    public function jsonExample(Base $fw)
    {
        header('Content-Type: application/json');

        echo json_encode([
            'status' => 'success',
            'text' => "it's a json response example",
        ]);
    }
}