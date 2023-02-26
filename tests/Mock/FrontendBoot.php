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
 
namespace Tobento\App\Http\Test\Mock;

use Tobento\App\Http\AreaBoot;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Routing\RouterInterface;

class FrontendBoot extends AreaBoot
{
    public const INFO = [
        'boot' => [
            'Frontend',
        ],
    ];
    
    protected const AREA_BOOT = [
        \Tobento\App\Boot\App::class,
        \Tobento\App\Http\Boot\Middleware::class,
        \Tobento\App\Http\Boot\Routing::class,
    ];
    
    protected const AREA_KEY = 'frontend';
    
    protected const AREA_SLUG = '';
    
    /**
     * @var array<int, callable>
     */    
    protected array $afterHandlers = [];
    
    /**
     * Add handler after the area is booted.
     *
     * @param callable $handler
     * @return static
     */
    public function afterBooting(callable $handler): static
    {
        $this->afterHandlers[] = $handler;
        return $this;
    }
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param RouterInterface $router
     * @return void
     */
    public function boot(Migration $migration, RouterInterface $router): void
    {
        parent::boot($migration, $router);
        
        foreach($this->afterHandlers as $handler) {
            call_user_func_array($handler, [$this]);
        }
    }
}