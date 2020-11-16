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
    var CountryRedirectUpdater = {
        steps: {
            download: {
                action: 'country-redirect/default/download-database',
                text: 'Downloading GeoLite database'
            },
            verify: {
                action: 'country-redirect/default/unpack-database',
                text: 'Unpacking and verifying database'
            },
        },
        currentStep: -1,
        totalSteps: 0,
        isVisible: false,

        init: function ($updater) {
            var self = this;
            self.$dbWarning = $('[data-db-warning]');
            self.$updater = $updater;
            self.$start = self.$updater.find('.js-start');
            self.stepIds = Object.keys(self.steps);
            self.totalSteps = self.stepIds.length;
            self.$statusContainer = self.$updater.find('.js-progress');

            self.bindEvents();
        },

        bindEvents: function () {
            var self = this;
            self.$start.on('click', function (e) {
                event.preventDefault();

                self.start();
            });
        },

        start: function () {
            var self = this;
            var totalSteps = Object.keys(self.steps).length;

            if (!self._progressBar) {
                self._progressBar = new Craft.ProgressBar(self.$statusContainer);
            }

            self._progressBar.setItemCount(totalSteps);
            self.$statusContainer.removeClass('hidden');
            self._progressBar.showProgressBar();

            //self.reset();
            self.disableStart();
            self.next();
        },

        next: function () {
            var self = this;
            var step;
            var stepKey;
            self.currentStep = self.currentStep + 1;

            if (self.currentStep > (self.totalSteps - 1)) {
                if (self._progressBar) {
                    setTimeout(_ => {
                        self._progressBar.hideProgressBar();
                        self._progressBar.resetProgressBar();
                    }, 500);

                    setTimeout(_ => {
                        self.$statusContainer.addClass('hidden');
                    }, 900)
                }

                if (self.$dbWarning.length) {
                    self.$dbWarning.slideUp(500, function () {
                        self.$dbWarning.remove();
                    });
                }

                self.updateStatus('Database was updated');
                self.enableStart();

                return false;
            }

            self._progressBar.setProcessedItemCount(self.currentStep + 1);
            self._progressBar.updateProgressBar();

            stepKey = self.stepIds[self.currentStep];
            step = self.steps[stepKey];

            Craft.postActionRequest(step.action, {}, function (response) {
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

    var $updater = $('[data-countryredirect-updater]');
    if ($updater.length) {
        CountryRedirectUpdater.init($updater);
    }
})(window, Craft, jQuery);