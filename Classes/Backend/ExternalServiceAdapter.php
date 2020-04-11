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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\RsaEncryptionEncoder;

/**
 * Aims to adapt external services or extensions to be working with `ext:sudo_mode`.
 */
class ExternalServiceAdapter
{
    public function applyRsaAuthModules(): void
    {
        if (!ExtensionManagementUtility::isLoaded('rsaauth')) {
            return;
        }
        $rsaEncryptionEncoder = GeneralUtility::makeInstance(RsaEncryptionEncoder::class);
        $rsaEncryptionEncoder->enableRsaEncryption(true);
    }
}
