/**
 * @module TYPO3/CMS/SudoMode/BackendEventListener
 */
define(
    ['require', 'exports', 'TYPO3/CMS/SudoMode/EventHandler'],
    function (require, exports, EventHandler) {
        'use strict';

        function handle(evt) {
            new EventHandler(evt.detail.payload).handle();
        }

        document.addEventListener('typo3:ajax-data-handler:process-failed', handle);
        document.addEventListener('typo3:ajax-data-handler:process-failed', handle);
    }
);
