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
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Response;
use GeoIp2\Database\Reader;
use GuzzleHttp\Client;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\checks\BotCheck;
use superbig\countryredirect\checks\CheckInterface;
use superbig\countryredirect\checks\EnabledCheck;
use superbig\countryredirect\checks\IgnoredSegmentCheck;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\helpers\CountryRedirectHelper;
use superbig\countryredirect\helpers\RedirectHelper;
use superbig\countryredirect\models\Banner;
use superbig\countryredirect\models\Link;
use superbig\countryredirect\models\LogModel;
use superbig\countryredirect\models\RedirectRequest;
use superbig\countryredirect\models\Settings;
use yii\base\InvalidConfigException;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class NavService extends Component
{
    // Public Methods
    // =========================================================================

    /** @var Settings $config */
    protected $config;

    public function init()
    {
        parent::init();

        $this->config = CountryRedirect::$plugin->getSettings();
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
}
