<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use superbig\countryredirect\console\controllers\UpdateController;
use superbig\countryredirect\services\CountryRedirect_DatabaseService;
use superbig\countryredirect\services\CountryRedirect_LogService;
use superbig\countryredirect\services\CountryRedirectService;
use superbig\countryredirect\utilities\CountryRedirectLogUtility;
use superbig\countryredirect\variables\CountryRedirectVariable;
use superbig\countryredirect\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class CountryRedirect
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property  CountryRedirectService          $countryRedirectService
 * @property  CountryRedirect_DatabaseService $database
 * @property   CountryRedirect_LogService     $log
 * @method  Settings getSettings()
 */
class CountryRedirect extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CountryRedirect
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public $version       = '2.0.0';
    public $schemaVersion = '2.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'countryRedirectService' => CountryRedirectService::class,
            'database'               => CountryRedirect_DatabaseService::class,
            'log'                    => CountryRedirect_LogService::class,
        ]);


        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'superbig\countryredirect\console\controllers';

            Craft::$app->controllerMap['country-redirect'] = [
                'class' => UpdateController::class,
            ];
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['country-redirect/update-database'] = 'country-redirect/default/update-database';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['country-redirect/clear-logs'] = 'country-redirect/default/clear-logs';
            }
        );


        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('countryRedirect', CountryRedirectVariable::class);
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = CountryRedirectLogUtility::class;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'country-redirect',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        $request = Craft::$app->getRequest();

        if ($request->getIsSiteRequest() && !$request->getIsLivePreview()) {
            $this->countryRedirectService->maybeRedirect();
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        $validDb = $this->database->checkValidDb();

        return Craft::$app->view->renderTemplate(
            'country-redirect/settings',
            [
                'settings' => $this->getSettings(),
                'validDb'  => $validDb,
            ]
        );
    }
}
