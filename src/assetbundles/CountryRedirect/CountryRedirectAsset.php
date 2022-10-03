<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\assetbundles\CountryRedirect;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class CountryRedirectAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = "@superbig/countryredirect/assetbundles/CountryRedirect/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CountryRedirect.js',
        ];

        $this->css = [
            'css/CountryRedirect.css',
        ];

        parent::init();
    }
}
