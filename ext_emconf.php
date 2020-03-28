<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 sudo mode',
    'description' => 'TYPO3 sudo mode',
    'category' => 'misc',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'author' => 'Oliver Hader',
    'author_email' => 'oliver.hader@typo3.org',
    'author_company' => '',
    'version' => '0.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99'
        ],
        'conflicts' => [],
    ],
];
