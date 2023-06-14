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
        [
            'key' => 'example.ch',
            'domain' => 'ch.localhost',
            'uri' => 'http://ch.localhost',
        ],
        [
            'key' => 'example.de',
            'domain' => 'de.localhost',
            'uri' => 'http://de.localhost',
        ],
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
    
];