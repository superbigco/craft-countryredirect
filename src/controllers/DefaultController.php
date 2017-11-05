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

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [ 'index', 'do-something' ];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/countryRedirect
     */
    public function actionDownloadDatabase ()
    {
        $response = CountryRedirect::$plugin->countryRedirectService->downloadDatabase();

        if ( isset($response['error']) ) {
            return $this->renderJSON($response['error']);
        }

        return $this->renderJSON($response);
    }

    public function actionUnpackDatabase ()
    {
        $response = CountryRedirect::$plugin->countryRedirectService->unpackDatabase();

        if ( isset($response['error']) ) {
            return $this->renderJSON($response['error']);
        }

        return $this->renderJSON($response);
    }

    public function actionUpdateDatabase ()
    {
        $response = CountryRedirect::$plugin->countryRedirectService->downloadDatabase();

        if ( isset($response['error']) ) {
            return $this->renderJSON($response['error']);
        }

        $response = CountryRedirect::$plugin->countryRedirectService->unpackDatabase();

        if ( isset($response['error']) ) {
            return $this->renderJSON($response['error']);
        }

        return $this->renderJSON($response);
    }

    /**
     * Return data to browser as JSON and end application.
     *
     * @param array $data
     */
    protected function renderJSON ($data)
    {
        header('Content-type: application/json');
        echo json_encode($data);

        return Craft::$app->end();
    }
}
