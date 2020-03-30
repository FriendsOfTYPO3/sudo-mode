<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Configuration;

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

/**
 * Model to represent behavior configuration.
 *
 * @todo Probably should have different name/namespace
 */
class Behavior
{
    protected const DEFAULT_TABLE_NAMES = ['be_users', 'be_groups'];
    protected const DEFAULT_EXPIRATION = 300;
    protected const MIN_EXPIRATION = 60;
    protected const MAX_EXPIRATION = 3600;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string[]
     */
    protected $tableNames;

    protected $expiration;

    public function __construct(array $configuration = null)
    {
        $this->configuration = $configuration ?? $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sudo_mode'] ?? [];

        $tableNames = explode(',', $this->configuration['tableNames'] ?? '');
        $tableNames = array_map('trim', array_filter($tableNames));
        $expiration = (int)($this->configuration['expiration'] ?? self::DEFAULT_EXPIRATION);
        $expiration = max(self::MIN_EXPIRATION, $expiration);
        $expiration = min(self::MAX_EXPIRATION, $expiration);

        // default table names are always defined and cannot be disabled
        $this->tableNames = array_merge($tableNames, self::DEFAULT_TABLE_NAMES);
        $this->expiration = $expiration;
    }

    public function getTableNames(): array
    {
        return $this->tableNames;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }
}
