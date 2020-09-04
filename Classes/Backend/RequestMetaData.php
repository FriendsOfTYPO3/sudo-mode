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

class RequestMetaData implements \JsonSerializable
{
    /**
     * @var string|null
     */
    protected $scope;

    /**
     * @var string|null
     */
    protected $returnUrl;

    /**
     * @var string|null
     */
    protected $eventName;

    /**
     * @var array|null
     */
    protected $jsonData;

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function withScope(string $scope): self
    {
        $target = clone $this;
        $target->scope = $scope;
        return $target;
    }

    public function withReturnUrl(string $returnUrl): self
    {
        $target = clone $this;
        $target->returnUrl = $returnUrl;
        return $target;
    }

    public function withEventName(string $eventName): self
    {
        $target = clone $this;
        $target->eventName = $eventName;
        return $target;
    }

    public function withJsonData(array $jsonData): self
    {
        $target = clone $this;
        $target->jsonData = $jsonData;
        return $target;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    /**
     * @return string|null
     */
    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    /**
     * @return array|null
     */
    public function getJsonData(): ?array
    {
        return $this->jsonData;
    }
}
