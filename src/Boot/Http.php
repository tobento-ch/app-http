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
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\App\Http\HttpErrorHandlers;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\ResponseEmitter;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\ErrorHandler\AutowiringThrowableHandlerFactory;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Config\ConfigLoadException;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\Uri\BaseUri;
use Tobento\Service\Uri\BasePathResolver;
use Tobento\Service\Uri\CurrentUriInterface;
use Tobento\Service\Uri\CurrentUri;
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
        \Tobento\App\Boot\Config::class,
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
     * @param Migration $migration
     * @return void
     */
    public function boot(Migration $migration): void
    {
        // Install migrations.
        $migration->install(\Tobento\App\Http\Migration\Http::class);
        
        // Load the app configuration.
        $config = $this->app->get(ConfigInterface::class);
        
        try {
            $config->load('http.php', 'http');
        } catch (ConfigLoadException $e) {
            // ignore
        }
        
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
        $this->app->set(ServerRequestInterface::class, function() {
                
            $psr17Factory = new Psr17Factory();
            
            $creator = new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory  // StreamFactory
            );

            $serverRequest = $creator->fromGlobals();
            
            $config = $this->app->get(ConfigInterface::class);
            
            // set host for security reason from config.
            $uri = $serverRequest->getUri()
                                 ->withHost($config->get('http.host', 'localhost'));
            
            return $serverRequest->withUri($uri);
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