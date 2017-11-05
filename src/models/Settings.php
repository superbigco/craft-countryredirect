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
     * @var string
     */
    public $overrideLocaleParam = 'selected-locale';

    /**
     * @var false|string
     */
    public $redirectedParam = 'redirected';

    /**
     * @var string
     */
    public $cookieName = 'countryRedirect';

    /**
     * @var string
     */
    public $cookieNameBanner = 'countryRedirectBanner';

    /**
     * @var boolean
     */
    public $ignoreBots = true;

    /**
     * @var boolean
     */
    public $enabled = false;

    /**
     * @var array
     */
    public $countryMap = [];

    /**
     * @var array
     */
    public $ignoreSegments = [];

    /**
     * @var array
     */
    public $banners = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules ()
    {
        return [
            //[ 'someAttribute', 'string' ],
            //[ 'someAttribute', 'default', 'value' => 'Some Default' ],
        ];
    }
}
