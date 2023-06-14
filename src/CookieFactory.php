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

use Tobento\Service\Cookie\CookieFactory as DefaultCookieFactory;
use Tobento\Service\Uri\BaseUriInterface;

/**
 * CookieFactory
 */
class CookieFactory extends DefaultCookieFactory
{
    /**
     * @var string
     */
    protected string $path = '/';
    
    /**
     * @var string
     */
    protected string $domain = '';
    
    /**
     * @var bool
     */
    protected bool $secure = true;
    
    /**
     * Create a new CookieFactory.
     *
     * @param null|BaseUriInterface $baseUri
     * @param string $sameSite
     */
    public function __construct(
        protected null|BaseUriInterface $baseUri = null,
        protected string $sameSite = 'Strict',
    ) {
        if ($baseUri) {
            $this->path = rtrim($baseUri->getPath(), '/').'/';
            $this->domain = $baseUri->withPort(null)->getHost();
            $this->secure = $baseUri->getScheme() === 'https' ? true : false;
        }
    }
}