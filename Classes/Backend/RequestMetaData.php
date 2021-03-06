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
    protected $returnUrl;

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function withReturnUrl(string $returnUrl): self
    {
        $target = clone $this;
        $target->returnUrl = $returnUrl;
        return $target;
    }

    /**
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }
}
