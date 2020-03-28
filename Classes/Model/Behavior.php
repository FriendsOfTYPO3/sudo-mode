<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal Note that this is not public API yet.
 */
class Behavior
{
    public function getTableNames(): array
    {
        return [
            'be_users',
            'be_groups',
        ];
    }

    public function getRoutePaths(): array
    {
        return [
            '/record/edit', // \TYPO3\CMS\Backend\Controller\EditDocumentController
            '/module/user/setup', // \TYPO3\CMS\Setup\Controller\SetupModuleController
            // returnUrl -> $moduleUri = (string)$uriBuilder->buildUriFromRoute('user_setup');
        ];
    }
}
