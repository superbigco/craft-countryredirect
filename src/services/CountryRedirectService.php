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

use craft\base\Element;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Response;
use GeoIp2\Database\Reader;
use GuzzleHttp\Client;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\helpers\CountryRedirectHelper;
use superbig\countryredirect\models\Banner;
use superbig\countryredirect\models\Link;
use superbig\countryredirect\models\LogModel;
use superbig\countryredirect\models\Settings;
use yii\base\InvalidConfigException;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class CountryRedirectService extends Component
{
    // Public Methods
    // =========================================================================

    /** @var Settings $config */
    protected $config;
    protected $countryMap;
    private   $_matchedElement;
    private   $_matchedElementRoute;

    public function init()
    {
        parent::init();

        $this->config = CountryRedirect::$plugin->getSettings();
    }

    /**
     * @return Response|bool
     */
    public function maybeRedirect()
    {
        // Get the site URL config setting
        $enabled        = $this->config->enabled;
        $ignoreSegments = $this->config->ignoreSegments;

        if ($this->config->ignoreBots) {
            $crawlerDetect = new CrawlerDetect;

            if ($crawlerDetect->isCrawler()) {
                return false;
            }
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (strpos($path, 'country-redirect')) {
            return false;
        }

        if ($this->wasRedirectedFromBanner()) {
            $this->setBannerCookie();
        }

        if (!empty($ignoreSegments)) {
            foreach ($ignoreSegments as $segment) {
                if (strpos($path, $segment)) {
                    return false;
                }
            }
        }

        // Don't redirect if the plugin is soft disabled
        if (!$enabled) {
            return false;
        }

        // Depending on enabled setting, do some extra checks
        if (!is_bool($enabled) && is_array($enabled)) {
            $user = Craft::$app->getUser()->getIdentity();

            // Should we check logged in users?
            // TODO: Add a setting to redirect for specific user groups?
            if (isset($enabled['loggedIn'])) {
                if ($user && !$enabled['loggedIn']) {
                    return false;
                }
            }

            // Should we check anon users?
            if (isset($enabled['anonymous'])) {
                if (!$user && !$enabled['anonymous']) {
                    return false;
                }
            }
        }

        $countryCode   = $this->getCountryCode();
        $countryLocale = $this->getSiteHandle($countryCode);

        if ($url = $this->getRedirectUrl($countryLocale)) {
            // Set redirected flash/query param
            if ($redirectParam = $this->config->redirectedParam) {
                Craft::$app->getSession()->setFlash($redirectParam, true);
            }

            if ($this->config->enableLogging) {
                CountryRedirect::$plugin->log->logRedirect($url);
            }

            return Craft::$app->getResponse()->redirect($url);
        }
    }

    /**
     * @return Link[]
     */
    public function getLinks()
    {
        // Get locales
        $sites               = Craft::$app->getSites()->getAllSites();
        $links               = [];
        $overrideLocaleParam = $this->getOverrideLocaleParam();

        foreach ($sites as $site) {
            /** @var Site $site */
            $link    = new Link();
            $siteUrl = $site->baseUrl;

            $link->siteName   = $site->name;
            $link->siteHandle = $site->handle;
            $link->url        = rtrim($siteUrl, '?') . '?' . http_build_query([$overrideLocaleParam => '✓']);
            $links[]          = $link;
        }

        return $links;
    }

    public function wasRedirected(): bool
    {
        $param = $this->config->redirectedParam;

        return Craft::$app->getSession()->hasFlash($param) || Craft::$app->getRequest()->getParam($param);
    }

    public function wasRedirectedFromBanner(): bool
    {
        $param = $this->config->bannerParam;

        return Craft::$app->getSession()->hasFlash($param) || Craft::$app->getRequest()->getParam($param);
    }

    public function wasOverridden()
    {
        $param = $this->config->overrideLocaleParam;

        return Craft::$app->getRequest()->getParam($param);
    }

    public function getBanner($currentUrl = null, $currentSiteHandle = null)
    {
        $banner      = null;
        $banners     = $this->config->banners;
        $countryCode = $this->getCountryCode();
        $siteHandle  = $this->getSiteHandle($countryCode);
        $info        = $this->getInfo();
        $redirectUrl = $this->getRedirectUrl($siteHandle, $currentUrl, $currentSiteHandle);
        $site        = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        $bannersLc   = array_change_key_case($banners);

        if ($matchesCountry = array_key_exists(strtolower($countryCode), $bannersLc)) {
            $banner = $bannersLc[ strtolower($countryCode) ] ?? null;
        }
        elseif ($matchesSiteHandle = isset($banners[ $siteHandle ])) {
            $banner = $banners[ $siteHandle ] ?? null;
        }

        if ($redirectUrl && $banner && !$this->getBannerCookie()) {
            return new Banner([
                'text'        => $banner,
                'url'         => $this->appendBannerParamToUrl($redirectUrl),
                'countryName' => $info ? $info->name : null,
                'siteHandle'  => $siteHandle,
                'siteName'    => $site->name ?? null,
            ]);
        }
    }

    public function getInfo()
    {
        $ip   = $this->getIpAddress();
        $info = $this->getInfoFromIp($ip);

        return $info ? $info->country : null;
    }

    public function getCountryFromIpAddress()
    {
        $ip       = $this->getIpAddress();
        $cacheKey = 'countryRedirect-ip-' . $ip;

        if ($ip == '::1' || $ip == '127.0.0.1' || !$ip) {
            return '*';
        }

        // Check cache first
        if ($cacheRecord = Craft::$app->cache->get($cacheKey)) {
            return $cacheRecord;
        }

        $info = $this->getInfoFromIp($ip);

        if ($info) {
            return $info->country->isoCode;
        }

        return '*';
    }

    public function getInfoFromIp($ip = null)
    {
        if (!$ip) {
            return null;
        }

        $ip = $this->getIpAddress();

        if ($ip == '::1' || $ip == '127.0.0.1') {
            return null;
        }

        $cacheKey = 'countryRedirect-info-' . $ip;

        // Check cache first
        if ($cacheRecord = Craft::$app->cache->get($cacheKey)) {
            return $cacheRecord;
        }

        try {
            $record = CountryRedirect::$plugin->database->getCountryFromIp($ip);

            Craft::$app->cache->set($cacheKey, $record);

            return $record;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return null|string
     */
    public function getIpAddress()
    {
        $ip = Craft::$app->getRequest()->getUserIP();

        if (!empty($this->config->overrideIp)) {
            $ip = $this->config->overrideIp;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $ip;
    }

    /**
     * @param null $url
     *
     * @return null|string
     */
    private function appendRedirectedParamToUrl($url = null)
    {
        $url   = Craft::parseEnv($url);
        $param = $this->config->redirectedParam;

        if (!$param) {
            return $url;
        }

        $query     = $param . '=✓';
        $parsedUrl = parse_url($url);

        if (empty($parsedUrl['path'])) {
            $url .= '/';
        }

        $separator = empty($parsedUrl['query']) ? '?' : '&';

        return $url . $separator . $query;
    }

    /*
    *   Cookies
    *
    */

    /**
     * @param null $url
     *
     * @return null|string
     */
    private function appendBannerParamToUrl($url = null)
    {
        $url   = Craft::parseEnv($url);
        $param = $this->config->bannerParam;

        if (!$param) {
            return $url;
        }

        $query     = $param . '=✓';
        $parsedUrl = parse_url($url);

        if (empty($parsedUrl['path'])) {
            $url .= '/';
        }

        $separator = empty($parsedUrl['query']) ? '?' : '&';

        return $url . $separator . $query;
    }

    /**
     * @return null
     */
    protected function getCountryCookie()
    {
        return $this->_getCookie($this->config->cookieName);
    }

    /**
     * @return null
     */
    protected function getBannerCookie()
    {
        return $this->_getCookie($this->config->cookieNameBanner);
    }

    /**
     * @return string
     */
    public function getBannerCookieName()
    {
        return $this->config->cookieNameBanner;
    }

    /**
     * @return mixed
     */
    public function getOverrideLocaleParam()
    {
        return $this->config->overrideLocaleParam;
    }

    /**
     * @return mixed
     */
    public function getBannerParam()
    {
        return $this->config->bannerParam;
    }

    /**
     * @param null $countryCode
     *
     * @return null
     */
    protected function setCountryCookie($countryCode = null)
    {
        $time = time() + 60 * 60 * 24 * 30;
        $this->_setCookie($this->config->cookieName, $countryCode, $time);

        return $this->getCountryCookie();
    }

    /**
     * @return null
     */
    protected function setBannerCookie()
    {
        $time = time() + 60 * 60 * 24 * 30;
        $this->_setCookie($this->config->cookieNameBanner, true, $time);

        return $this->getBannerCookie();
    }

    /**
     *
     */
    public function removeCountryCookie()
    {
        $this->_setCookie($this->config->cookieName, null, -1);
    }

    public function getMatchedElement($url = null)
    {
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(__METHOD__ . "() was called before the application was fully initialized.\n" .
                "Stack trace:\n" . App::backtrace(), __METHOD__);
        }

        if ($this->_matchedElement !== null) {
            return $this->_matchedElement;
        }

        $request = Craft::$app->getRequest();

        if (!$request->getIsSiteRequest()) {
            return $this->_matchedElement = false;
        }

        $this->_getMatchedElementRoute($url ?? $request->getPathInfo());

        return $this->_matchedElement;
    }

    /**
     * Attempts to match a path with an element in the database.
     *
     * @param string $path
     *
     * @return mixed
     */
    private function _getMatchedElementRoute(string $path)
    {
        if ($this->_matchedElementRoute !== null) {
            return $this->_matchedElementRoute;
        }

        $this->_matchedElement      = false;
        $this->_matchedElementRoute = false;


        if (Craft::$app->getIsInstalled() && Craft::$app->getRequest()->getIsSiteRequest()) {
            $path = rtrim(ltrim(parse_url($path, PHP_URL_PATH), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

            /** @var Element $element */
            /** @noinspection PhpUnhandledExceptionInspection */
            $element = Craft::$app->getElements()->getElementByUri($path, Craft::$app->getSites()->getCurrentSite()->id, true);

            if ($element) {
                $route = $element->getRoute();

                if ($route) {
                    if (is_string($route)) {
                        $route = [$route, []];
                    }

                    $this->_matchedElement      = $element;
                    $this->_matchedElementRoute = $route;
                }
            }
        }

        if (YII_DEBUG) {
            Craft::debug([
                'rule'   => 'Element URI: ' . $path,
                'match'  => isset($element, $route),
                'parent' => null,
            ], __METHOD__);
        }

        return $this->_matchedElementRoute;
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
    private function _setCookie($name = "", $value = "", $expire = 0, $path = "/", $domain = "", $secure = false, $httponly = false)
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
    private function _getCookie($name)
    {
        return isset($_COOKIE[ $name ]) ? $_COOKIE[ $name ] : null;
    }

    /**
     * @return bool|mixed
     */
    private function getCountryMap()
    {
        // Get country map
        if (!isset($this->countryMap)) {
            $this->countryMap = $this->config->countryMap;
        }

        if (empty($this->countryMap)) {
            return false;
        }

        return $this->countryMap;
    }

    /**
     * @return int|null|string
     */
    private function getCountryCode()
    {
        $currentLocale = Craft::$app->getSites()->currentSite->handle;
        $countryCode   = null;

        // Get country map
        $countryMap = $this->getCountryMap();

        // Get selected country from GET
        $overrideLocaleParam = $this->getOverrideLocaleParam();
        $overrideLocale      = Craft::$app->getRequest()->getParam($overrideLocaleParam);

        if ($overrideLocale) {
            // The selected country could be both key and value, so check for both
            foreach ($countryMap as $countryCode => $locale) {
                if ($locale === $currentLocale) {
                    // Override if parameter is in there
                    $this->setCountryCookie($countryCode);
                }
            }
        }
        // Get selected country from cookie
        $countryCode = $this->getCountryCookie();

        // Still no code? Geo lookup it is.
        if (!$countryCode) {
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
    private function getSiteHandle($countryCode)
    {
        // Get country map
        $countryMap = $this->getCountryMap();

        // Get country locale
        if (isset($countryMap[ $countryCode ])) {
            $countryLocale = $countryMap[ $countryCode ];

            // If country locale is array, it's a list of regional languages
            if (is_array($countryLocale)) {
                $browseLanguageCodes = $this->getBrowserLanguages();

                foreach ($browseLanguageCodes as $blCode) {
                    if (isset($countryLocale[ $blCode ])) {
                        return $countryLocale[ $blCode ];
                    }
                }
            }
            else {
                return $countryLocale;
            }
        }

        if (isset($countryMap['*'])) {
            $countryLocale = $countryMap['*'];
        }
        else {
            $countryLocale = null;
        }

        // At this point, whatever
        if (!$countryLocale) {
            return false;
        }

        return $countryLocale;
    }

    /**
     * @param null        $siteHandle
     * @param null|string $currentUrl
     *
     * @return bool|mixed|null|string
     */
    private function getRedirectUrl($siteHandle = null, $currentUrl = null, $currentSiteHandle = null)
    {
        // First check if the countryLocale is actually a arbitrary URL
        if ($url = filter_var($siteHandle, FILTER_VALIDATE_URL)) {
            $this->removeCountryCookie();

            return $url;
        }

        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $currentSiteHandle = $currentSiteHandle ?? Craft::$app->getSites()->currentSite->handle;

        // Don't redirect if there's no site URL defined for this locale, or if the country locale matches the current locale
        $siteUrl = $site->baseUrl ?? null;
        $siteUrl = $siteHandle !== $currentSiteHandle ? $siteUrl : null;

        if (!$siteUrl) {
            return false;
        }

        // Try to get the matched element's local URL
        $element = $this->getMatchedElement($currentUrl);

        /** @var Element $element */
        if ($element && isset($element->url) && $element->url) {
            $localeElement = Craft::$app->getElements()->getElementById($element->id, get_class($element), $site->id);

            if ($localeElement && isset($localeElement->url) && $localeElement->url) {
                $url = $this->appendRedirectedParamToUrl($localeElement->url);

                return $url;
            }
        }

        return $this->appendRedirectedParamToUrl($siteUrl);
    }

    /**
     * @return mixed
     */
    public function getBrowserLanguages()
    {
        return Craft::$app->request->getAcceptableLanguages();
    }
}
