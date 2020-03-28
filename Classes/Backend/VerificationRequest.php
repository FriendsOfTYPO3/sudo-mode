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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class VerificationRequest implements \JsonSerializable
{
    public const TYPE_TABLE_NAME = 'tableName';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string[]
     */
    protected $subjects;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var int
     */
    protected $currentTime;

    public static function fromArray(array $data): self
    {
        $hmac = $data['hmac'] ?? null;
        $data = array_intersect_key($data, get_class_vars(static::class));
        if (empty($hmac) || GeneralUtility::hmac(json_encode($data)) !== $hmac) {
            throw new \RuntimeException('Invalid HMAC', 1584515264);
        }
        return new static(
            $data['type'],
            $data['subjects'],
            $data['identifier'],
            $data['currentTime']
        );
    }

    public function __construct(string $type, array $subjects, string $identifier = null, int $currentTime = null)
    {
        $this->type = $type;
        $this->subjects = $subjects;
        $this->identifier = $identifier ?? (new Random)->generateRandomHexString(20);
        $this->currentTime = $currentTime ?? $GLOBALS['EXEC_TIME'] ?? time();
    }

    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);
        $data['hmac'] = GeneralUtility::hmac(json_encode($data));
        return $data;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string[]
     */
    public function getSubjects(): array
    {
        return $this->subjects;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return int
     */
    public function getCurrentTime(): int
    {
        return $this->currentTime;
    }
}
