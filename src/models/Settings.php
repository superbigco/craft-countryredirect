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

use craft\helpers\FileHelper;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Model;
use superbig\countryredirect\helpers\RedirectHelper;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property Redirect[] $redirectMap
 */
class Settings extends Model
{
    public    $enabled                      = false;
    public    $enableLogging                = false;
    public    $cacheEnabled                 = true;
    public    $devMode                      = false;
    public    $checkEveryTime               = false;
    public    $ignoreBots                   = true;
    public    $redirectMatchingElementOnly  = false;
    public    $overrideIp;
    public    $overrideLocaleParam          = 'selected-locale';
    public    $redirectedParam              = 'redirected';
    public    $bannerParam                  = 'fromBanner';
    public    $cookieName                   = 'countryRedirect';
    public    $cookieNameBanner             = 'countryRedirectBanner';
    public    $licenseKey                   = '';
    public    $minimumAcceptLanguageQuality = 80;
    public    $countryMap                   = [];
    public    $redirectMap                  = [];
    public    $ignoreSegments               = [];
    public    $banners                      = [];
    public    $dbPath;
    public    $tempPath;
    public    $accountAreaUrl               = 'https://www.maxmind.com/en/account';
    public    $cityDbDownloadUrl            = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
    public    $countryDbDownloadUrl         = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';
    public    $countryDbChecksumUrl         = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5';
    public    $cityDbFilename               = 'GeoLite2-City.mmdb';
    public    $countryDbFilename            = 'GeoLite2-Country.mmdb';
    protected $parsedRedirectMap;
    protected $parsedIgnoreSegments;

    public function init()
    {
        parent::init();

        if (!empty($this->countryMap)) {
            Craft::$app->getDeprecator()->log('CountryRedirect::Settings->countryMap', 'Country Redirect setting `countryMap` has been deprecated. Use `redirectMap` instead.');

            $this->redirectMap = RedirectHelper::mapLegacyCountryToRedirectMap($this->countryMap);
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    public function getCountryDownloadUrl()
    {
        return "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&suffix=tar.gz&license_key={$this->licenseKey}";
    }

    public function getCountryChecksumDownloadUrl()
    {
        return "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&suffix=tar.gz.md5&license_key={$this->licenseKey}";
    }

    public function getCityDownloadUrl()
    {
        return "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz&license_key={$this->licenseKey}";
    }

    public function getCityDbPath($isTempPath = false)
    {
        if ($isTempPath) {
            return $this->getTempPath($this->cityDbFilename);
        }

        return $this->getDbPath($this->cityDbFilename);
    }

    public function getCountryDbPath($isTempPath = false)
    {
        if ($isTempPath) {
            return $this->getTempPath($this->countryDbFilename);
        }

        return $this->getDbPath($this->countryDbFilename);
    }

    public function getDbPath($filename = null, $createDirectory = false)
    {
        $dbPath = $this->dbPath;

        if (empty($dbPath)) {
            $dbPath = Craft::$app->getPath()->getStoragePath() . \DIRECTORY_SEPARATOR . 'countryredirect';
        }

        if ($createDirectory) {
            FileHelper::createDirectory($dbPath);
        }

        return FileHelper::normalizePath($dbPath . \DIRECTORY_SEPARATOR . $filename);
    }

    public function getTempPath($filename = null, $createDirectory = true)
    {
        $tempPath = $this->tempPath;

        if (empty($tempPath)) {
            $tempPath = Craft::$app->getPath()->getTempPath() . '/countryredirect/';
        }

        if ($createDirectory) {
            FileHelper::createDirectory($tempPath);
        }

        return FileHelper::normalizePath($tempPath . \DIRECTORY_SEPARATOR . $filename);
    }

    public function hasValidLicenseKey()
    {
        return !empty($this->licenseKey);
    }

    /**
     * @return IgnoreSegment[]
     */
    public function getIgnoredSegments()
    {
        if (!$this->parsedIgnoreSegments) {
            $builtInIgnoreSegments = [
                'country-redirect',
            ];

            $this->parsedIgnoreSegments = array_map(function($segment) {
                return new IgnoreSegment(['rawSegment' => $segment]);
            }, array_merge($builtInIgnoreSegments, $this->ignoreSegments));
        }

        return $this->parsedIgnoreSegments;
    }


    /**
     * @return Redirect[]
     */
    public function getRedirectMap()
    {
        if (!$this->parsedRedirectMap) {
            $this->parsedRedirectMap = array_map(function($row) {
                return new Redirect($row);
            }, array_merge($this->redirectMap));
        }

        return $this->parsedRedirectMap;
    }
}
