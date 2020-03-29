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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ServerRequestInstructionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller to show dialog to confirm previous command with user's password.
 * In case confirmation is successful, previous request will be replayed using
 * a `ServerRequestInstructionException`.
 */
class VerificationController implements \Psr\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const FLAG_INVALID_PASSWORD = 1;

    /**
     * @var BackendUserAuthentication
     */
    protected $user;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    public function __construct(UriBuilder $uriBuilder = null, BackendUserAuthentication $user = null)
    {
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->user = $user ?? $GLOBALS['BE_USER'];
    }

    public function buildUriForRequestAction(VerificationRequest $verificationRequest, string $returnUrl, int $flags = null): UriInterface
    {
        return $this->uriBuilder->buildUriFromRoute('session_verification_request', [
            'verificationRequest' => json_encode($verificationRequest),
            'returnUrl' => $returnUrl,
            'flags' => $flags,
        ]);
    }

    public function requestAction(ServerRequestInterface $request): ResponseInterface
    {
        $verificationRequest = (string)($request->getQueryParams()['verificationRequest'] ?? '');
        $returnUrl = (string)($request->getQueryParams()['returnUrl'] ?? '');
        $flags = (int)($request->getQueryParams()['flags'] ?? 0);

        if (!$this->isJsonRequest($request)) {
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $templatePath = GeneralUtility::getFileAbsFileName('EXT:sudo_mode/Resources/Private/Templates/Backend/VerificationRequest.html');
            $view->setTemplatePathAndFilename($templatePath);
            $view->assignMultiple([
                'flagInvalidPassword' => $flags & self::FLAG_INVALID_PASSWORD,
                'verificationRequest' => $verificationRequest,
                'returnUrl' => $returnUrl,
            ]);
            return new HtmlResponse($view->render());
        }
        // @todo Add JSON handling
        return new JsonResponse([], 500);
    }

    public function verifyAction(ServerRequestInterface $request): ResponseInterface
    {
        $verificationRequest = (string)($request->getQueryParams()['verificationRequest'] ?? '');
        $verificationRequest = VerificationRequest::fromArray(json_decode($verificationRequest, true));
        $verificationPassword = (string)($request->getParsedBody()['verificationPassword'] ?? '');
        $returnUrl = (string)($request->getQueryParams()['returnUrl'] ?? '');

        if (!$this->isJsonRequest($request)) {
            if ($this->isValidPassword($verificationPassword)) {
                $verificationHandler = GeneralUtility::makeInstance(VerificationHandler::class);
                $verificationHandler->grantSubject($verificationRequest, $this->user);
                $requestInstruction = $verificationHandler->fetchRequestInstruction($verificationRequest, $this->user);
                throw new ServerRequestInstructionException($requestInstruction);
            }
            $uri = $this->buildUriForRequestAction($verificationRequest, $returnUrl, self::FLAG_INVALID_PASSWORD);
            return new RedirectResponse($uri, 401);
        }
        // @todo Add JSON handling
        return new JsonResponse([], 500);
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
