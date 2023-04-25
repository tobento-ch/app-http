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
 
namespace Tobento\App\Http\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\App\Http\HttpErrorHandlers;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\ResponseEmitter;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\ErrorHandler\AutowiringThrowableHandlerFactory;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\Uri\BaseUri;
use Tobento\Service\Uri\BasePathResolver;
use Tobento\Service\Uri\CurrentUriInterface;
use Tobento\Service\Uri\CurrentUri;
use Tobento\Service\Uri\PreviousUriInterface;
use Tobento\Service\Uri\PreviousUri;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

/**
 * Http boot.
 */
class Http extends Boot
{
    public const INFO = [
        'boot' => [
            'PSR-7 implementations',
            'PSR-17 implementations',
            'installs and loads http config file',
            'base and current uri implementation',
            'http error handling implementation',
        ],
        'terminate' => [
            'emits response',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        Migration::class,
    ];
    
    public const REBOOTABLE = ['terminate'];
    
    /**
     * @var int Terminating count.
     */    
    protected int $terminated = 0;
    
    /**
     * @var null|ResponseInterface
     */
    protected null|ResponseInterface $response = null;
    
    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(Config $config, Migration $migration): void
    {
        // Install migrations.
        $migration->install(\Tobento\App\Http\Migration\Http::class);
        
        // Load the http configuration.
        $config = $config->load('http.php', 'http');
        
        // HttpErrorHandlers
        $this->app->set(HttpErrorHandlersInterface::class, function(ContainerInterface $container) {
            return new HttpErrorHandlers(
                new AutowiringThrowableHandlerFactory($container)
            );
        });

        // ResponseEmitter
        $this->app->set(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        // UriFactory
        $this->app->set(UriFactoryInterface::class, Psr17Factory::class);
        
        // ServerRequest
        $this->app->set(ServerRequestInterface::class, function() use ($config) {
            $psr17Factory = new Psr17Factory();
            
            $creator = new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory  // StreamFactory
            );
            
            $serverRequest = $creator->fromGlobals();
            $host = $serverRequest->getUri()->getHost();
            $validHosts = $config['hosts'] ?? ['', 'localhost'];
            
            if (!in_array($host, $validHosts)) {
                $uri = $serverRequest->getUri()->withHost($validHosts[0] ?? 'localhost');
                return $serverRequest->withUri($uri);
            }
                        
            return $serverRequest;
        });
        
        // ResponseFactory
        $this->app->set(ResponseFactoryInterface::class, Psr17Factory::class);
        
        // StreamFactory
        $this->app->set(StreamFactoryInterface::class, Psr17Factory::class);
        
        // UploadedFileFactory
        $this->app->set(UploadedFileFactoryInterface::class, Psr17Factory::class);        
        
        // Response
        $this->app->set(ResponseInterface::class, function() {
            return (new Psr17Factory())->createResponse(200);
        });
        
        // BaseUri
        $this->app->set(BaseUriInterface::class, function() {
            
            $request = $this->app->get(ServerRequestInterface::class);
            
            $uri = $request->getUri()
                           ->withPath((new BasePathResolver($request))->resolve())
                           ->withQuery('')
                           ->withFragment('');
            
            return new BaseUri($uri);
        });
        
        // CurrentUri
        $this->app->set(CurrentUriInterface::class, function() {
            
            $request = $this->app->get(ServerRequestInterface::class);
            
            $uri = $request->getUri();
            
            $baseUri = $this->app->get(BaseUriInterface::class);
            
            $isHome = rtrim((string) $baseUri, '/') === rtrim((string) $uri, '/');
            
            return new CurrentUri($uri, $isHome);
        });
        
        // PreviousUri
        $this->app->set(PreviousUriInterface::class, function() {
            return new PreviousUri($this->app->get(BaseUriInterface::class));
        });
    }
    
    /**
     * Terminate application services.
     *
     * @return void
     */
    public function terminate(): void
    {
        $this->terminated++;

        // Emit response:
        if ($this->terminated === $this->app->getRunCycles()) {
            $this->getResponseEmitter()->emit($this->getResponse());
        }
    }
    
    /**
     * Set the response to emit.
     *
     * @param ResponseInterface $response
     * @return static $this
     */
    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;
        return $this;
    }
    
    /**
     * Get the response to emit.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response ?: $this->app->get(ResponseInterface::class);
    }
    
    /**
     * Returns the response emitter.
     *
     * @return ResponseEmitterInterface
     */
    public function getResponseEmitter(): ResponseEmitterInterface
    {
        return $this->app->get(ResponseEmitterInterface::class);
    }
} 