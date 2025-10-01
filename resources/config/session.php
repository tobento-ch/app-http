<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Session\HttpUserAgentValidation;
use Tobento\Service\Session\RemoteAddrValidation;
use Tobento\Service\Session\ValidationInterface;
use Tobento\Service\Session\Validations;

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
        /*'validation' => static function(ServerRequestInterface $request): ValidationInterface {
            return new Validations(
                new RemoteAddrValidation($request->getServerParams()['REMOTE_ADDR'] ?? null),
                new HttpUserAgentValidation($request->getServerParams()['HTTP_USER_AGENT'] ?? null),
            );
        },*/
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Specific Middlewares
    |--------------------------------------------------------------------------
    |
    | The middlewares.
    |
    */
    
    'middlewares' => [
        
        // The session middleware used to start and save session.
        \Tobento\Service\Session\Middleware\Session::class,
        
        \Tobento\App\Http\Middleware\PreviousUriSession::class,
    ],

];