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

use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionException;
use Closure;

/**
 * ArgumentsHandlerParameters
 */
class ArgumentsHandlerParameters
{
    /**
     * Create a new ArgumentsHandlerParameters.
     *
     * @param array $parameters
     */
    public function __construct(
        protected array $parameters,
    ) {}

    /**
     * Add a parameter value.
     *
     * @param null|int|string $key Name or position of parameter.
     * @param mixed $value
     * @return static $this
     */
    public function add(null|int|string $key, mixed $value): static
    {
        if (is_null($key)) {
            $this->parameters[] = $value;
        } else {
            $this->parameters[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Remove a parameter by name or position.
     *
     * @param int|string $key
     * @return static $this
     */
    public function remove(int|string $key): static
    {
        unset($this->parameters[$key]);
        return $this;
    }
    
    /**
     * Returns the arguments handler parameters to be called.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}