<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\SudoMode;

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

use FriendsOfTYPO3\SudoMode\Backend\VerificationRequest;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

trait LoggerAccessorTrait
{
    protected function createLoggerContext(object ...$items): array
    {
        $context = [];
        foreach ($items as $item) {
            if ($item instanceof VerificationRequest) {
                $context['id'] = $item->getIdentifier();
                $context['type'] = $item->getType();
                $context['subjects'] = $item->getSubjects();
            } elseif ($item instanceof Route) {
                $context['route'] = $item->getPath();
            } elseif ($item instanceof AbstractUserAuthentication) {
                $context['user'] = $item->user['uid'] ?? null;
            }
        }
        return $context;
    }

}
