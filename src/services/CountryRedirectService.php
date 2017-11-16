<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\services;

use craft\helpers\FileHelper;
use GuzzleHttp\Client;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\models\Link;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class CountryRedirectService extends Component
{
    // Public Methods
    // =========================================================================

    protected $config;
    protected $countryMap;
    protected $urls;
    protected $localDatabaseFilename;
    protected $localDatabasePath;
    protected $unpackedDatabasePath;
    protected $localDatabasePathWithoutFilename;

    public function init ()
    {
        parent::init();

        $this->urls                             = [
            'city'            => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
            'country'         => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz',
            'countryChecksum' => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5'
        ];
        $this->localDatabaseFilename            = 'GeoLite2-Country.mmdb.gz';
        $this->localDatabasePathWithoutFilename = rtrim(dirname(__FILE__, 2) . '/database/', '/');
        $this->localDatabasePath                = rtrim(dirname(__FILE__, 2) . '/database/', '/') . DIRECTORY_SEPARATOR . $this->localDatabaseFilename;
        $this->unpackedDatabasePath             = str_replace('.gz', '', $this->localDatabasePath);
    }

    public function maybeRedirect ()
    {
        // Get the site URL config setting
        $siteUrl        = Craft::$app->config->general->siteUrl;
        $enabled        = $this->getSetting('enabled');
        $ignoreSegments = $this->getSetting('ignoreSegments');
        if ( !is_array($siteUrl) ) {
            throw new \Exception(Craft::t('Site URL is not an array'));
        }
        if ( $this->getSetting('ignoreBots') ) {
            $crawlerDetect = new CrawlerDetect;
            if ( $crawlerDetect->isCrawler() ) {
                return false;
            }
        }
        if ( !empty($ignoreSegments) ) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            foreach ($ignoreSegments as $segment) {
                if ( strpos($path, $segment) ) {
                    return false;
                }
            }
        }
        // Don't redirect if the plugin is soft disabled
        if ( !$enabled ) {
            return false;
        }
        // Depending on enabled setting, do some extra checks
        if ( !is_bool($enabled) && is_array($enabled) ) {
            $user = craft()->userSession->getUser();
            // Should we check logged in users?
            // TODO: Add a setting to redirect for specific user groups?
            if ( isset($enabled['loggedIn']) ) {
                if ( $user && !$enabled['loggedIn'] ) {
                    return false;
                }
            }
            // Should we check anon users?
            if ( isset($enabled['anonymous']) ) {
                if ( !$user && !$enabled['anonymous'] ) {
                    return false;
                }
            }
        }
        $countryCode   = $this->getCountryCode();
        $countryLocale = $this->getCountryLocale($countryCode);
        if ( $url = $this->getRedirectUrl($countryLocale) ) {
            // Set redirected flash/query param
            if ( $redirectParam = $this->getSetting('redirectedParam') ) {
                craft()->userSession->setFlash($redirectParam, true);
            }
            craft()->request->redirect($url);
        }
    }

    public function getLinks ()
    {
        // Get locales
        $locales             = craft()->i18n->getSiteLocales();
        $links               = [];
        $countryMap          = $this->getCountryMap();
        $overrideLocaleParam = $this->getOverrideLocaleParam();

        foreach ($locales as $locale) {
            $link          = new Link();
            $localeSiteUrl = craft()->config->getLocalized('siteUrl', $locale->getId());
            $link->setAttribute('locale', $locale->getId());
            $link->setAttribute('locale_name', $locale->getName());
            $link->setAttribute('url', rtrim($localeSiteUrl, '?') . '?' . http_build_query([ $overrideLocaleParam => '✓' ]));
            $links[] = $link;
        }

        return $links;
    }

    public function wasRedirected ()
    {
        $param = $this->getSetting('redirectedParam');

        return craft()->userSession->hasFlash($param) || craft()->request->getParam($param);
    }

    public function wasOverridden ()
    {
        $param = $this->getSetting('overrideLocaleParam');

        return craft()->request->getParam($param);
    }

    public function getBanner ()
    {
        $countryCode   = $this->getCountryCode();
        $countryLocale = $this->getCountryLocale($countryCode);
        $banners       = $this->getSetting('banners');
        $info          = $this->getInfo();
        $redirectUrl   = $this->getRedirectUrl($countryLocale);
        if ( !$this->getBannerCookie() && $redirectUrl && isset($banners[ $countryLocale ]) ) {
            return new CountryRedirect_BannerModel([
                'text'        => $banners[ $countryLocale ],
                'url'         => $redirectUrl,
                'countryName' => $info ? $info->name : null,
            ]);
        }
    }

    public function getInfo ()
    {
        $ip   = $this->getIpAddress();
        $info = $this->getInfoFromIp($ip);

        return $info ? $info->country : null;
    }

    protected function getCountryFromIpAddress ()
    {
        $ip = $this->getIpAddress();
        if ( $ip == '::1' || $ip == '127.0.0.1' || !$ip ) {
            return '*';
        }
        $cacheKey = 'countryRedirect-ip-' . $ip;
        // Check cache first
        if ( $cacheRecord = Craft::$app->cache->get($cacheKey) ) {
            return $cacheRecord;
        }
        $info = $this->getInfoFromIp($ip);
        if ( $info ) {
            return $info->country->isoCode;
        }

        return '*';
    }

    public function getInfoFromIp ($ip = null)
    {
        if ( $ip ) {
            $ip = $this->getIpAddress();

            if ( $ip == '::1' || $ip == '127.0.0.1' ) {
                return null;
            }

            $cacheKey = 'countryRedirect-info-' . $ip;

            // Check cache first
            if ( $cacheRecord = Craft::$app->cache->get($cacheKey) ) {
                return $cacheRecord;
            }

            try {
                // This creates the Reader object, which should be reused across lookups.
                $reader = new Reader($this->unpackedDatabasePath);
                $record = $reader->country($ip);

                Craft::$app->cache->set($cacheKey, $record);

                return $record;
            }
            catch (\Exception $e) {
                return null;
            }
        }
    }

    public function getIpAddress ()
    {
        $ip         = craft()->request->getIpAddress();
        $headerKeys = [
            // Shared ISP
            // Sucuri
            'HTTP_X_SUCURI_CLIENTIP',
            // CloudFlare
            'HTTP_CF_CONNECTING_IP',
            // Akamai?
            'HTTP_TRUE_CLIENT_IP',
            //'HTTP_X_CLUSTER_CLIENT_IP',
            // Fastly
            'HTTP_X_REAL_IP',
        ];
        foreach ($headerKeys as $key) {
            if ( isset($_SERVER[ $key ]) ) {
                $ip = $_SERVER[ $key ];
            }
        }
        if ( !filter_var($ip, FILTER_VALIDATE_IP) ) {
            return null;
        }

        return $ip;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getSetting ($key)
    {
        if ( !isset($this->config) ) {
            $this->config = CountryRedirect::$plugin->getSettings();
        }

        return $this->config->$key;
    }

    /**
     * @param null $url
     *
     * @return null|string
     */
    private function appendRedirectedParamToUrl ($url = null)
    {
        $param = $this->getSetting('redirectedParam');

        if ( !$param ) {
            return $url;
        }
        $query     = $param . '=✓';
        $parsedUrl = parse_url($url);

        if ( empty($parsedUrl['path']) ) {
            $url .= '/';
        }

        $separator = empty($parsedUrl['query']) ? '?' : '&';
        $url       .= $separator . $query;

        return $url;
    }

    public function downloadDatabase ()
    {
        if ( !FileHelper::isWritable($this->localDatabasePathWithoutFilename) ) {
            Craft::error('Database folder is not writeable: ' . $this->localDatabasePathWithoutFilename, __METHOD__);

            return [
                'error' => 'Database folder is not writeable: ' . $this->localDatabasePathWithoutFilename,
            ];
        }
        $tempPath = Craft::$app->path->getTempPath() . DIRECTORY_SEPARATOR . 'countryredirect' . DIRECTORY_SEPARATOR;

        FileHelper::createDirectory($tempPath);

        $tempFile = $tempPath . $this->localDatabaseFilename;
        Craft::info('Download database to: ' . $this->localDatabasePath, __METHOD__);

        try {
            $guzzle = new Client();

            $response = $guzzle
                ->get($this->urls['country'], [
                    'sink' => $tempFile,
                ]);

            @unlink($this->localDatabasePath);

            FileHelper::createDirectory($this->localDatabasePathWithoutFilename);
            copy($tempFile, $this->localDatabasePath);
            @unlink($tempFile);
        }
        catch (\Exception $e) {
            Craft::error('Failed to write downloaded database to: ' . $this->localDatabasePath . ' ' . $e->getMessage(), __METHOD__);

            return [
                'error' => 'Failed to write downloaded database to file',
            ];
        }

        return [
            'success' => true,
        ];
    }

    /**
     * @return array
     */
    public function unpackDatabase ()
    {
        try {
            $guzzle   = new Client();
            $response = $guzzle
                ->get($this->urls['countryChecksum']);

            $remoteChecksum = (string) $response->getBody();
        }
        catch (\Exception $e) {
            Craft::error('Was not able to get checksum from GeoLite url: ' . $this->urls['countryChecksum'], __METHOD__);

            return [
                'error' => 'Failed to get remote checksum for Country database',
            ];
        }
        $result = gzdecode(file_get_contents($this->localDatabasePath));
        if ( md5($result) !== $remoteChecksum ) {
            Craft::error('Remote checksum for Country database doesn\'t match downloaded database. Please try again or contact support.', __METHOD__);

            return [
                'error' => 'Remote checksum for Country database doesn\'t match downloaded database. Please try again or contact support.'
            ];
        }
        Craft::info('Unpacking database to: ' . $this->unpackedDatabasePath, __METHOD__);
        $write = file_put_contents($this->unpackedDatabasePath, $result);
        if ( !$write ) {
            Craft::error('Was not able to write unpacked database to: ' . $this->unpackedDatabasePath, __METHOD__);

            return [
                'error' => 'Was not able to write unpacked database to: ' . $this->unpackedDatabasePath,
            ];
        }
        @unlink($this->localDatabasePath);

        return [
            'success' => true,
        ];
    }

    /**
     * @return bool
     */
    public function checkValidDb ()
    {
        return @file_exists($this->unpackedDatabasePath);
    }
    /*
    *   Cookies
    *
    */
    // private function checkForCookie ($countryCode = null)
    // {
    //     $cookie = $this->_getCookie($this->getSetting('cookieName'));
    //
    //     if (!$cookie && $countryCode ) {
    //         $time = time() + 60 * 60 * 24 * 30;
    //         $this->_setCookie('countryRedirect', $countryCode, $time);
    //
    //         return $country;
    //     }
    //
    //     return false;
    // }
    /**
     * @return null
     */
    protected function getCountryCookie ()
    {
        return $this->_getCookie($this->getSetting('cookieName'));
    }

    /**
     * @return null
     */
    protected function getBannerCookie ()
    {
        return $this->_getCookie($this->getSetting('cookieNameBanner'));
    }

    /**
     * @param null $countryCode
     *
     * @return null
     */
    protected function setCountryCookie ($countryCode = null)
    {
        $time = time() + 60 * 60 * 24 * 30;
        $this->_setCookie($this->getSetting('cookieName'), $countryCode, $time);

        return $this->getCountryCookie();
    }

    /**
     *
     */
    public function removeCountryCookie ()
    {
        $this->_setCookie($this->getSetting('cookieName'), null, -1);
    }

    /**
     * set() takes the same parameters as PHP's builtin setcookie();
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param mixed  $secure
     * @param mixed  $httponly
     */
    private function _setCookie ($name = "", $value = "", $expire = 0, $path = "/", $domain = "", $secure = false, $httponly = false)
    {
        setcookie($name, $value, (int)$expire, $path, $domain, $secure, $httponly);
        $_COOKIE[ $name ] = $value;
    }

    /**
     * get() lets you retrieve the value of a cookie.
     *
     * @param mixed $name
     *
     * @return null
     */
    private function _getCookie ($name)
    {
        return isset($_COOKIE[ $name ]) ? $_COOKIE[ $name ] : null;
    }

    /**
     * @return bool|mixed
     */
    private function getCountryMap ()
    {
        // Get country map
        if ( !isset($this->countryMap) ) {
            $this->countryMap = $this->getSetting('countryMap');
        }
        if ( empty($this->countryMap) ) {
            return false;
        }

        return $this->countryMap;
    }

    /**
     * @return mixed
     */
    private function getOverrideLocaleParam ()
    {
        $overrideLocaleParam = $this->getSetting('overrideLocaleParam');

        return $overrideLocaleParam;
    }

    /**
     * @return int|null|string
     */
    private function getCountryCode ()
    {
        $currentLocale = craft()->locale->id;
        $countryCode   = null;
        // Get country map
        $countryMap = $this->getCountryMap();
        // Get selected country from GET
        $overrideLocaleParam = $this->getOverrideLocaleParam();
        $overrideLocale      = craft()->request->getParam($overrideLocaleParam);
        if ( $overrideLocale ) {
            // The selected country could be both key and value, so check for both
            foreach ($countryMap as $countryCode => $locale) {
                if ( $locale === $currentLocale ) {
                    // Override if parameter is in there
                    $this->setCountryCookie($countryCode);
                }
            }
        }
        // Get selected country from cookie
        $countryCode = $this->getCountryCookie();
        // Still no code? Geo lookup it is.
        if ( !$countryCode ) {
            $countryCode = $this->getCountryFromIpAddress();
            $this->setCountryCookie($countryCode);
        }

        return $countryCode;
    }

    /**
     * @param $countryCode
     *
     * @return bool|mixed|null
     */
    private function getCountryLocale ($countryCode)
    {
        // Get country map
        $countryMap = $this->getCountryMap();
        // Get country locale
        if ( isset($countryMap[ $countryCode ]) ) {
            $countryLocale = $countryMap[ $countryCode ];
            // If country locale is array, it's a list of regional languages
            if ( is_array($countryLocale) ) {
                $browseLanguageCodes = $this->getBrowserLanguages();
                foreach ($browseLanguageCodes as $blCode) {
                    if ( isset($countryLocale[ $blCode ]) ) {
                        return $countryLocale[ $blCode ];
                    }
                }
            }
            else {
                return $countryLocale;
            }
        }
        if ( isset($countryMap['*']) ) {
            $countryLocale = $countryMap['*'];
        }
        else {
            $countryLocale = null;
        }
        // At this point, whatever
        if ( !$countryLocale ) {
            return false;
        }

        return $countryLocale;
    }

    /**
     * @param null $countryLocale
     *
     * @return bool|mixed|null|string
     */
    private function getRedirectUrl ($countryLocale = null)
    {
        // First check if the countryLocale is actually a arbitrary URL
        if ( $url = filter_var($countryLocale, FILTER_VALIDATE_URL) ) {
            $this->removeCountryCookie();

            return $url;
        }
        $siteUrl = Craft::$app->config->general->siteUrl;
        // Don't redirect if there's no site URL defined for this locale, or if the country locale matches the current locale
        $localeSiteUrl = isset($siteUrl[ $countryLocale ]) ? $siteUrl[ $countryLocale ] : null;
        $localeSiteUrl = $countryLocale != craft()->locale->id ? $localeSiteUrl : null;
        if ( !$localeSiteUrl ) {
            return false;
        }
        // Try to get the matched element's local URL
        $element = craft()->urlManager->getMatchedElement();
        if ( $element && isset($element->url) && $element->url ) {
            $localeElement = craft()->elements->getCriteria($element->elementType, [
                'id'     => $element->id,
                'locale' => $countryLocale,
            ])->first();
            if ( $localeElement && isset($localeElement->url) && $localeElement->url ) {
                $url = $this->appendRedirectedParamToUrl($localeElement->url);

                return $url;
            }
        }
        $url = $this->appendRedirectedParamToUrl($localeSiteUrl);

        return $url;
    }

    /**
     * @return mixed
     */
    public function getBrowserLanguages ()
    {
        return Craft::$app->request->getAcceptableLanguages();
    }
}
