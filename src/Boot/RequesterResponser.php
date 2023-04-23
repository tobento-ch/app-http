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
use Tobento\App\Http\Boot\Middleware;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Responser\Responser;
use Tobento\Service\Responser\RendererInterface;
use Tobento\Service\Responser\ViewRenderer;
use Tobento\Service\Responser\SessionStorage;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Message\MessagesFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * RequesterResponser boot.
 */
class RequesterResponser extends Boot
{
    public const INFO = [
        'boot' => [
            RequesterInterface::class.' implementation',
            ResponserInterface::class.' implementation and adds its middleware',
        ],
    ];
    
    public const BOOT = [
        Middleware::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Middleware $middleware
     * @return void
     */
    public function boot(Middleware $middleware): void
    {
        // Requester
        $this->app->set(RequesterInterface::class, Requester::class)->prototype();
        
        // Responser
        $this->app->set(ResponserInterface::class, function() {
            
            $renderer = null;
            $storage = null;
            $messages = null;
            
            if ($this->app->has(ViewInterface::class)) {
                $renderer = new ViewRenderer($this->app->get(ViewInterface::class));
            }
            
            if ($this->app->has(SessionInterface::class)) {
                $storage = new SessionStorage($this->app->get(SessionInterface::class));
            }
            
            if ($this->app->has(MessagesFactoryInterface::class)) {
                $messages = $this->app->get(MessagesFactoryInterface::class)->createMessages();
            }
            
            return new Responser(
                responseFactory: $this->app->get(ResponseFactoryInterface::class),
                streamFactory: $this->app->get(StreamFactoryInterface::class),
                renderer: $renderer,
                storage: $storage,
                messages: $messages,
            );
        });
        
        // Responser middleware
        $middleware->add(\Tobento\Service\Responser\Middleware\Responser::class, priority: 4000);
        $middleware->add(\Tobento\Service\Responser\Middleware\ResponserMergeInput::class, priority: 3500);
    }
}