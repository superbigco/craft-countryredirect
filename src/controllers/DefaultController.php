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
    protected $allowAnonymous = ['info'];

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

    public function actionClearLogs()
    {
        Craft::$app->getSession()->setNotice(Craft::t('country-redirect', 'Cleared logs'));

        CountryRedirect::$plugin->log->clearLogs();

        return $this->redirect('utilities/country-redirect-log-utility');
    }

    public function actionInfo()
    {
        $currentUrl        = Craft::$app->getRequest()->getParam('currentUrl');
        $currentSiteHandle = Craft::$app->getRequest()->getParam('currentSiteHandle');
        $bannerModel       = CountryRedirect::$plugin->countryRedirectService->getBanner($currentUrl, $currentSiteHandle);

        if ($bannerModel) {
            $banner         = $bannerModel->toArray();
            $banner['text'] = $bannerModel->getTextRaw();
        }

        return $this->asJson([
            'info'   => CountryRedirect::$plugin->countryRedirectService->getInfo(),
            'banner' => $banner ?? null,
        ]);
    }
}
