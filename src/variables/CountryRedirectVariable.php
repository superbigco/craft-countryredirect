<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\variables;

use superbig\countryredirect\CountryRedirect;

use Craft;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class CountryRedirectVariable
{
    // Public Methods
    // =========================================================================

    public function getLinks()
    {
        return CountryRedirect::$plugin->countryRedirectService->getLinks();
    }

    public function redirected()
    {
        return CountryRedirect::$plugin->countryRedirectService->wasRedirected();
    }

    public function overridden()
    {
        return CountryRedirect::$plugin->countryRedirectService->wasOverridden();
    }

    public function info()
    {
        return CountryRedirect::$plugin->countryRedirectService->getInfo();
    }

    public function getBanner()
    {
        return CountryRedirect::$plugin->countryRedirectService->getBanner();
    }
}
