/**
 * Country Redirect plugin for Craft CMS
 *
 * Country Redirect JS
 *
 * @author    Superbig
 * @copyright Copyright (c) 2017 Superbig
 * @link      https://superbig.co
 * @package   CountryRedirect
 * @since     2.0.0
 */
(function (window, Craft, $) {
    var Preview = {
        isVisible: false,

        init: function ($updater) {
            var self = this;
            self.$updater = $updater;
            self.$form = self.$updater.find('form');
            self.$start = self.$updater.find('button');
            self.$statusContainer = self.$updater.find('.js-progress');

            self.bindEvents();
        },

        bindEvents: function () {
            var self = this;
            self.$form.on('submit', function (e) {
                e.preventDefault();

                self.start();
            });
        },

        start: function () {
            var self = this;
            self.$statusContainer.removeClass('hidden');

            //self.reset();
            self.disableStart();
            self.next();
        },

        next: function () {
            var self = this;

            Craft.postActionRequest('country-redirect/preview/preview', {
                ipAddress: '',
                sites: [],
            }, function (response) {
                if (!response) {
                    Craft.cp.displayError('There occurred an error when running the step. Check the plugin logs (countryredirect.log) for further information.');
                    self.enableStart();

                    return false;
                }

                if (response.hasOwnProperty('success')) {
                    self.next();
                } else {
                    self.updateErrorStatus(response.error);
                    self.enableStart();

                    return false;
                }
            });

        },

        updateStatus: function (text) {
            Craft.cp.displayNotice(text);
        },

        updateErrorStatus: function (error) {
            Craft.cp.displayError(error);
        },

        disableStart: function () {
            this.$start.prop('disabled', true);
        },

        enableStart: function () {
            this.$start.prop('disabled', false);
        },

        reset: function () {
            this.currentStep = -1;
        }
    }

    var $updater = $('[data-countryredirect-preview]');
    if ($updater.length) {
        Preview.init($updater);
    }
})(window, Craft, jQuery);