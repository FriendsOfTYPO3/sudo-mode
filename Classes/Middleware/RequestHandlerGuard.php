<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Middleware;

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

use FriendsOfTYPO3\SudoMode\Backend\RouteManager;
use FriendsOfTYPO3\SudoMode\Backend\VerificationController;
use FriendsOfTYPO3\SudoMode\Backend\VerificationException;
use FriendsOfTYPO3\SudoMode\Backend\VerificationHandler;
use FriendsOfTYPO3\SudoMode\Backend\VerificationRequest;
use FriendsOfTYPO3\SudoMode\LoggerAccessorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal Note that this is not public API yet.
 */
class RequestHandlerGuard implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LoggerAccessorTrait;

    protected $routeManager;

    public function __construct(RouteManager $routeManager = null)
    {
        $this->routeManager = $routeManager ?? GeneralUtility::makeInstance(RouteManager::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->resolveRoute($request);
        $shallGuard = $route !== null && $this->routeManager->canHandle($request, $route);

        try {
            return $handler->handle($request);
        } catch (VerificationException $exception) {
            $verificationRequest = $exception->getVerificationRequest();
            $loggerContext = $this->createLoggerContext($route, $verificationRequest, $this->getBackendUser());

            if ($shallGuard) {
                $this->logger->info('Handled verification request', $loggerContext);
                return $this->handle($request, $route, $exception->getVerificationRequest());
            }

            $this->logger->notice('Unhandled verification request', $loggerContext);
            throw $exception;
        }
    }

    protected function handle(ServerRequestInterface $request, Route $route, VerificationRequest $verificationRequest)
    {
        GeneralUtility::makeInstance(VerificationHandler::class)
            ->commitRequestInstruction($verificationRequest, $this->getBackendUser(), $request);
        $routeMetaData = $this->routeManager->resolveMetaData($request, $route);
        $uri = GeneralUtility::makeInstance(VerificationController::class)
            ->buildUriForRequestAction($verificationRequest, $routeMetaData->getReturnUrl());
        return GeneralUtility::makeInstance(RedirectResponse::class, $uri, 401);
    }

    protected function resolveRoute(ServerRequestInterface $request): ?Route
    {
        $route = $request->getAttribute('route', null);
        if ($route !== null) {
            return $route;
        }
        try {
            $router = GeneralUtility::makeInstance(Router::class);
            return $router->matchRequest($request);
        } catch (ResourceNotFoundException $exception) {
            return null;
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        // @todo Make use of context
        $context = GeneralUtility::makeInstance(Context::class);
        return $GLOBALS['BE_USER'];
    }
}
