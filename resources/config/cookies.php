<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\Service\Cookie;
use Tobento\Service\Encryption;
use Psr\Container\ContainerInterface;

return [
    
    /*
    |--------------------------------------------------------------------------
    | Middlewares
    |--------------------------------------------------------------------------
    |
    | The middlewares.
    |
    */
    
    'middlewares' => [
        Cookie\Middleware\Cookies::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names as it may be used in other app bundles!
    |
    */
    
    'interfaces' => [
        
        Cookie\CookiesFactoryInterface::class => Cookie\CookiesFactory::class,
        
        Cookie\CookieFactoryInterface::class => \Tobento\App\Http\CookieFactory::class,
        
        Cookie\CookieValuesFactoryInterface::class => Cookie\CookieValuesFactory::class,

        Cookie\CookiesProcessorInterface::class => Cookie\CookiesProcessor::class,
        
        /* 
        // you may use a specified encrypter only for cookies:
        
        Cookie\CookiesProcessorInterface::class => static function(ContainerInterface $c): Cookie\CookiesProcessorInterface {
            
            $encrypter = null;

            if (
                $c->has(Encryption\EncryptersInterface::class)
                && $c->get(Encryption\EncryptersInterface::class)->has('cookies')
            ) {
                $encrypter = $c->get(Encryption\EncryptersInterface::class)->get('cookies');
            }

            return new Cookie\CookiesProcessor(
                encrypter: $encrypter,
                whitelistedCookies: [],
            );
        },
        */
    ],
];