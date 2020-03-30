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

use FriendsOfTYPO3\SudoMode\Backend\ConfirmationFactory;
use FriendsOfTYPO3\SudoMode\Backend\ConfirmationHandler;
use FriendsOfTYPO3\SudoMode\LoggerAccessorTrait;
use FriendsOfTYPO3\SudoMode\Model\Behavior;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `DataHandler` hook to guard commands that would be modifying data in database tables
 * that need confirmation before actually being performed.
 */
class DataManipulationHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LoggerAccessorTrait;

    protected const TABLE_NAMES = [
        'be_users',
        'be_groups',
    ];

    /**
     * @var ConfirmationFactory
     */
    protected $factory;

    /**
     * @var ConfirmationHandler
     */
    protected $handler;

    /**
     * @var string[]
     */
    protected $tableNames = [];

    public function __construct(ConfirmationFactory $factory = null, ConfirmationHandler $handler = null)
    {
        $this->factory = $factory ?? GeneralUtility::makeInstance(ConfirmationFactory::class);
        $this->handler = $handler ?? GeneralUtility::makeInstance(ConfirmationHandler::class);
        $this->tableNames = (new Behavior())->getTableNames() ?? self::TABLE_NAMES;
    }

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $tableNames = array_keys($dataHandler->datamap ?? []);
        $this->assertTableNamesPermission($tableNames, $dataHandler);
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler): void
    {
        $tableNames = array_keys($dataHandler->cmdmap ?? []);
        $this->assertTableNamesPermission($tableNames, $dataHandler);
    }

    protected function shallVerifyPermission(DataHandler $dataHandler): bool
    {
        // skip verification when importing or access checks shall be bypassed (e.g. for ReferenceIndex)
        return !$dataHandler->isImporting && !$dataHandler->bypassAccessCheckForRecords;
    }

    protected function assertTableNamesPermission(array $tableNames, DataHandler $dataHandler): void
    {
        $affectedTableNames = array_intersect($this->tableNames, $tableNames);
        if ($affectedTableNames === []) {
            return;
        }

        $confirmationBundle = $this->factory->createBundleForTableNameSubjects($affectedTableNames);
        if (!$this->shallVerifyPermission($dataHandler)) {
            $this->logger->notice('By-passed assertion', $this->createLoggerContext($confirmationBundle));
            return;
        }
        $this->handler->assertSubjects($confirmationBundle, $dataHandler->BE_USER);
    }
}
