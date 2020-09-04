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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CoreHtmlRouteHandler implements RouteHandlerInterface
{
    use RouteHandlerTrait;

    public function __construct()
    {
        // TYPO3 v10
        $this->handlers = [
            // \TYPO3\CMS\Backend\Controller\EditDocumentController
            '/record/edit' => function(Route $route, ServerRequestInterface $request): string {
                $parsedBody = $request->getParsedBody();
                $queryParams = $request->getQueryParams();
                return GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? null);
            },
            // \TYPO3\CMS\Setup\Controller\SetupModuleController
            '/module/user/setup' => function(Route $route, ServerRequestInterface $request): string {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $routeIdentifier = $route->getOption('_identifier');
                return (string)$uriBuilder->buildUriFromRoute($routeIdentifier);
            },
            // \TYPO3\CMS\Backend\Controller\Controller\ContentElement\ElementHistoryController
            '/record/history' => function(Route $route, ServerRequestInterface $request): string {
                $queryParams = $request->getQueryParams();
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $routeIdentifier = $route->getOption('_identifier');
                return (string)$uriBuilder->buildUriFromRoute($routeIdentifier, [
                    'element' => $queryParams['element'] ?? '',
                    'historyEntry' => $queryParams['historyEntry'] ?? '',
                ]);
            },
        ];

        // TYPO3 v9
        $this->handlers['/user/setup'] = $this->handlers['/module/user/setup'];
    }

    public function canHandle(ServerRequestInterface $request, Route $route): bool
    {
        $routePath = $this->normalizeRoutePath($route);
        return in_array($routePath, array_keys($this->handlers), true);
    }

    public function resolveMetaData(ServerRequestInterface $request, Route $route): RequestMetaData
    {
        $routePath = $this->normalizeRoutePath($route);
        $returnUrl = $this->handlers[$routePath]($route, $request);
        return (new RequestMetaData())
            ->withScope('html')
            ->withReturnUrl($returnUrl);
    }
}
