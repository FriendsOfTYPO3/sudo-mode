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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

/**
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class VerificationHandler
{
    protected const SESSION_VERIFICATION_EXPIRATION = 300;
    protected const SESSION_VERIFICATION_SUBJECTS = 'verificationSubjects';
    protected const SESSION_VERIFICATION_INSTRUCTIONS = 'verificationRequests';

    /**
     * @var int
     */
    protected $currentTime;

    public function __construct()
    {
        $this->currentTime = $GLOBALS['EXEC_TIME'] ?? time();
    }

    public function assertSubjects(VerificationRequest $verificationRequest, AbstractUserAuthentication $user): void
    {
        $this->purgeSessionData($user);
        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_SUBJECTS) ?? [];

        $verificationSubjects = $sessionData[$verificationRequest->getType()] ?? [];
        // @todo shall existing grants be extended?
        $missingVerifications = array_filter(
            $verificationRequest->getSubjects(),
            function (string $subject) use ($verificationSubjects) {
                return !isset($verificationSubjects[$subject])
                    || $verificationSubjects[$subject] < $this->currentTime;
            }
        );

        if (!empty($missingVerifications)) {
            throw (new VerificationException('Session Verification required', 1584446638))
                ->setVerificationRequest($verificationRequest);
        }
    }

    public function grantSubject(VerificationRequest $verificationRequest, AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_SUBJECTS) ?? [];
        $sessionData = $this->purgeSubjects($sessionData);
        foreach ($verificationRequest->getSubjects() as $subject) {
            $sessionData[$verificationRequest->getType()][$subject] = $this->currentTime + self::SESSION_VERIFICATION_EXPIRATION;
        }
        $user->setAndSaveSessionData(self::SESSION_VERIFICATION_SUBJECTS, $sessionData);
    }

    public function fetchRequestInstruction(VerificationRequest $verificationRequest, AbstractUserAuthentication $user): ?ServerRequestInstruction
    {
        $this->purgeSessionData($user);
        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_INSTRUCTIONS) ?? [];

        $data = json_decode($sessionData[$verificationRequest->getIdentifier()]['instruction'] ?? '', true);
        return is_array($data) ? ServerRequestInstruction::fromJsonArray($data) : null;
    }

    public function commitRequestInstruction(VerificationRequest $verificationRequest, AbstractUserAuthentication $user, ServerRequestInterface $request): void
    {
        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_INSTRUCTIONS) ?? [];
        $sessionData = $this->purgeInstructions($sessionData);
        $sessionData[$verificationRequest->getIdentifier()] = [
            'expirationTime' => $this->currentTime + self::SESSION_VERIFICATION_EXPIRATION,
            'instruction' => json_encode(ServerRequestInstruction::fromServerRequest($request)),
        ];
        $user->setAndSaveSessionData(self::SESSION_VERIFICATION_INSTRUCTIONS, $sessionData);
    }

    protected function purgeSessionData(AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_SUBJECTS) ?? [];
        $sessionData = $this->purgeSubjects($sessionData, $purgedSubjects);
        if ($purgedSubjects) {
            $user->setAndSaveSessionData(self::SESSION_VERIFICATION_SUBJECTS, $sessionData);
        }

        $sessionData = $user->getSessionData(self::SESSION_VERIFICATION_INSTRUCTIONS) ?? [];
        $sessionData = $this->purgeInstructions($sessionData, $purgedInstructions);
        if ($purgedInstructions) {
            $user->setAndSaveSessionData(self::SESSION_VERIFICATION_INSTRUCTIONS, $sessionData);
        }
    }

    protected function purgeSubjects(array $sessionData, bool &$purged = null): array
    {
        $purged = false;
        foreach (array_keys($sessionData) as $type) {
            if (empty($sessionData[$type])) {
                continue;
            }
            foreach ($sessionData[$type] as $key => $item) {
                if (!is_int($item) || $item < $this->currentTime) {
                    unset($sessionData[$type][$key]);
                    $purged = true;
                }
            }
        }
        return $sessionData;
    }

    protected function purgeInstructions(array $sessionData, bool &$purged = null): array
    {
        $purged = false;
        foreach ($sessionData as $key => $item) {
            if ($item['expirationTime'] < $this->currentTime) {
                unset($sessionData[$key]);
                $purged = true;
            }
        }
        return $sessionData;
    }
}
