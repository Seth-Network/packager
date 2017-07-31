<?php defined('SYSPATH') or die('No direct script access.');


Route::set('packager', 'packager/<file>', array(
    'file' => '.*'))
    ->defaults(array(
        'controller' => 'packager',
        'action' => 'get'
    ));


