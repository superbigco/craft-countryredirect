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

use Craft;
use craft\base\Model;

use craft\helpers\FileHelper;
use superbig\countryredirect\CountryRedirect;

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
    public $overrideIp = null;
    public $overrideLocaleParam = 'selected-locale';
    public $redirectedParam = 'redirected';
    public $queryParamsValue = '✓';
    public $bannerParam = 'fromBanner';
    public $cookieName = 'countryRedirect';
    public $cookieNameBanner = 'countryRedirectBanner';
    public $licenseKey = '';
    public $ignoreBots = true;
    public $countryMap = [];
    public $ignoreSegments = [];
    public $ignoreUrlPatterns = [];
    public $stripSlashWhenComparingExactUrlMatches = true;
    public $appendExistingQueryParamsToUrl = true;
    public $banners = [];
    public $dbPath;
    public $tempPath;
    public $accountAreaUrl = 'https://www.maxmind.com/en/account';
    public $cityDbDownloadUrl = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
    public $countryDbDownloadUrl = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';
    public $countryDbChecksumUrl = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5';
    public $cityDbFilename = 'GeoLite2-City.mmdb';
    public $countryDbFilename = 'GeoLite2-Country.mmdb';

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
}
