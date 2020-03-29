<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][\FriendsOfTYPO3\SudoMode\Hook\DataManipulationHook::class] = \FriendsOfTYPO3\SudoMode\Hook\DataManipulationHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][\FriendsOfTYPO3\SudoMode\Hook\DataManipulationHook::class] = \FriendsOfTYPO3\SudoMode\Hook\DataManipulationHook::class;
