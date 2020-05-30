<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect;

use superbig\countryredirect\services\BannerService;
use superbig\countryredirect\services\DatabaseService;
use superbig\countryredirect\services\LogService;
use superbig\countryredirect\services\NavService;
use superbig\countryredirect\services\RedirectService;

/**
 * Class CountryRedirect
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property  RedirectService $countryRedirectService
 * @property  DatabaseService $database
 * @property  BannerService   $banner
 * @property   LogService     $log
 */
trait ServicesTrait
{
    /**
     * @return LogService
     */
    public function getLog()
    {
        return $this->get('log');
    }

    /**
     * @return DatabaseService
     */
    public function getDatabase()
    {
        return $this->get('database');
    }

    /**
     * @return BannerService
     */
    public function getBanner()
    {
        return $this->get('banner');
    }

    /**
     * @return NavService
     */
    public function getNav()
    {
        return $this->get('nav');
    }

    /**
     * @return RedirectService
     */
    public function getService()
    {
        return $this->get('countryRedirectService');
    }
}