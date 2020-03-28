<?php
return [
    'session_verification_request' => [
        'path' => '/session/verification/request',
        'target' => \FriendsOfTYPO3\SudoMode\Backend\VerificationController::class . '::requestAction',
    ],
    'session_verification_verify' => [
        'path' => '/session/verification/verify',
        'target' => \FriendsOfTYPO3\SudoMode\Backend\VerificationController::class . '::verifyAction',
    ],
];
