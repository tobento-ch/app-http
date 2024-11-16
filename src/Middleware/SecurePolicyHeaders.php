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
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\Service\View\ViewInterface;

/**
 * SecurePolicyHeaders
 */
class SecurePolicyHeaders implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected string $nonce;
    
    /**
     * @var bool
     */
    protected bool $headersAdded = false;
    
    /**
     * Create a new SecurePolicyHeaders.
     *
     * @param AppInterface $app
     */
    public function __construct(
        AppInterface $app,
    ) {
        $this->nonce = $this->generateNonce();
        
        $app->on(ViewInterface::class, function (ViewInterface $view): void {
            $view->with('cspNonce', $this->nonce);
        });
        
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
            $request = $request->withAttribute('csp_nonce', $this->nonce);
        }
        
        $response = $handler->handle($request);
        
        $this->headersAdded = true;
        
        return $this->addCspHeaders($response);
    }

    /**
     * Add CSP header to response.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function addCspHeaders(ResponseInterface $response): ResponseInterface
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
        // https://www.owasp.org/index.php/HTTP_Strict_Transport_Security_Cheat_Sheet        
        $response = $response->withHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
        $response = $response->withHeader(
            'Content-Security-Policy',
            'base-uri \'none\'; default-src \'self\'; img-src \'self\' data:; script-src \'nonce-'.$this->nonce.'\' \'self\'; object-src \'none\'; style-src \'nonce-'.$this->nonce.'\' \'self\''
        );

        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
        $response = $response->withHeader('X-Frame-Options', 'DENY');
        
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options    
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');
                
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
        $response = $response->withHeader('Referrer-Policy', 'same-origin');
        
        return $response;
    }
    
    /**
     * Generates a nonce.
     *
     * @return string
     */
    protected function generateNonce(): string
    {
        return base64_encode(random_bytes(32));
    }
}