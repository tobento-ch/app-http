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

namespace Tobento\App\Http\Exception;

use Throwable;

/**
 * ForbiddenException
 */
class ForbiddenException extends HttpException
{
    /**
     * Create a new ForbiddenException.
     *
     * @param string $message
     * @param int $code
     * @param null|Throwable $previous
     * @param array $headers
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null,
        protected array $headers = [],
    ) {
        parent::__construct(403, $message, $code, $previous);
    }
}