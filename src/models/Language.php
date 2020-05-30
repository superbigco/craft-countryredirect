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

use craft\base\Model;
use superbig\countryredirect\CountryRedirect;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Language extends Model
{
    public $quality;
    public $region;
    public $language;
    public $script;

    public function match(Redirect $redirect)
    {
    }

    public function getMatchQuality()
    {
        return $this->quality >= CountryRedirect::$plugin->getSettings()->minimumAcceptLanguageQuality;
    }
}
