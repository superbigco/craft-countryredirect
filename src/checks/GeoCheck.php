<?php

namespace superbig\countryredirect\checks;

use Craft;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\RedirectRequest;

class GeoCheck extends BaseCheck implements CheckInterface
{
    const TYPE_COUNTRY = 'country';
    const TYPE_CITY    = 'city';

    /** @var RedirectRequest $redirectRequest */
    public $redirectRequest;

    public function execute(RedirectRequest $redirectRequest)
    {
        $this->redirectRequest = $redirectRequest;

        if (empty($redirectRequest->ipAddress)) {
            $this->lookupIP();
        }

        if ($redirectRequest->hasIp()) {
            $this->lookupLocation(self::TYPE_COUNTRY);
            $this->lookupLocation(self::TYPE_CITY);
        }
        /*
        $countryCode   = $this->getCountryCode();
         $countryLocale = $this->getSiteHandle($countryCode);

         if ($url = $this->getRedirectUrl($countryLocale)) {
             // Set redirected flash/query param
             if ($redirectParam = $this->config->redirectedParam) {
                 Craft::$app->getSession()->setFlash($redirectParam, true);
             }

             if ($this->config->enableLogging) {
                 CountryRedirect::$plugin->getLog()->logRedirect($url);
             }

             return Craft::$app->getResponse()->redirect($url);
         }
         */
    }

    public function getTargetSite()
    {
        // @todo Get country, city, continent and locale/browser language
    }

    private function lookupIP()
    {
        $ip = null;

        if (!$this->getRequestService()->getIsConsoleRequest()) {
            $ip = $this->getRequestService()->getUserIP();
        }

        if (!empty($this->getSettings()->overrideIp)) {
            $ip = $this->getSettings()->overrideIp;
        }

        $isLocalIp = $ip === '::1' || $ip === '127.0.0.1';

        if ($isLocalIp || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        $this->redirectRequest->ipAddress = $ip;
    }

    public function lookupLocation(string $type = 'country')
    {
        if (!$this->redirectRequest->hasIp()) {
            return false;
        }

        $cacheEnabled = $this->getSettings()->cacheEnabled;
        $ip           = $this->redirectRequest->ipAddress;
        $cacheKey     = $this->getCacheKey($ip, $type);

        // Check cache first
        if ($cacheEnabled && $cacheRecord = Craft::$app->cache->get($cacheKey)) {
            $type === static::TYPE_COUNTRY ? $this->setCountry($cacheRecord) : $this->setCity($cacheRecord);

            return true;
        }

        try {
            $record = $type === static::TYPE_COUNTRY ? CountryRedirect::$plugin->getDatabase()->getCountryFromIp($ip) : CountryRedirect::$plugin->getDatabase()->getCityFromIp($ip);

            if ($cacheEnabled) {
                Craft::$app->cache->set($cacheKey, $record);
            }

            $type === static::TYPE_COUNTRY ? $this->setCountry($record) : $this->setCity($record);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setCountry(Country $country)
    {
        $this->redirectRequest->matchedCountry = $country;
    }

    public function setCity(City $city)
    {
        $this->redirectRequest->matchedCity = $city;
    }

    public function getCacheKey(string $ip, string $type = 'country')
    {
        $ip = md5($ip);

        return "countryRedirect-info-{$ip}-{$type}";
    }
}