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

use InvalidArgumentException;
use Stringable;

/**
 * The Content Security Policy of the current request.
 */
interface CspInterface extends Stringable
{
    /**
     * Adds sources to a directive, e.g. add('frame-src', 'https://js.stripe.com').
     *
     * Keywords such as self or none may be written without quotes,
     * and the source nonce is replaced by the nonce of the request.
     *
     * @param string $directive
     * @param string ...$sources
     * @return static $this
     * @throws InvalidArgumentException If the directive or a source is invalid.
     */
    public function add(string $directive, string ...$sources): static;

    /**
     * Returns the nonce for inline scripts and styles.
     *
     * @return string
     */
    public function nonce(): string;

    /**
     * Returns the directives with their sources as added, e.g. ['script-src' => ['nonce', 'self']].
     *
     * @return array<string, list<string>>
     */
    public function directives(): array;

    /**
     * Returns the policy as value of the Content-Security-Policy header,
     * or an empty string if there are no directives.
     *
     * @return string
     */
    public function toHeaderValue(): string;
}