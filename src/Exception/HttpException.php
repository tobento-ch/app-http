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

use RuntimeException;
use Throwable;

/**
 * HttpException
 */
class HttpException extends RuntimeException
{
    /**
     * Create a new HttpException.
     *
     * @param int $statusCode
     * @param string $message Any message for the client.
     * @param int $code
     * @param null|Throwable $previous
     * @param array $headers
     */
    public function __construct(
        protected int $statusCode,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null,
        protected array $headers = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the status code.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Returns the headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }
}