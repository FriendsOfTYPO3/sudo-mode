/**
 * @module TYPO3/CMS/SudoMode/BackendEventListener
 */
define(
    [
        'jquery',
        'TYPO3/CMS/Backend/Modal',
        // opt(ional) modules using loader plugin
        'TYPO3/CMS/SudoMode/opt!TYPO3/CMS/Backend/BroadcastService',
        'TYPO3/CMS/SudoMode/opt!TYPO3/CMS/Backend/BroadcastMessage',
    ],
    function ($, Modal, broadcastService, BroadcastMessageModule) {
        'use strict';

        if (!broadcastService || !BroadcastMessageModule) {
            console.info('BroadcastService or BroadcastMessage not found, which is fine in TYPO3 v9');
        }

        function EventHandler(action, message) {
            this.action = action;
            this.message = message;
            this.response = message.response;
            this.processToken = message.processToken;
            this.modal = null;
        }

        EventHandler.prototype.handle = function() {
            if (!this.isRelevant()) {
                return;
            }
            this.requestAction();
        }

        EventHandler.prototype.showModal = function(instruction) {
            const that = this;
            let $content = $(instruction.content);
            let $form = $content.find('#' + instruction.formId)
                .on('submit', function(evt) {
                    evt.preventDefault();
                    that.verifyAction(instruction, $form, $invalid);
                });
            let $invalid = $content.find('#' + instruction.invalidId)
                .hide();

            this.modal = Modal.advanced({
                type: Modal.types.default,
                title: instruction.title,
                content: $content,
                severity: instruction.severity,
                buttons: [
                    {
                        btnClass: 'btn-default',
                        text: instruction.button.cancel,
                        trigger: function(evt) {
                            that.cancelAction(instruction);
                        }
                    },
                    {
                        btnClass: 'btn-warning',
                        text: instruction.button.confirm,
                        trigger: function(evt) {
                            $form.submit();
                        }
                    },
                ],
            });
        }

        EventHandler.prototype.isRelevant = function() {
            return this.response.headers
                && this.response.headers.get('x-typo3-emitevent') === 'sudo-mode:confirmation-request';
        }

        EventHandler.prototype.requestAction = function() {
            const that = this;
            $.ajax({
                method: 'GET',
                dataType: 'json',
                url: this.response.body.uri
            }).done(function(response) {
                that.showModal(response);
            });
        }

        EventHandler.prototype.verifyAction = function(instruction, $form, $invalid) {
            const that = this;
            let formData = new FormData($form.get(0));
            $invalid.hide();
            $.ajax({
                method: 'POST',
                url: instruction.uri.verify,
                data: formData,
                processData: false,
                contentType: false
            }).done(function(response, status, xhr) {
                that.modal.trigger('modal-dismiss');
                that.broadcast(that.action);
            }).fail(function(xhr, status, error) {
                $invalid.show();
            });
        }

        EventHandler.prototype.cancelAction = function(instruction) {
            const that = this;
            $.ajax({
                method: 'GET',
                url: instruction.uri.cancel
            }).done(function(response, status, xhr) {
                that.modal.trigger('modal-dismiss');
                that.broadcast('revert');
            }).fail(function(xhr, status, error) {
                console.warn('Cancel action failed: ' + error);
            })
        }

        EventHandler.prototype.broadcast = function(action) {
            const instruction = {
                action,
                processToken: this.processToken,
                elementIdentifier: this.message.elementIdentifier
            };
            broadcastService.post(
                // class BroadcastMessage is wrapped in module object
                new BroadcastMessageModule.BroadcastMessage(
                    'ajax-data-handler',
                    'instruction@' + this.processToken,
                    instruction
                )
            );
        }

        return EventHandler;
    }
);
