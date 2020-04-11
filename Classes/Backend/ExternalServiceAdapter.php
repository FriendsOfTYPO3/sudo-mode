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

use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Aims to adapt external services or extensions to be working with `ext:sudo_mode`.
 */
class ExternalServiceAdapter
{
    public function emitLoginProviderSignal(PageRenderer $pageRenderer = null): void
    {
        $this->getSignalSlotDispatcher()->dispatch(
            UsernamePasswordLoginProvider::class,
            UsernamePasswordLoginProvider::SIGNAL_getPageRenderer,
            [$pageRenderer ?? $this->getPageRenderer()]
        );
    }

    public function getSignalSlotDispatcher(): Dispatcher
    {
        return $this->getObjectManager()->get(Dispatcher::class);
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    public function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
