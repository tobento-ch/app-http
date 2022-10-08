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

/**
 * ResponseEmitter
 */
class ResponseEmitter implements ResponseEmitterInterface
{
    /**
     * @var array<int, callable>
     */    
    protected array $beforeHandlers = [];
    
    /**
     * Add handler before the response is emitted.
     *
     * @param callable $handler
     * @return static
     */
    public function before(callable $handler): static
    {
        $this->beforeHandlers[] = $handler;
        return $this;
    }
    
    /**
     * Emit the specified response.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        foreach($this->beforeHandlers as $handler) {
            call_user_func($handler);
        }
        
        (new SapiEmitter())->emit($response);
    }
}