<?php
return [
    // action showing a dialog to request confirmation with user's password
    'session_verification_request' => [
        'path' => '/session/verification/request',
        'target' => \FriendsOfTYPO3\SudoMode\Backend\VerificationController::class . '::requestAction',
    ],
    // action verifying provided password and replaying previous command request
    'session_verification_verify' => [
        'path' => '/session/verification/verify',
        'target' => \FriendsOfTYPO3\SudoMode\Backend\VerificationController::class . '::verifyAction',
    ],
];
