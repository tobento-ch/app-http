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
use Tobento\App\AppInterface;
use Tobento\App\Http\CspInterface;
use Tobento\App\Http\ResponseEmitterInterface;

/**
 * SecurePolicyHeaders
 */
class SecurePolicyHeaders implements MiddlewareInterface
{
    /**
     * @var bool
     */
    protected bool $headersAdded = false;
    
    /**
     * Create a new SecurePolicyHeaders.
     *
     * @param AppInterface $app
     * @param CspInterface $csp
     * @param array<string, string> $headers The headers to add, except the Content-Security-Policy header.
     */
    public function __construct(
        AppInterface $app,
        protected CspInterface $csp,
        protected array $headers = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ],
    ) {
        // If an Exception is thrown in the middleware process we add CSP header on response emitting:
        $app->on(ResponseEmitterInterface::class, function(ResponseEmitterInterface $emitter): void {
            if (! $this->headersAdded) {
                $emitter->before(function(ResponseInterface $response): ResponseInterface {
                    return $this->addCspHeaders($response);
                });                
            }
        });
    }
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (empty($request->getAttribute('csp_nonce'))) {
            $request = $request->withAttribute('csp_nonce', $this->csp->nonce());
        }
        
        $response = $handler->handle($request);
        
        $this->headersAdded = true;
        
        return $this->addCspHeaders($response);
    }

    /**
     * Add CSP header to response.
     *
     * A header the response has set itself is kept, e.g. by a route allowing other framing.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function addCspHeaders(ResponseInterface $response): ResponseInterface
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
        $csp = $this->csp->toHeaderValue();
        
        if ($csp !== '' && ! $response->hasHeader('Content-Security-Policy')) {
            $response = $response->withHeader('Content-Security-Policy', $csp);
        }
        
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
        foreach ($this->headers as $name => $value) {
            if (is_string($name) && is_string($value) && ! $response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }
        
        return $response;
    }
}