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

use Tobento\Service\ErrorHandler\ThrowableHandlersInterface;

use Psr\Http\Message\ResponseInterface;
use Closure;

/**
 * ResponseEmitterInterface
 */
interface ResponseEmitterInterface
{
    /**
     * Add handler before the response is emitted.
     *
     * @param callable $handler
     * @return static
     */
    public function before(callable $handler): static;
    
    /**
     * Emit the specified response.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void;
}