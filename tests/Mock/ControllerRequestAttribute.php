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

use Psr\Http\Message\ServerRequestInterface;

class ControllerRequestAttribute
{
    public function __construct(
        private ServerRequestInterface $request,
        private string $attributeName = 'name',
    ) {}
    
    public function __invoke(null|int $id = null): mixed
    {
        return $this->request->getAttribute($this->attributeName);
    }
}