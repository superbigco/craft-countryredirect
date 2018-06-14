<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\controllers;

use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\web\Controller;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/countryRedirect
     */
    public function actionDownloadDatabase()
    {
        $response = CountryRedirect::$plugin->database->downloadDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }

    public function actionUnpackDatabase()
    {
        $response = CountryRedirect::$plugin->database->unpackDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }

    public function actionUpdateDatabase()
    {
        $response = CountryRedirect::$plugin->database->downloadDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        $response = CountryRedirect::$plugin->database->unpackDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }
}
