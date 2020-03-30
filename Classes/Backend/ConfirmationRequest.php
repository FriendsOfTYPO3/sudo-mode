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

/**
 * Model for formalize a request/command to be confirmed using user's password.
 * This model focuses on the subject that needs to be confirmed and handled.
 */
class ConfirmationRequest implements \JsonSerializable
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
    protected $expirationTimestamp;

    public function __construct(string $type, array $subjects, string $identifier, int $expirationTimestamp)
    {
        $this->type = $type;
        $this->subjects = array_values($subjects);
        $this->identifier = $identifier;
        $this->expirationTimestamp = $expirationTimestamp;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
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
    public function getExpirationTimestamp(): int
    {
        return $this->expirationTimestamp;
    }
}
