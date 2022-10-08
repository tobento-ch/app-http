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

use Tobento\Service\Session\SessionFactory as DefaultSessionFactory;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Session\Validations;
use Tobento\Service\Session\HttpUserAgentValidation;
use Tobento\Service\Session\RemoteAddrValidation;
use Tobento\Service\Uri\BaseUriInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * SessionFactory
 */
class SessionFactory extends DefaultSessionFactory
{
    /**
     * Create a new SessionFactory.
     *
     * @param ServerRequestInterface $request
     * @param null|BaseUriInterface $baseUri
     */
    public function __construct(
        protected ServerRequestInterface $request,
        protected null|BaseUriInterface $baseUri = null,
    ) {}
    
    /**
     * Create a new Session.
     *
     * @param string $name
     * @param array $config
     * @return SessionInterface
     */
    public function createSession(string $name, array $config = []): SessionInterface
    {
        if ($this->baseUri && !isset($config['cookiePath'])) {
            $basePath = $this->baseUri->getPath();
            $config['cookiePath'] = rtrim($basePath, '/').'/';
        }
        
        if (!isset($config['validation'])) {
            $config['validation'] = new Validations(
                new RemoteAddrValidation($this->request->getServerParams()['REMOTE_ADDR'] ?? null),
                new HttpUserAgentValidation($this->request->getServerParams()['HTTP_USER_AGENT'] ?? null),
            );
        }
        
        return parent::createSession($name, $config);
    }
}