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
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var boolean
     */
    public $enabled = false;

    /**
     * @var boolean
     */
    public $enableLogging = false;

    /**
     * @var boolean
     */
    public $devMode = false;

    /**
     * Check location on every request, regardless of the cookie value
     *
     * @var boolean
     */
    public $checkEveryTime = false;

    /**
     * Local mode allow you to lookup your current IP from a external service.
     *
     * This allow you to test the service locally, since the IP normally would be 127.0.0.1.
     *
     * @var boolean
     */
    public $localMode = false;

    /**
     * Override what is considered the users IP. Useful when testing locally, or when you want to debug
     */
    public $overrideIp          = null;
    public $overrideLocaleParam = 'selected-locale';
    public $redirectedParam     = 'redirected';
    public $bannerParam         = 'fromBanner';
    public $cookieName          = 'countryRedirect';
    public $cookieNameBanner    = 'countryRedirectBanner';
    public $ignoreBots          = true;
    public $countryMap          = [];
    public $ignoreSegments      = [];
    public $banners             = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[ 'someAttribute', 'string' ],
            //[ 'someAttribute', 'default', 'value' => 'Some Default' ],
        ];
    }
}
