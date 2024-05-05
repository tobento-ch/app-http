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

namespace Tobento\App\Http\Test\Mock;

use Tobento\App\Http\ResponseEmitter as DefaultResponseEmitter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ResponseEmitter
 */
class ResponseEmitter extends DefaultResponseEmitter
{
    /**
     * Emit the specified response.
     *
     * @param ResponseInterface $response
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function emit(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        foreach($this->beforeHandlers as $handler) {
            $response = $handler($response, $request);
        }
        
        return $response;
    }
}