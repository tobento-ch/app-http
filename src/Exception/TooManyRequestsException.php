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
 * TooManyRequestsException
 */
class TooManyRequestsException extends HttpException
{
    /**
     * Create a new TooManyRequestsException.
     *
     * @param int|string|null $retryAfter The number of seconds or the http-date after which the request may be retried.
     * @param string $message
     * @param int $code
     * @param null|Throwable $previous
     * @param array $headers
     */
    public function __construct(
        protected int|string|null $retryAfter = null,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null,
        protected array $headers = [],
    ) {
        if ($retryAfter) {
            $headers['Retry-After'] = (string)$retryAfter;
        }
        
        parent::__construct(429, $message, $code, $previous, $headers);
    }
    
    /**
     * Returns the retryAfter.
     *
     * @return int|string|null
     */
    public function retryAfter(): int|string|null
    {
        return $this->retryAfter;
    }    
}