<?php
/**
 * Definitions for middle-wares provided by EXT:sudo_mode
 */

use FriendsOfTYPO3\SudoMode\Middleware\RequestHandlerGuard;

return [
    // middleware trying to put sudo mode handling to the end of the stack
    // (having all/most of the context-specific settings initialized and applied by other middlewares)
    'backend' => [
        'friendsoftypo3/sudo-mode/request-handler-guard' => [
            'before' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-backend/locked-backend',
            ],
            'target' => RequestHandlerGuard::class,
        ],
    ],
];
