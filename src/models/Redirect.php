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
use superbig\countryredirect\helpers\CountryRedirectHelper;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Redirect extends Model
{
    public $country           = [];
    public $continent         = [];
    public $language          = [];
    public $languageRegion    = [];
    public $targetSiteHandle;
    public $targetUrl;
    public $isDefault         = false;
    public $isInEuropeanUnion = null;

    public function init()
    {
        parent::init();

        if (!is_array($this->country)) {
            $this->country = (array)$this->country;
        }

        if (!is_array($this->continent)) {
            $this->continent = (array)$this->continent;
        }
    }

    public function matchCountry(string $country)
    {
        return in_array(CountryRedirectHelper::normalize($country), $this->country);
    }

    public function matchLanguage(string $language)
    {
        return in_array(CountryRedirectHelper::normalize($language), $this->language);
    }
}
