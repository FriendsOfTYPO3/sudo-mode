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

class ConfirmationBundle implements \JsonSerializable
{
    /**
     * @var ConfirmationRequest
     */
    protected $confirmationRequest;

    /**
     * @var ServerRequestInstruction|null
     */
    protected $requestInstruction;

    /**
     * @var RequestMetaData|null
     */
    protected $requestMetaData;

    public function __construct(
        ConfirmationRequest $confirmationRequest,
        ServerRequestInstruction $requestInstruction = null,
        RequestMetaData $requestMetaData = null
    )
    {
        $this->confirmationRequest = $confirmationRequest;
        $this->requestInstruction = $requestInstruction;
        $this->requestMetaData = $requestMetaData;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function withRequestInstruction(ServerRequestInstruction $requestInstruction): self
    {
        if ($this->requestInstruction === $requestInstruction) {
            return $this;
        }
        $target = clone $this;
        $target->requestInstruction = $requestInstruction;
        return $target;
    }

    public function withRequestMetaData(RequestMetaData $requestMetaData): self
    {
        if ($this->requestMetaData === $requestMetaData) {
            return $this;
        }
        $target = clone $this;
        $target->requestMetaData = $requestMetaData;
        return $target;
    }

    public function getIdentifier(): string
    {
        return $this->confirmationRequest->getIdentifier();
    }

    /**
     * @return ConfirmationRequest
     */
    public function getConfirmationRequest(): ConfirmationRequest
    {
        return $this->confirmationRequest;
    }

    /**
     * @return ServerRequestInstruction|null
     */
    public function getRequestInstruction(): ?ServerRequestInstruction
    {
        return $this->requestInstruction;
    }

    /**
     * @return RequestMetaData|null
     */
    public function getRequestMetaData(): ?RequestMetaData
    {
        return $this->requestMetaData;
    }
}
