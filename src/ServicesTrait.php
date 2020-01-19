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

use superbig\countryredirect\services\DatabaseService;
use superbig\countryredirect\services\LogService;
use superbig\countryredirect\services\CountryRedirectService;

/**
 * Class CountryRedirect
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property  CountryRedirectService $countryRedirectService
 * @property  DatabaseService        $database
 * @property   LogService            $log
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
     * @return CountryRedirectService
     */
    public function getService()
    {
        return $this->get('countryRedirectService');
    }
}