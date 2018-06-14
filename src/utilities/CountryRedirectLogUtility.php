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

use superbig\countryredirect\assetbundles\CountryRedirect\CountryRedirectAsset;
use superbig\payments\Payments;
use superbig\payments\assetbundles\paymentsutilityutility\PaymentsUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

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
        return Craft::t('payments', 'Country Redirect Log');
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

        return Craft::$app->getView()->renderTemplate(
            'country-redirect/utilities/CountryRedirect_LogUtility',
            [
                'logs' => [],
            ]
        );
    }
}
