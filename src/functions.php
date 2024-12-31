<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Http;

use Psr\Container\ContainerInterface;
use Tobento\Service\HelperFunction\Functions;
use Tobento\Service\Uri\AssetUriInterface;
use Tobento\Service\Uri\BaseUriInterface;

if (!function_exists(__NAMESPACE__.'\assetUri')) {
    /**
     * Returns the assetUri.
     *
     * @return AssetUriInterface
     */
    function assetUri(): AssetUriInterface
    {
        return Functions::get(ContainerInterface::class)->get(AssetUriInterface::class);
    }
}

if (!function_exists(__NAMESPACE__.'\baseUri')) {
    /**
     * Returns the baseUri.
     *
     * @return BaseUriInterface
     */
    function baseUri(): BaseUriInterface
    {
        return Functions::get(ContainerInterface::class)->get(BaseUriInterface::class);
    }
}