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

        function EventHandler(message) {
            this.canCancel = true;
            this.message = message;
            this.action = message.action;
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
            var that = this;
            var $content = $(instruction.content);
            var $form = $content.find('#' + instruction.formId)
                .on('submit', function(evt) {
                    evt.preventDefault();
                    that.verifyAction(instruction, $form, $invalid);
                });
            var $invalid = $content.find('#' + instruction.invalidId)
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
                            if (that.canCancel) {
                                that.cancelAction(instruction);
                                that.broadcast('revert');
                            }
                            that.modal.trigger('modal-dismiss');
                        }
                    },
                    {
                        btnClass: 'btn-warning',
                        text: instruction.button.confirm,
                        trigger: function(evt) {
                            $form.submit();
                        }
                    }
                ]
            }).on('hidden.bs.modal', function(evt) {
                if (that.canCancel) {
                    that.cancelAction(instruction);
                    that.broadcast('revert');
                }
                // remove memory reference with next tick
                setTimeout(function() {
                    that.modal = null;
                }, 0);
            });
        }

        EventHandler.prototype.isRelevant = function() {
            return this.response.headers
                && this.response.headers.get('x-typo3-emitevent') === 'sudo-mode:confirmation-request';
        }

        EventHandler.prototype.requestAction = function() {
            var that = this;
            $.ajax({
                method: 'GET',
                dataType: 'json',
                url: this.response.body.uri
            }).done(function(response) {
                that.showModal(response);
            });
        }

        EventHandler.prototype.verifyAction = function(instruction, $form, $invalid) {
            var that = this;
            var formData = new FormData($form.get(0));
            $invalid.hide();
            $.ajax({
                method: 'POST',
                url: instruction.uri.verify,
                data: formData,
                processData: false,
                contentType: false
            }).done(function(response, status, xhr) {
                that.canCancel = false;
                that.broadcast(that.action);
                that.modal.trigger('modal-dismiss');
            }).fail(function(xhr, status, error) {
                $invalid.show();
            });
        }

        EventHandler.prototype.cancelAction = function(instruction) {
            this.canCancel = false;
            var that = this;
            $.ajax({
                method: 'GET',
                url: instruction.uri.cancel
            }).fail(function(xhr, status, error) {
                console.warn('Cancel action failed: ' + error);
                that.canCancel = true;
            })
        }

        EventHandler.prototype.broadcast = function(action) {
            var instruction = {
                action: action,
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
