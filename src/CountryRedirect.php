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

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\Utilities;

use craft\web\Application as WebApplication;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use superbig\countryredirect\models\Settings;
use superbig\countryredirect\services\CountryRedirectService;
use superbig\countryredirect\services\DatabaseService;
use superbig\countryredirect\services\LogService;
use superbig\countryredirect\utilities\CountryRedirectLogUtility;

use superbig\countryredirect\variables\CountryRedirectVariable;
use yii\base\Event;

/**
 * Class CountryRedirect
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property  CountryRedirectService $countryRedirectService
 * @property  DatabaseService        $database
 * @property  LogService             $log
 * @method  Settings getSettings()
 */
class CountryRedirect extends Plugin
{
    use ServicesTrait;

    // Static Properties
    // =========================================================================

    /**
     * @var CountryRedirect
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '2.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'countryRedirectService' => CountryRedirectService::class,
            'database' => DatabaseService::class,
            'log' => LogService::class,
        ]);


        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'superbig\countryredirect\console\controllers';
        }

        $this->installEventListeners();

        Craft::info(
            Craft::t(
                'country-redirect',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function installEventListeners(): void
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event): void {
                if ($event->plugin === $this) {
                    $request = Craft::$app->getRequest();

                    if ($request->isCpRequest) {
                        $url = UrlHelper::cpUrl('settings/plugins/country-redirect');

                        Craft::$app->getResponse()->redirect($url)->send();
                    }
                }
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function(): void {
                // Install these only after all other plugins have loaded
                $request = Craft::$app->getRequest();
                $this->installGlobalEventListeners();

                Craft::$app->on(WebApplication::EVENT_INIT, function() use ($request): void {
                    if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
                        $this->handleSiteRequest();
                    }
                });

                if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
                    $this->installSiteEventListeners();
                }

                if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
                    $this->installCpEventListeners();
                }
            }
        );
    }

    public function installGlobalEventListeners(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('countryRedirect', CountryRedirectVariable::class);
            }
        );
    }

    public function installCpEventListeners(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function(RegisterUrlRulesEvent $event): void {
                $event->rules['country-redirect/clear-logs'] = 'country-redirect/default/clear-logs';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = CountryRedirectLogUtility::class;
            }
        );
    }

    public function installSiteEventListeners(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            static function(RegisterUrlRulesEvent $event): void {
                $event->rules['country-redirect/update-database'] = 'country-redirect/default/update-database';
                $event->rules['country-redirect/info'] = 'country-redirect/default/info';
            }
        );
    }

    public function handleSiteRequest(): void
    {
        self::$plugin->countryRedirectService->maybeRedirect();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): \superbig\countryredirect\models\Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        $validDb = $this->getDatabase()->checkValidDb();

        return Craft::$app->view->renderTemplate(
            'country-redirect/settings',
            [
                'settings' => $this->getSettings(),
                'validDb' => $validDb,
            ]
        );
    }
}
