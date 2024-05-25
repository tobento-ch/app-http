<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Middlewares
    |--------------------------------------------------------------------------
    |
    | These middlewares are applied to all routes and requests.
    |
    */
    
    'middlewares' => [
        // priority => middleware
        8000 => \Tobento\App\Http\Middleware\SecurePolicyHeaders::class,
    ],
    
    
    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | You may define middleware groups.
    |
    */
    
    'groups' => [
        'name' => [
            \MiddlewareFromGroup::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | The middleware aliases.
    |
    */
    
    'aliases' => [
        'foo' => \FooMiddlewareFromAliases::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Replace Middlewares
    |--------------------------------------------------------------------------
    |
    | You may replace any middleware with another or remove it at all,
    | when the middleware was added by the middleware boot.
    |
    */
    
    'replace' => [
        \ToReplaceMiddleware::class => \ReplacedMiddleware::class,
        \ToReplaceNullMiddleware::class => null,
    ],
];