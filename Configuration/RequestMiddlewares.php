<?php
/**
 * Definitions for middle-wares provided by EXT:sudo_mode
 */
return [
    'backend' => [
        'friendsoftypo3/sudo-mode/request-handler-guard' => [
            'after' => [
                'typo3/cms-backend/backend-routing',
                'typo3/cms-backend/response-headers',
                'typo3/cms-backend/site-resolver',
            ],
            'target' => \FriendsOfTYPO3\SudoMode\Middleware\RequestHandlerGuard::class,
        ],
    ],
];
