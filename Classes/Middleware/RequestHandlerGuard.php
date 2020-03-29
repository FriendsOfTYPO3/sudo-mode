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

use FriendsOfTYPO3\SudoMode\Backend\VerificationController;
use FriendsOfTYPO3\SudoMode\Backend\VerificationException;
use FriendsOfTYPO3\SudoMode\Backend\VerificationHandler;
use FriendsOfTYPO3\SudoMode\Backend\VerificationRequest;
use FriendsOfTYPO3\SudoMode\Model\Behavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal Note that this is not public API yet.
 */
class RequestHandlerGuard implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routePath = $this->resolveRoutePath($request);
        $shallGuard = in_array($routePath, (new Behavior())->getRoutePaths(), true);

        try {
            return $handler->handle($request);
        } catch (VerificationException $exception) {
            if (!$shallGuard) {
                throw $exception;
            }
            return $this->handle($request, $exception->getVerificationRequest());
        }
    }

    protected function handle(ServerRequestInterface $request, VerificationRequest $verificationRequest)
    {
        GeneralUtility::makeInstance(VerificationHandler::class)
            ->commitRequestInstruction($verificationRequest, $this->getBackendUser(), $request);
        $uri = GeneralUtility::makeInstance(VerificationController::class)
            ->buildUriForRequestAction($verificationRequest, $this->resolveReturnUrl($request));
        return GeneralUtility::makeInstance(RedirectResponse::class, $uri, 401);
    }

    protected function resolveReturnUrl(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();
        return GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? null);
    }

    protected function resolveRoutePath(ServerRequestInterface $request): ?string
    {
        $route = $request->getAttribute('route', null);
        if ($route !== null) {
            return $route->getPath();
        }
        try {
            $router = GeneralUtility::makeInstance(Router::class);
            return $router->matchRequest($request)->getPath();
        } catch (ResourceNotFoundException $exception) {
            return null;
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return $GLOBALS['BE_USER'];
    }
}
