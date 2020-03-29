<?php
/**
 * Definitions for middle-wares provided by EXT:sudo_mode
 */
return [
    // middleware trying to put sudo mode handling to the end of the stack
    // (having all/most of the context-specific settings initialized and applied by other middlewares)
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
