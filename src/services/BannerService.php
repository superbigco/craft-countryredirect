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

use Craft;
use craft\base\Component;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\Banner;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.1.0
 *
 */
class BannerService extends Component
{
    public function getBanner($currentUrl = null, $currentSiteHandle = null)
    {
        $banner          = null;
        $banners         = $this->config->banners;
        $redirectService = CountryRedirect::$plugin->getService();
        $countryCode     = $redirectService->getCountryCode();
        $siteHandle      = $redirectService->getSiteHandle($countryCode);
        $info            = $redirectService->getInfo();
        $redirectUrl     = $redirectService->getRedirectUrl($siteHandle, $currentUrl, $currentSiteHandle);
        $site            = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        $bannersLc       = array_change_key_case($banners);

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