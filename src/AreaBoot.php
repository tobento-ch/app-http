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

use Tobento\App\Boot;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\Boot\Routing;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlInterface;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Config\ConfigLoadException;
use Psr\Http\Message\ResponseInterface;

/**
 * AreaBoot
 */
abstract class AreaBoot extends Boot
{
    public const BOOT = [
        Middleware::class,
        Routing::class,
    ];
    
    protected const AREA_BOOT = [
        //
    ];
    
    protected const AREA_KEY = 'area';
    
    protected const AREA_SLUG = 'area';
    
    protected const AREA_DOMAIN = '';
    
    protected const MIGRATION = '';
    
    /**
     * @var array The registered boots.
     */
    protected array $boots = [];
    
    /**
     * @var null|AppInterface
     */
    protected null|AppInterface $areaApp = null;
    
    /**
     * @var bool
     */
    protected bool $routed = false;
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param RouterInterface $router
     * @return void
     */
    public function boot(Migration $migration, RouterInterface $router): void
    {
        $this->bootRootAppDirs($this->app);
        
        if (static::MIGRATION) {
            $migration->install(static::MIGRATION);
        }
        
        $config = $this->app->get(ConfigInterface::class);
        
        try {
            $config->load($this->areaKey().'.php', $this->areaKey());
        } catch (ConfigLoadException $e) {
            // ignore
        }
        
        $this->routing($router, $config);
    }

    /**
     * Returns the app.
     *
     * @return AppInterface
     */
    public function app(): AppInterface
    {
        if (is_null($this->areaApp)) {
            // Create a new app:
            $app = (new AppFactory())->createApp(dirs: $this->app->dirs());
            
            // Bind backend to app.
            $app->set($this::class, $this);
            
            // Boot dirs:
            $this->bootAppDirs($app);
            
            // Adjust base uri on router:
            $app->on(RouterInterface::class, function(RouterInterface $router) {
                
                $slug = $this->areaSlug();
                
                if (!is_null($this->areaDomain())) {
                    $slug = '';
                }
                
                $router->setBaseUri($slug.'/');
                
                $urlGenerator = $router->getUrlGenerator();
                $urlBase = rtrim($urlGenerator->getUrlBase(), '/').'/'.$slug;
                $urlGenerator->setUrlBase($urlBase);
            });
            
            // Add booting:
            $app->boot(...static::AREA_BOOT);
            
            $app->booting();
            
            $this->areaApp = $app;
        }
        
        return $this->areaApp;
    }
    
    /**
     * Returns the root app.
     *
     * @return AppInterface
     */
    public function rootApp(): AppInterface
    {
        return $this->app;
    }
    
    /**
     * Register a boot or multiple. 
     *
     * @param mixed $boots
     * @return static $this
     */
    public function addBoot(mixed ...$boots): static
    {
        if ($this->routed) {
            $this->app()->boot(...$boots);
            return $this;
        }
        
        $this->boots[] = $boots;
        return $this;
    }

    /**
     * Returns the area key.
     *
     * @return string
     */
    public function areaKey(): string
    {
        return static::AREA_KEY;
    }
    
    /**
     * Returns the area name.
     *
     * @return string
     */
    public function areaName(): string
    {
        return ucfirst(static::AREA_KEY);
    }
    
    /**
     * Returns the area slug.
     *
     * @return string
     */
    public function areaSlug(): string
    {
        $slug = static::AREA_SLUG;

        if ($this->app->has(ConfigInterface::class)) {
            $config = $this->app->get(ConfigInterface::class);
            $slug = $config->get($this->areaKey().'.slug', $slug);
        }
        
        return $slug;
    }
    
    /**
     * Returns the area domain.
     *
     * @return null|string
     */
    public function areaDomain(): null|string
    {
        $domain = static::AREA_DOMAIN;

        if ($this->app->has(ConfigInterface::class)) {
            $config = $this->app->get(ConfigInterface::class);
            $domain = $config->get($this->areaKey().'.domain', '');
        }
        
        return empty($domain) ? null : $domain;
    }
    
    /**
     * Returns the area url.
     *
     * @return UrlInterface
     */
    public function url(): UrlInterface
    {
        return $this->rootApp()->get(RouterInterface::class)->url(static::AREA_KEY);        
    }

    /**
     * Booting area.
     *
     * @return static $this
     */
    public function booting(): static
    {
        if (!empty($this->boots)) {
            $this->app()->boot(...$this->boots);
            $this->boots = [];            
        }

        $this->app()->booting();
        return $this;
    }
    
    /**
     * Route handler.
     *
     * @return ResponseInterface
     */
    public function routeHandler(): ResponseInterface
    {
        $this->routed = true;
        
        $this->booting();
        
        if (is_null($this->app()->booter()->getBoot(Http::class))) {
            $this->app()->boot(Http::class);
            $this->app()->booting();
        }
        
        $this->app()->get(Http::class)->getResponseEmitter()->after(function() {
            exit;
        });
        
        $this->app()->run();
        
        return $this->app()->get(Http::class)->getResponse();
    }
    
    /**
     * Routing.
     *
     * @param RouterInterface $router
     * @param ConfigInterface $config
     * @return void
     */
    protected function routing(RouterInterface $router, ConfigInterface $config): void
    {
        if (!is_null($domain = $this->areaDomain())) {
            // we skip slug at all if domain is set:
            $router->route('*', '{?path*}', [$this, 'routeHandler'])
                ->name($this->areaKey())
                ->domain($domain)
                ->where('path', '[^?]*')
                ->parameter('area', $this->areaKey());
            
            return;
        }
        
        $slug = $this->areaSlug();
        
        if ($slug === '') {
            $router->route('*', '{?path*}', [$this, 'routeHandler'])
                ->name($this->areaKey())
                ->where('path', '[^?]*')
                ->parameter('area', $this->areaKey());
            
            return;
        }
        
        $uri = $slug.'/{?path*}';
        
        $router->route('*', $uri, [$this, 'routeHandler'])
            ->name($this->areaKey())
            ->where('path', '[^?]*')
            ->parameter('area', $this->areaKey());
    }

    /**
     * Boot app dirs.
     *
     * @param AppInterface $app
     * @return void
     */
    protected function bootAppDirs(AppInterface $app): void
    {
        if ($this->app->getEnvironment() !== 'production') {
            $app->dirs()->dir(
                dir: $app->dir('config.'.$this->app->getEnvironment()).'/'.$this->areaKey().'/',
                name: 'config.'.$this->app->getEnvironment(),
                group: 'config',
                priority: 110,
            );
        }
        
        $app->dirs()->dir(
            dir: $app->dir('app').'config/'.$this->areaKey().'/',
            name: 'config.'.$this->areaKey(),
            group: 'config',
            priority: 100,
        );
    }
    
    /**
     * Boot root app dirs.
     *
     * @param AppInterface $app
     * @return void
     */
    protected function bootRootAppDirs(AppInterface $app): void
    {
        $app->dirs()->dir(
            dir: $app->dir('app').'config/'.$this->areaKey().'/',
            name: 'config.'.$this->areaKey(),
            group: 'config.'.$this->areaKey(),
            priority: 100,
        );
    }
}