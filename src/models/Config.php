<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\models;

use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Config extends Model
{
    // Public Properties
    // =========================================================================

    public $overrideLocaleParam = 'selected-locale';
    public $redirectedParam     = 'redirected';
    public $cookieName          = 'countryRedirect';
    public $cookieNameBanner    = 'countryRedirectBanner';
    public $ignoreBots          = true;
    public $enabled             = false;
    public $countryMap          = [];
    public $ignoreSegments      = [];
    public $banners             = [];
    public $ipAddress;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['overrideLocaleParam', 'redirectedParam', 'cookieName', 'cookieNameBanner'], 'string'],
        ];
    }
}
