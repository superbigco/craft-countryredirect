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

use Craft;
use craft\base\Utility;
use craft\helpers\UrlHelper;
use JasonGrimes\Paginator;

use superbig\countryredirect\assetbundles\CountryRedirect\CountryRedirectAsset;
use superbig\countryredirect\CountryRedirect;

/**
 * CountryRedirectLog Utility
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 */
class CountryRedirectLogUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('country-redirect', 'Country Redirect Log');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'country-redirect-log-utility';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias("@superbig/countryredirect/assetbundles/CountryRedirect/dist/img/icon-mask.svg");
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

        $currentPage = Craft::$app->getRequest()->getParam('page', 1);
        $itemsPerPage = 30;
        $count = CountryRedirect::$plugin->getLog()->getLogCount();
        $offset = ($currentPage - 1) * $itemsPerPage;
        $urlPattern = UrlHelper::cpUrl('utilities/country-redirect-log-utility?page=(:num)');
        $paginator = new Paginator($count, $itemsPerPage, $currentPage, $urlPattern);

        return Craft::$app->getView()->renderTemplate(
            'country-redirect/utilities/CountryRedirect_LogUtility',
            [
                'logCount' => CountryRedirect::$plugin->getLog()->getLogCount(),
                'logs' => CountryRedirect::$plugin->getLog()->getAllLogs($offset, $itemsPerPage),
                'paginator' => $paginator,
            ]
        );
    }
}
