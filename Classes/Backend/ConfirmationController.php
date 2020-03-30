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

use FriendsOfTYPO3\SudoMode\LoggerAccessorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ServerRequestInstructionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller to show dialog to confirm previous command with user's password.
 * In case confirmation is successful, previous request will be replayed using
 * a `ServerRequestInstructionException`.
 */
class ConfirmationController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LoggerAccessorTrait;

    protected const FLAG_INVALID_PASSWORD = 1;

    /**
     * @var ConfirmationHandler
     */
    protected $handler;

    /**
     * @var BackendUserAuthentication
     */
    protected $user;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    public function __construct(ConfirmationHandler $handler = null, BackendUserAuthentication $user = null, UriBuilder $uriBuilder = null)
    {
        $this->handler = $handler ?? GeneralUtility::makeInstance(ConfirmationHandler::class);
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->user = $user ?? $GLOBALS['BE_USER'];
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ServerRequestInstructionException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->resolveUriParameters($request);
        $actionName = (string)($parameters['action'] ?? '');
        $bundleIdentifier = (string)($parameters['bundle'] ?? '');
        $bundle = $this->handler->fetchConfirmationBundle($bundleIdentifier, $this->user);

        if ($bundle === null) {
            return $this->errorAction($request);
        }

        if ($actionName === 'request') {
            return $this->requestAction($request, $bundle);
        } elseif ($actionName === 'verify') {
            return $this->verifyAction($request, $bundle);
        } elseif ($actionName === 'cancel') {
            return $this->cancelAction($request, $bundle);
        }
        throw new \LogicException('Invalid action name', 1585514635);
    }

    protected function requestAction(ServerRequestInterface $request, ConfirmationBundle $bundle): ResponseInterface
    {
        $flags = (int)($request->getQueryParams()['flags'] ?? 0);

        if (!$this->isJsonRequest($request)) {
            $view = $this->createView('Request');
            $view->assignMultiple([
                'bundle' => $bundle,
                'verifyUri' => (string)$this->buildActionUriFromBundle('verify', $bundle),
                'flagInvalidPassword' => $flags & self::FLAG_INVALID_PASSWORD,
            ]);
            if (!empty($bundle->getRequestMetaData()->getReturnUrl())) {
                $view->assign('cancelUri', (string)$this->buildActionUriFromBundle('cancel', $bundle));
            }
            return new HtmlResponse($view->render());
        }
        // @todo Add JSON handling
        return new JsonResponse([], 500);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ConfirmationBundle $bundle
     * @return ResponseInterface
     * @throws ServerRequestInstructionException
     */
    protected function verifyAction(ServerRequestInterface $request, ConfirmationBundle $bundle): ResponseInterface
    {
        $confirmationPassword = (string)($request->getParsedBody()['confirmationPassword'] ?? '');
        $loggerContext = $this->createLoggerContext($bundle, $this->user);

        if (!$this->isJsonRequest($request)) {
            if ($this->isValidPassword($confirmationPassword)) {
                $this->handler->grantSubjects($bundle, $this->user);
                $this->logger->info('Password verification succeeded', $loggerContext);
                throw new ServerRequestInstructionException($bundle->getRequestInstruction());
            }

            $this->logger->warning('Password verification failed', $loggerContext);
            $uri = $this->buildActionUriFromBundle('request', $bundle, self::FLAG_INVALID_PASSWORD);
            return new RedirectResponse($uri, 401);
        }
        // @todo Add JSON handling
        return new JsonResponse([], 500);
    }

    protected function cancelAction(ServerRequestInterface $request, ConfirmationBundle $bundle): ResponseInterface
    {
        $loggerContext = $this->createLoggerContext($bundle, $this->user);

        if (!$this->isJsonRequest($request)) {
            $this->handler->removeConfirmationBundle($bundle, $this->user);
            $this->logger->notice('Password verification cancelled', $loggerContext);
            return new RedirectResponse($bundle->getRequestMetaData()->getReturnUrl(), 401);
        }
        // @todo Add JSON handling
        return new JsonResponse([], 500);
    }

    protected function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createView('Error');
        $view->assign('returnUrl', $request->getQueryParams()['returnUrl'] ?? '');
        return new HtmlResponse($view->render());
    }

    public function buildActionUriFromBundle(string $actionName, ConfirmationBundle $bundle, int $flags = null): UriInterface
    {
        return $this->buildActionUri(
            $actionName,
            $bundle->getRequestMetaData()->getReturnUrl(),
            $bundle->getIdentifier(),
            $flags
        );
    }

    protected function buildActionUri(string $actionName, string $returnUrl, string $bundleIdentifier, int $flags = null): UriInterface
    {
        $parameters = [
            'action' => $actionName,
            'returnUrl' => $returnUrl,
            'bundle' => $bundleIdentifier,
            'flags' => $flags,
        ];
        $parameters['hmac'] = $this->signParameters($parameters);
        return $this->uriBuilder->buildUriFromRoute('sudo-mode.confirmation', $parameters);
    }

    protected function resolveUriParameters(ServerRequestInterface $request): array
    {
        $parameters = $this->filterParameters($request->getQueryParams());
        $expectedHmac = $this->signParameters($parameters);
        $givenHmac = $parameters['hmac'] ?? '';
        if (!hash_equals($expectedHmac, $givenHmac)) {
            throw new \RuntimeException('Invalid parameters signature', 1585563355);
        }
        return $parameters;
    }

    protected function filterParameters(array $parameters): array
    {
        return array_intersect_key($parameters, array_flip(['action', 'returnUrl', 'bundle', 'flags', 'hmac']));
    }

    protected function signParameters(array $parameters): string
    {
        unset($parameters['hmac']);
        ksort($parameters);
        $parameters = array_filter($parameters);
        $parameters = array_map('strval', $parameters);
        return GeneralUtility::hmac(json_encode($parameters));
    }

    protected function createView(string $templateName): ViewInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templatePath = GeneralUtility::getFileAbsFileName(
            sprintf('EXT:sudo_mode/Resources/Private/Templates/Backend/%s.html', $templateName)
        );
        $view->setTemplatePathAndFilename($templatePath);
        return $view;
    }

    /**
     * Copied from AbstractUserAuthentication to run through authentication services.
     * @todo Make use of extracted functionality once it's available in TYPO3 core
     *
     * @param string $password
     * @return bool
     */
    protected function isValidPassword(string $password): bool
    {
        $subType = 'authUserBE';
        $loginData = [
            'status' => '',
            'uname'  => $this->user->user['username'],
            'uident' => $password,
        ];
        $loginData = $this->user->processLoginData($loginData);
        $authInfo = $this->user->getAuthInfoArray();

        $authenticated = false;
        foreach ($this->getAuthServices($subType, $loginData, $authInfo) as $serviceObj) {
            if (($ret = $serviceObj->authUser($this->user->user)) > 0) {
                if ((int)$ret >= 200) {
                    $authenticated = true;
                    break;
                }
                if ((int)$ret >= 100) {
                } else {
                    $authenticated = true;
                }
            } else {
                $authenticated = false;
                break;
            }
        }
        return $authenticated;
    }

    /**
     * Initializes authentication services to be used in a foreach loop
     *
     * @param string $subType e.g. getUserFE
     * @param array $loginData
     * @param array $authInfo
     * @return \Traversable|AuthenticationService[]
     */
    protected function getAuthServices(string $subType, array $loginData, array $authInfo): \Traversable
    {
        $serviceChain = [];
        $user = clone $this->user;
        while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
            $serviceChain[] = $serviceObj->getServiceKey();
            $serviceObj->initAuth($subType, $loginData, $authInfo, $user);
            yield $serviceObj;
        }
        if (!empty($serviceChain)) {
            $this->logger->debug($subType . ' auth services called: ' . implode(', ', $serviceChain));
        }
    }

    protected function isJsonRequest(ServerRequestInterface $request): bool
    {
        return strpos($request->getHeaderLine('content-type'), 'application/json') === 0;
    }
}
