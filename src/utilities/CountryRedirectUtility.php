<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\utilities;

use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\models\Site;
use JasonGrimes\Paginator;
use superbig\countryredirect\assetbundles\CountryRedirect\CountryRedirectAsset;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Utility;

/**
 * CountryRedirect Utility
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 */
class CountryRedirectUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('country-redirect', 'Country Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'country-redirect';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@superbig/countryredirect/assetbundles/countryredirect/dist/img/icon-mask.svg");
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(CountryRedirectAsset::class);

        $currentPage  = Craft::$app->getRequest()->getParam('page', 1);
        $itemsPerPage = 30;
        $count        = CountryRedirect::$plugin->log->getLogCount();
        $offset       = ($currentPage - 1) * $itemsPerPage;
        $urlPattern   = UrlHelper::cpUrl('utilities/country-redirect-log-utility?page=(:num)');
        $paginator    = new Paginator($count, $itemsPerPage, $currentPage, $urlPattern);
        $plugin       = CountryRedirect::$plugin;

        $sites = array_map(function($site) {
            /** @var Site $site */
            return [
                'label' => $site->name,
                'value' => $site->handle,
            ];
        }, Craft::$app->getSites()->getAllSites());

        return Craft::$app->getView()->renderTemplate(
            'country-redirect/utilities/CountryRedirect_Utility',
            [
                'settings'     => $plugin->getSettings(),
                'logCount'     => $plugin->log->getLogCount(),
                'logs'         => $plugin->log->getAllLogs($offset, $itemsPerPage),
                'paginator'    => $paginator,
                'entryClass'   => Entry::class,
                'sites'        => $sites,
                'dbExists'     => $plugin->getDatabase()->checkValidDb(),
                'dbUpdateTime' => $plugin->getDatabase()->getLastUpdateTime(),
            ]
        );
    }
}
