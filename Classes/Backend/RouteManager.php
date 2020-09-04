<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Backend;

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

use FriendsOfTYPO3\SudoMode\Scope\CoreHtmlRouteHandler;
use FriendsOfTYPO3\SudoMode\Scope\CoreJsonRouteHandler;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RouteManager
{
    /**
     * @var RouteHandlerInterface[]
     * @todo Add possibility to register 3rd party handlers
     */
    protected $handlerClassNames = [
        CoreHtmlRouteHandler::class,
        CoreJsonRouteHandler::class,
    ];

    /**
     * @var RouteHandlerInterface[]
     */
    protected $handlers;

    public function __construct()
    {
        $this->handlers = array_map(
            function (string $className) {
                return GeneralUtility::makeInstance($className);
            },
            array_values($this->handlerClassNames)
        );
    }

    public function canHandle(ServerRequestInterface $request, Route $route): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($request, $route)) {
                return true;
            }
        }
        return false;
    }

    public function resolveMetaData(ServerRequestInterface $request, Route $route): ?RequestMetaData
    {
        foreach ($this->handlers as $handler) {
            if (!$handler->canHandle($request, $route)) {
                continue;
            }
            return $handler->resolveMetaData($request, $route);
        }
        return null;
    }
}
