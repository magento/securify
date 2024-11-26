/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global grecaptcha */
define(
    [
        'uiComponent',
        'jquery',
        'ko',
        'underscore',
        'Magento_ReCaptchaFrontendUi/js/registry',
        'Magento_ReCaptchaFrontendUi/js/reCaptchaScriptLoader',
        'Magento_ReCaptchaFrontendUi/js/nonInlineReCaptchaRenderer'
    ], function (Component, $, ko, _, registry, reCaptchaLoader, nonInlineReCaptchaRenderer) {
        'use strict';

        return Component.extend({

            defaults: {
                template: 'Magento_ReCaptchaFrontendUi/reCaptcha',
                reCaptchaId: 'recaptcha'
            },

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super();
                this._loadApi();
            },

            /**
             * Loads recaptchaapi API and triggers event, when loaded
             * @private
             */
            _loadApi: function () {
                if (this._isApiRegistered !== undefined) {
                    if (this._isApiRegistered === true) {
                        $(window).trigger('recaptchaapiready');
                    }

                    return;
                }
                this._isApiRegistered = false;

                // global function
                window.globalOnRecaptchaOnLoadCallback = function () {
                    this._isApiRegistered = true;
                    $(window).trigger('recaptchaapiready');
                }.bind(this);

                reCaptchaLoader.addReCaptchaScriptTag();
            },

            /**
             * Checking that reCAPTCHA is invisible type
             * @returns {Boolean}
             */
            getIsInvisibleRecaptcha: function () {
                if (this.settings ===

                    void 0) {
                    return false;
                }

                return this.settings.invisible;
            },

            /**
             * reCAPTCHA callback
             * @param {String} token
             */
            reCaptchaCallback: function (token) {
                var submitButton;

                if (this.getIsInvisibleRecaptcha()) {
                    this.tokenField.value = token;
                    submitButton = this.$parentForm.find('button:not([type]), [type=submit]');
                    if (submitButton.length) { //eslint-disable-line max-depth
                        submitButton.attr('disabled', false);
                    }
                    this.$parentForm.submit();
                }
            },

            /**
             * Initialize reCAPTCHA after first rendering
             */
            initCaptcha: function () {
                var $parentForm,
                    $wrapper,
                    $reCaptcha,
                    widgetId,
                    parameters;

                if (this.captchaInitialized || this.settings ===

                    void 0) {
                    return;
                }

                this.captchaInitialized = true;

                /*
                 * Workaround for data-bind issue:
                 * We cannot use data-bind to link a dynamic id to our component
                 * See:
                 * https://stackoverflow.com/questions/46657573/recaptcha-the-bind-parameter-must-be-an-element-or-id
                 *
                 * We create a wrapper element with a wrapping id and we inject the real ID with jQuery.
                 * In this way we have no data-bind attribute at all in our reCAPTCHA div
                 */
                $wrapper = $('#' + this.getReCaptchaId() + '-wrapper');
                $reCaptcha = $wrapper.find('.g-recaptcha');
                $reCaptcha.attr('id', this.getReCaptchaId());

                $parentForm = $wrapper.parents('form');

                if (this.settings === undefined) {

                    return;
                }

                parameters = _.extend(
                    {
                        'callback': function (token) { // jscs:ignore jsDoc
                            this.reCaptchaCallback(token);
                            this.validateReCaptcha(true);
                        }.bind(this),
                        'expired-callback': function () {
                            this.validateReCaptcha(false);
                        }.bind(this)
                    },
                    this.settings.rendering
                );

                if (parameters.size === 'invisible' && parameters.badge !== 'inline') {
                    nonInlineReCaptchaRenderer.add($reCaptcha, parameters);
                }

                // eslint-disable-next-line no-undef
                widgetId = grecaptcha.render(this.getReCaptchaId(), parameters);
                this.initParentForm($parentForm, widgetId);

                registry.ids.push(this.getReCaptchaId());
                registry.captchaList.push(widgetId);
                registry.tokenFields.push(this.tokenField);

            },

            /**
             * Initialize parent form.
             *
             * @param {Object} parentForm
             * @param {String} widgetId
             */
            initParentForm: function (parentForm, widgetId) {
                var listeners;

                if (this.getIsInvisibleRecaptcha() && parentForm.length > 0) {
                    parentForm.on('submit', function (event) {
                        var submitButton;

                        if (!this.tokenField.value) {
                            submitButton = this.$parentForm.find('button:not([type]), [type=submit]');
                            if (submitButton.length) { //eslint-disable-line max-depth
                                submitButton.attr('disabled', true);
                            }
                            // eslint-disable-next-line no-undef
                            grecaptcha.execute(widgetId);
                            event.preventDefault(event);
                            event.stopImmediatePropagation();
                        }
                    }.bind(this));

                    // Move our (last) handler topmost. We need this to avoid submit bindings with ko.
                    listeners = $._data(parentForm[0], 'events').submit;
                    listeners.unshift(listeners.pop());

                    // Create a virtual token field
                    this.tokenField = $('<input type="text" name="token" style="display: none" />')[0];
                    this.$parentForm = parentForm;
                    parentForm.append(this.tokenField);
                } else {
                    this.tokenField = null;
                }
                let submitButton = parentForm.find('button:not([type]), [type=submit]');

                if (submitButton.length) {
                    submitButton.prop('disabled', false);
                }
            },

            /**
             * Validates reCAPTCHA
             * @param {*} state
             * @returns {jQuery}
             */
            validateReCaptcha: function (state) {
                if (!this.getIsInvisibleRecaptcha()) {
                    return $(document).find('input[type=checkbox].required-captcha').prop('checked', state);
                }
            },

            /**
             * Render reCAPTCHA
             */
            renderReCaptcha: function () {
                if (window.grecaptcha && window.grecaptcha.render) { // Check if reCAPTCHA is already loaded
                    this.initCaptcha();
                } else { // Wait for reCAPTCHA to be loaded
                    $(window).on('recaptchaapiready', function () {
                        this.initCaptcha();
                    }.bind(this));
                }
            },

            /**
             * Get reCAPTCHA ID
             * @returns {String}
             */
            getReCaptchaId: function () {
                return this.reCaptchaId;
            }
        });
    });
