<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Scope;

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

use FriendsOfTYPO3\SudoMode\Backend\RouteHandlerInterface;
use FriendsOfTYPO3\SudoMode\Backend\RequestMetaData;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;

class CoreJsonRouteHandler implements RouteHandlerInterface
{
    use RouteHandlerTrait;

    public function __construct()
    {
        $this->handlers = [
            // \TYPO3\CMS\Backend\Controller\SimpleDataHandlerController::processAjaxRequest
            '/ajax/record/process' => function(Route $route, ServerRequestInterface $request, RequestMetaData $metaData): RequestMetaData {
                return $metaData
                    ->withEventName('sudo-mode:confirmation-request')
                    ->withJsonData([]);
            },
        ];
    }

    public function canHandle(ServerRequestInterface $request, Route $route): bool
    {
        $routePath = $this->normalizeRoutePath($route);
        return in_array($routePath, array_keys($this->handlers), true);
    }

    public function resolveMetaData(ServerRequestInterface $request, Route $route): RequestMetaData
    {
        $routePath = $this->normalizeRoutePath($route);
        $metaData = (new RequestMetaData())->withScope('json');
        return $this->handlers[$routePath]($route, $request, $metaData);
    }
}
