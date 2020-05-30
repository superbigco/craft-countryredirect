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

use craft\helpers\ArrayHelper;
use craft\models\Site;
use craft\base\Model;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property Site    $currentSite
 * @property Site    $targetSite
 * @property Country $matchedCountry
 * @property City    $matchedCity
 */
class RedirectRequest extends Model
{
    public $ipAddress;
    public $userAgent;
    public $acceptedLanguages;
    public $currentUri;
    public $redirectUrl;
    public $isAutoRedirectEnabled   = false;
    public $isOverridden            = false;
    public $isInEuropeanUnion       = false;
    public $isCrawler               = false;
    public $shouldRedirect          = false;
    public $matchingIgnoredSegments = [];
    public $currentSite;
    public $targetSite;
    public $currentElement;
    public $matchedElement;
    public $matchedCountry;
    public $matchedCity;
    public $matchedLanguages        = [];

    public function init()
    {
        parent::init();
    }

    public function preventRedirect()
    {
        $this->shouldRedirect = false;

        return $this;
    }

    /**
     * @param IgnoreSegment[] $ignoreSegments
     *
     * @return $this
     */
    public function addIgnoredSegments($ignoreSegments)
    {
        $this->matchingIgnoredSegments = array_merge($this->matchingIgnoredSegments, $ignoreSegments);

        return $this;
    }

    public function hasIp()
    {
        return filter_var($this->ipAddress, FILTER_VALIDATE_IP) !== false;
    }

    public function getCountryCode()
    {
        return ArrayHelper::getValue($this->matchedCountry);
    }

    public function getCityCode()
    {

    }
}
