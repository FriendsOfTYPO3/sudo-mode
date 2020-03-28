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
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class VerificationException extends \RuntimeException
{
    /**
     * @var VerificationRequest
     */
    protected $verificationRequest;

    public function setVerificationRequest(VerificationRequest $verificationRequest): self
    {
        $this->verificationRequest = $verificationRequest;
        return $this;
    }

    /**
     * @return VerificationRequest
     */
    public function getVerificationRequest(): VerificationRequest
    {
        return $this->verificationRequest;
    }
}
