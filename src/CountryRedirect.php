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
use craft\helpers\UrlHelper;
use craft\services\Utilities;
use superbig\countryredirect\console\controllers\UpdateController;
use superbig\countryredirect\services\BannerService;
use superbig\countryredirect\services\DatabaseService;
use superbig\countryredirect\services\LogService;
use superbig\countryredirect\services\NavService;
use superbig\countryredirect\services\RedirectService;
use superbig\countryredirect\utilities\CountryRedirectLogUtility;
use superbig\countryredirect\utilities\CountryRedirectUtility;
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
use craft\web\Application as WebApplication;

/**
 * Class CountryRedirect
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property  RedirectService $countryRedirectService
 * @property  DatabaseService $database
 * @property  BannerService   $banner
 * @property  LogService      $log
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
            'countryRedirectService' => RedirectService::class,
            'banner'                 => BannerService::class,
            'database'               => DatabaseService::class,
            'log'                    => LogService::class,
            'nav'                    => NavService::class,
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

    public function installEventListeners()
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
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
            function() {
                // Install these only after all other plugins have loaded
                $request = Craft::$app->getRequest();
                $this->installGlobalEventListeners();

                Craft::$app->on(WebApplication::EVENT_INIT, function() use ($request) {
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

    public function installGlobalEventListeners()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('countryRedirect', CountryRedirectVariable::class);
            }
        );
    }

    public function installCpEventListeners()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['country-redirect/clear-logs'] = 'country-redirect/default/clear-logs';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = CountryRedirectUtility::class;
                $event->types[] = CountryRedirectLogUtility::class;
            }
        );
    }

    public function installSiteEventListeners()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['country-redirect/update-database'] = 'country-redirect/default/update-database';
                $event->rules['country-redirect/info']            = 'country-redirect/default/info';
            }
        );
    }

    public function handleSiteRequest()
    {
        self::$plugin->countryRedirectService->maybeRedirect();
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
        $validDb = $this->getDatabase()->checkValidDb();

        return Craft::$app->view->renderTemplate(
            'country-redirect/settings',
            [
                'settings' => $this->getSettings(),
                'validDb'  => $validDb,
            ]
        );
    }
}
