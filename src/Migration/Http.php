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

namespace Tobento\App\Http\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\FileStringReplacer;
use Tobento\Service\Dir\DirsInterface;

/**
 * Http
 */
class Http implements MigrationInterface
{
    /**
     * @var array The files.
     */    
    protected array $files;
    
    /**
     * Create a new Migration.
     *
     * @param DirsInterface $dirs
     */    
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $this->files = [
            $this->dirs->get('config') => [
                realpath(__DIR__.'/../../').'/config/http.php',
                realpath(__DIR__.'/../../').'/config/session.php',
                realpath(__DIR__.'/../../').'/config/cookies.php',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */    
    public function description(): string
    {
        return 'Http config files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */    
    public function install(): ActionsInterface
    {        
        return new Actions(
            new FilesCopy(
                files: $this->files,
                type: 'config',
                description: 'Http config files.',
            ),
            new FileStringReplacer(
                file: $this->dirs->get('config').'http.php',
                replace: [
                    '{signature_key}' => base64_encode(random_bytes(32)),
                ],
                type: 'config',
                description: 'signature_key generation.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */    
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->files,
                type: 'config',
                description: 'Http config files.',
            ),
        );
    }
}