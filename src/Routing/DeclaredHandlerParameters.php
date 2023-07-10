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

namespace Tobento\App\Http\Routing;

use ReflectionParameter;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;
use Closure;

/**
 * DeclaredHandlerParameters
 */
class DeclaredHandlerParameters
{
    /**
     * @var null|array<int, ReflectionParameter>
     */
    protected null|array $parameters = null;
    
    /**
     * Create a new DeclaredHandlerParameters.
     *
     * @param mixed $handler
     */
    public function __construct(
        protected mixed $handler,
    ) {}

    /**
     * Returns the declared handler parameters.
     *
     * @return array<int, ReflectionParameter>
     */
    public function getParameters(): array
    {
        // supports the following route handler definitions:
        // function {}
        // [Controller::class, 'method']
        // [Controller::class, 'method', ['name' => 'value']]
        // 'Controller::method'
        // Controller::class (using invoke method)
        
        if (is_array($this->parameters)) {
            return $this->parameters;
        }
        
        if ($this->handler instanceof Closure) {
            $function = new ReflectionFunction($this->handler);
            return $function->getParameters();
        }
        
        if (is_array($this->handler)) {
            $object = $this->handler[0] ?? null;
            $method = $this->handler[1] ?? '__invoke';
            
            if (is_null($object)) {
                return $this->parameters = [];
            }
            
            try {
                return $this->parameters = (new ReflectionMethod($object, $method))->getParameters();
            } catch (ReflectionException $e) {
                return $this->parameters = [];
            }
        }
        
        if (is_string($this->handler)) {
            if (str_contains($this->handler, '::')) {
                [$object, $method] = explode('::', $this->handler, 2);
            } else {
                $object = $this->handler;
                $method = '__invoke';
            }
            
            try {
                return $this->parameters = (new ReflectionMethod($object, $method))->getParameters();
            } catch (ReflectionException $e) {
                return $this->parameters = [];
            }
        }
        
        return $this->parameters = [];
    }
}