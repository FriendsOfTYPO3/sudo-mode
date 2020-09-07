<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Hook;

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

use FriendsOfTYPO3\SudoMode\Backend\ExternalServiceAdapter;
use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook triggers loading resources (JavaScript, Stylesheets) in backend context.
 */
class BackendResourceHook
{
    public function applyResources(array $parameters, BackendController $backendController)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/SudoMode/BackendEventListener');
        // load RSA auth JavaScript modules (if applicable)
        GeneralUtility::makeInstance(ExternalServiceAdapter::class)->applyRsaAuthModules();
    }
}
