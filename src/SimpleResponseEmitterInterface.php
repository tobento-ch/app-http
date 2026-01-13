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

use Psr\Http\Message\ResponseInterface;

interface SimpleResponseEmitterInterface
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
    public function emit(ResponseInterface $response): ResponseInterface;
}