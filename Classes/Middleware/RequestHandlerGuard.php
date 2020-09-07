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

use FriendsOfTYPO3\SudoMode\Backend\ConfirmationBundle;
use FriendsOfTYPO3\SudoMode\Backend\RouteManager;
use FriendsOfTYPO3\SudoMode\Backend\ConfirmationController;
use FriendsOfTYPO3\SudoMode\Backend\ConfirmationException;
use FriendsOfTYPO3\SudoMode\Backend\ConfirmationHandler;
use FriendsOfTYPO3\SudoMode\Http\ServerRequestInstruction;
use FriendsOfTYPO3\SudoMode\Http\ServerRequestInstructionException;
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
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware catching and handling Sudo Mode confirmation requests.
 */
class RequestHandlerGuard implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LoggerAccessorTrait;

    /**
     * @var ConfirmationHandler
     */
    protected $confirmationHandler;

    /**
     * @var RouteManager
     */
    protected $routeManager;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ConfirmationException $exception) {
            return $this->handleConfirmationException($exception, $request);
        } catch (ServerRequestInstructionException $exception) {
            $request = ServerRequestFactory::fromGlobals();
            $request = $exception->getInstruction()->applyTo($request);
            // populate request aspects to super globals
            $_GET = $request->getQueryParams() ?? [];
            $_POST = $request->getParsedBody() ?? [];
            return $handler->handle($request);
        }
    }

    protected function handleConfirmationException(ConfirmationException $exception, ServerRequestInterface $request)
    {
        $route = $this->resolveRoute($request);
        $bundle = $exception->getConfirmationBundle();
        $loggerContext = $this->createLoggerContext($route, $bundle, $this->getBackendUser());

        $routeManager = $this->getRouteManager();
        $shallGuard = $route !== null && $routeManager->canHandle($request, $route);

        if ($shallGuard) {
            $this->logger->info('Handled verification request', $loggerContext);
            $bundle = $bundle
                ->withRequestInstruction(ServerRequestInstruction::fromServerRequest($request))
                ->withRequestMetaData($routeManager->resolveMetaData($request, $route));
            return $this->processBundle($bundle);
        }

        $this->logger->notice('Unhandled verification request', $loggerContext);
        throw $exception;
    }

    protected function processBundle(ConfirmationBundle $bundle): ResponseInterface
    {
        $this->getConfirmationHandler()
            ->commitConfirmationBundle($bundle, $this->getBackendUser());
        $uri = GeneralUtility::makeInstance(ConfirmationController::class)
            ->buildActionUriFromBundle('request', $bundle);

        if ($bundle->getRequestMetaData()->getScope() === 'json') {
            $eventName = $bundle->getRequestMetaData()->getEventName();
            $eventData = $bundle->getRequestMetaData()->getJsonData();
            return GeneralUtility::makeInstance(
                JsonResponse::class,
                [
                    'uri' => (string)$uri,
                    'data' => $eventData,
                ],
                403,
                $eventName ? ['X-TYPO3-EmitEvent' => $eventName] : []
            );
        } else {
            return GeneralUtility::makeInstance(RedirectResponse::class, $uri, 401);
        }
    }

    protected function resolveRoute(ServerRequestInterface $request): ?Route
    {
        try {
            $routePath = $request->getQueryParams()['route'] ?? '';
            $router = GeneralUtility::makeInstance(Router::class);
            return $router->match($routePath);
        } catch (ResourceNotFoundException $exception) {
            return null;
        }
    }

    protected function getConfirmationHandler(): ConfirmationHandler
    {
        if (!isset($this->confirmationHandler)) {
            $this->confirmationHandler = GeneralUtility::makeInstance(ConfirmationHandler::class);
        }
        return $this->confirmationHandler;
    }

    protected function getRouteManager(): RouteManager
    {
        if (!isset($this->routeManager)) {
            $this->routeManager = GeneralUtility::makeInstance(RouteManager::class);
        }
        return $this->routeManager;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        // @todo Make use of context
        $context = GeneralUtility::makeInstance(Context::class);
        return $GLOBALS['BE_USER'];
    }
}
