# TYPO3 sudo mode

Experimental implementation of sudo mode in TYPO3

## Installation

* apply patch https://review.typo3.org/c/Packages/TYPO3.CMS/+/63763 (TYPO3 v10 currently only)
* install extension or composer package via `composer require friendsoftypo3/sudo-mode:dev-master`

## Logging

In order to log sudo mode events `LOG` section in `LocalConfiguration.php` has to be extended
like shown in the following example (writer configuration and file names can be adjusted of course):

```
'LOG' => [
    // ...
    'FriendsOfTYPO3' => [
        'SudoMode' => [
            'writerConfiguration' => [
                \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                        'logFile' => 'typo3temp/var/log/sudoMode.log'
                    ],
                ],
            ],
        ],
    ],
    // ...
],

```
