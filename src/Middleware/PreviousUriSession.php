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

namespace Tobento\App\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Uri\CurrentUriInterface;

/**
 * Starting, saving and adding Session to request.
 */
class PreviousUriSession implements MiddlewareInterface
{
    /**
     * Create a new PreviousUriSession.
     *
     * @param BaseUriInterface $baseUri
     */
    public function __construct(
        protected null|CurrentUriInterface $currentUri = null,
        protected null|RequesterInterface $requester = null,
    ) {}
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if (
            is_null($session)
            || is_null($this->requester)
            || is_null($this->currentUri)
        ) {
            return $handler->handle($request);
        }
        
        $response = $handler->handle($request);

        if (
            $this->requester->isReading()
            && ! $this->requester->isPrefetch()
            && ! $this->requester->isAjax()
        ) {
            $session->set('_previous_uri', (string)$this->currentUri);
        }
        
        return $response;
    }
}