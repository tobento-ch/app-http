<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    |
    | Specify the session settings for your application.
    |
    */
    
    'name' => 'sess',
    'factory' => \Tobento\App\Http\SessionFactory::class,
    'config' => [
        'maxlifetime' => 1800,
        //'cookiePath' => null,
        'cookieDomain' => '',
        'cookieSamesite' => 'Strict',
        'secure' => true,
        'httpOnly' => true,
        'saveHandler' => null,
        'validation' => null,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Middleware
    |--------------------------------------------------------------------------
    |
    | Specify the session middleware used to start and save session.
    |
    */
    
    'middleware' => \Tobento\Service\Session\Middleware\Session::class,
    
];