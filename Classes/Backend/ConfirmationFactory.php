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

use FriendsOfTYPO3\SudoMode\Http\ServerRequestInstruction;
use FriendsOfTYPO3\SudoMode\Model\Behavior;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfirmationFactory
{
    /**
     * @var Random
     */
    protected $random;

    /**
     * @var Behavior
     */
    protected $behavior;

    /**
     * @var int
     */
    protected $currentTimestamp;

    public function __construct(Random $random = null, Behavior $behavior = null)
    {
        $this->random = $random ?? GeneralUtility::makeInstance(Random::class);
        $this->behavior = $behavior ?? GeneralUtility::makeInstance(Behavior::class);
        $this->currentTimestamp = (int)($GLOBALS['EXEC_TIME'] ?? time());
    }

    public function setCurrentTimestamp(int $currentTimestamp): void
    {
        $this->currentTimestamp = $currentTimestamp;
    }

    public function createBundleForTableNameSubjects(array $subjects): ConfirmationBundle
    {
        $confirmationRequest = GeneralUtility::makeInstance(
            ConfirmationRequest::class,
            ConfirmationRequest::TYPE_TABLE_NAME,
            $subjects,
            $this->random->generateRandomHexString(20),
            $this->currentTimestamp + $this->behavior->getExpiration()
        );
        return GeneralUtility::makeInstance(
            ConfirmationBundle::class,
            $confirmationRequest
        );
    }

    public function createBundleFromArray(array $data): ConfirmationBundle
    {
        $confirmationRequest = $this->createRequestFromArray($data['confirmationRequest'] ?? []);
        $bundle = GeneralUtility::makeInstance(
            ConfirmationBundle::class,
            $confirmationRequest
        );
        if (isset($data['requestMetaData'])) {
            $bundle = $bundle->withRequestMetaData(
                $this->createRequestMetaDataFromArray($data['requestMetaData'])
            );
        }
        if (isset($data['requestInstruction'])) {
            $bundle = $bundle->withRequestInstruction(
                ServerRequestInstruction::fromJsonArray(
                    $data['requestInstruction']
                )
            );
        }
        return $bundle;
    }

    public function createRequestFromArray(array $data): ConfirmationRequest
    {
        return GeneralUtility::makeInstance(
            ConfirmationRequest::class,
            $data['type'] ?? null,
            $data['subjects'] ?? null,
            $data['identifier'] ?? null,
            $data['expirationTimestamp'] ?? null
        );
    }

    public function createRequestMetaDataFromArray(array $data): RequestMetaData
    {
        $target = GeneralUtility::makeInstance(RequestMetaData::class);
        if (isset($data['returnUrl'])) {
            $target = $target->withReturnUrl($data['returnUrl']);
        }
        return $target;
    }
}
