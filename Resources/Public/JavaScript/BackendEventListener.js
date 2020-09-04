/**
 * @module TYPO3/CMS/SudoMode/BackendEventListener
 */
define(
    ['require', 'exports', 'TYPO3/CMS/SudoMode/EventHandler'],
    function (require, exports, EventHandler) {
        'use strict';

        function handle(evt) {
            let action = resolveAction(evt);
            new EventHandler(action, evt.detail.payload).handle();
        }

        function resolveAction(evt) {
            switch (evt.type) {
                case 'typo3:ajax-data-handler:toggle-process-failed':
                    return 'toggle';
                case 'typo3:ajax-data-handler:delete-process-failed':
                    return 'delete';
                default:
                    throw new RangeError('Unexpected event type "' + evt.type + '"');
            }
        }

        document.addEventListener('typo3:ajax-data-handler:toggle-process-failed', handle);
        document.addEventListener('typo3:ajax-data-handler:delete-process-failed', handle);
    });
