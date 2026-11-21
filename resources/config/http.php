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
    | Application Hosts
    |--------------------------------------------------------------------------
    |
    | Sets the valid hosts to use in the application for secure urls.
    |
    */
    
    'hosts' => [
        '',
        'localhost',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Application Url
    |--------------------------------------------------------------------------
    |
    | The url used for setting the base uri of the application.
    |
    */
    
    'url' => 'http://localhost',
    
    /*
    |--------------------------------------------------------------------------
    | Application Routing Domains
    |--------------------------------------------------------------------------
    |
    | You may set domains for routing.
    | https://github.com/tobento-ch/service-routing#managing-domains
    |
    | The domains will be valid hosts too! No need to specify them again.
    |
    */
    
    'domains' => [
        /*[
            'key' => 'example.ch',
            'domain' => 'ch.localhost',
            'uri' => 'http://ch.localhost',
        ],
        [
            'key' => 'example.de',
            'domain' => 'de.localhost',
            'uri' => 'http://de.localhost',
        ],*/
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Signature Key For Signed Routing
    |--------------------------------------------------------------------------
    |
    | This key should be set to a random, 32 character string.
    |
    */
    
    'signature_key' => '{signature_key}',
    
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | The default directives of the Content-Security-Policy header.
    | Apps add their sources on the pages needing them,
    | e.g. a payment provider's script on the checkout.
    | "nonce" is replaced by the nonce of the request.
    | If empty, the default directives of the Csp class are used.
    |
    */
    
    'csp' => [
        'base-uri' => ['none'],
        'default-src' => ['self'],
        'img-src' => ['self', 'data:'],
        'script-src' => ['nonce', 'self'],
        'style-src' => ['nonce', 'self'],
        'object-src' => ['none'],
        'form-action' => ['self'],
        'frame-ancestors' => ['self'],
    ],
    
];