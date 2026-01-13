<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Http;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;

class SimpleResponseEmitter implements SimpleResponseEmitterInterface
{    
    /**
     * Emit the specified response to the client.
     *
     * Implementations may send headers, output the body,
     * and perform any finalization required by the environment.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface The emitted response.
     */
    public function emit(ResponseInterface $response): ResponseInterface
    {
        new SapiEmitter()->emit($response);
        
        return $response;
    }
}