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

use FriendsOfTYPO3\SudoMode\LoggerAccessorTrait;
use FriendsOfTYPO3\SudoMode\Model\Behavior;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handling potential command requests, stores results and requests in user's session
 * database table. Previously confirmed commands are valid for a given expiration time.
 *
 * Original requests are stored in session data as well and thus can be used to replay
 * the previous request once confirmation is successful.
 */
class ConfirmationHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LoggerAccessorTrait;

    protected const SESSION_CONFIRMATION_SUBJECTS = 'SudoMode.subjects';
    protected const SESSION_CONFIRMATION_BUNDLE = 'SudoMode.bundle';

    /**
     * @var ConfirmationFactory
     */
    protected $factory;

    /**
     * @var Behavior
     */
    protected $behavior;

    /**
     * @var int
     */
    protected $currentTimestamp;

    public function __construct(ConfirmationFactory $factory = null, Behavior $behavior = null)
    {
        $this->factory = $factory ?? GeneralUtility::makeInstance(ConfirmationFactory::class);
        $this->behavior = $behavior ?? GeneralUtility::makeInstance(Behavior::class);
        $this->currentTimestamp = (int)($GLOBALS['EXEC_TIME'] ?? time());
    }

    public function assertSubjects(ConfirmationBundle $bundle, AbstractUserAuthentication $user): void
    {
        $this->purgeSessionData($user);
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_SUBJECTS) ?? [];
        $request = $bundle->getConfirmationRequest();

        $subjects = $sessionData[$request->getType()] ?? [];
        // @todo shall existing grants be extended?
        $missingVerifications = array_filter(
            $request->getSubjects(),
            function (string $subject) use ($subjects) {
                return !isset($subjects[$subject])
                    || $subjects[$subject] < $this->currentTimestamp;
            }
        );

        if (!empty($missingVerifications)) {
            throw (new ConfirmationException('Session Verification required', 1584446638))
                ->setConfirmationBundle($bundle);
        }
    }

    public function grantSubjects(ConfirmationBundle $bundle, AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_SUBJECTS) ?? [];
        $sessionData = $this->purgeSubjects($user, $sessionData);

        $request = $bundle->getConfirmationRequest();
        foreach ($request->getSubjects() as $subject) {
            $sessionData[$request->getType()][$subject] = $this->currentTimestamp + $this->behavior->getExpiration();
        }
        $user->setAndSaveSessionData(self::SESSION_CONFIRMATION_SUBJECTS, $sessionData);
    }

    public function isExpired(ConfirmationBundle $bundle): bool
    {
        return $bundle->getConfirmationRequest()->getExpirationTimestamp() < $this->currentTimestamp;
    }

    public function fetchConfirmationBundle(string $identifier, AbstractUserAuthentication $user): ?ConfirmationBundle
    {
        $this->purgeSessionData($user);
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_BUNDLE) ?? [];

        $data = json_decode($sessionData[$identifier]['bundle'] ?? '', true);
        $bundle = is_array($data) ? $this->factory->createBundleFromArray($data) : null;
        if ($bundle === null || $bundle->getConfirmationRequest()->getExpirationTimestamp() < $this->currentTimestamp) {
            return null;
        }
        return $bundle;
    }

    public function commitConfirmationBundle(ConfirmationBundle $bundle, AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_BUNDLE) ?? [];
        $sessionData = $this->purgeBundles($user, $sessionData);

        $identifier = $bundle->getConfirmationRequest()->getIdentifier();
        $sessionData[$identifier] = [
            'bundle' => json_encode($bundle),
            'expiration' => $bundle->getConfirmationRequest()->getExpirationTimestamp(),
        ];
        $user->setAndSaveSessionData(self::SESSION_CONFIRMATION_BUNDLE, $sessionData);
    }

    public function removeConfirmationBundle(ConfirmationBundle $bundle, AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_BUNDLE) ?? [];
        $sessionData = $this->purgeBundles($user, $sessionData);

        $identifier = $bundle->getConfirmationRequest()->getIdentifier();
        if (isset($sessionData[$identifier])) {
            unset($sessionData[$identifier]);
            $user->setAndSaveSessionData(self::SESSION_CONFIRMATION_BUNDLE, $sessionData);
        }
    }

    protected function purgeSessionData(AbstractUserAuthentication $user): void
    {
        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_SUBJECTS) ?? [];
        $sessionData = $this->purgeSubjects($user, $sessionData, $purgedSubjects);
        if ($purgedSubjects) {
            $user->setAndSaveSessionData(self::SESSION_CONFIRMATION_SUBJECTS, $sessionData);
        }

        $sessionData = $user->getSessionData(self::SESSION_CONFIRMATION_BUNDLE) ?? [];
        $sessionData = $this->purgeBundles($user, $sessionData, $purgedInstructions);
        if ($purgedInstructions) {
            $user->setAndSaveSessionData(self::SESSION_CONFIRMATION_BUNDLE, $sessionData);
        }
    }

    protected function purgeSubjects(AbstractUserAuthentication $user, array $sessionData, bool &$purged = null): array
    {
        $purged = false;
        foreach (array_keys($sessionData) as $type) {
            if (empty($sessionData[$type])) {
                continue;
            }
            foreach ($sessionData[$type] as $key => $item) {
                if (is_int($item) && $item >= $this->currentTimestamp) {
                    continue;
                }
                unset($sessionData[$type][$key]);
                $purged = true;
            }
        }
        return $sessionData;
    }

    protected function purgeBundles(AbstractUserAuthentication $user, array $sessionData, bool &$purged = null): array
    {
        $purged = false;
        foreach ($sessionData as $key => $item) {
            $expirationTimestamp = $item['expiration'] ?? 0;
            if ($expirationTimestamp >= $this->currentTimestamp) {
                continue;
            }
            $data = json_decode($item['bundle'] ?? '', true);
            $bundle = $this->factory->createBundleFromArray($data);
            $loggerContext = $this->createLoggerContext($bundle, $user);
            $this->logger->notice('Password verification purged', $loggerContext);
            unset($sessionData[$key]);
            $purged = true;
        }
        return $sessionData;
    }
}
