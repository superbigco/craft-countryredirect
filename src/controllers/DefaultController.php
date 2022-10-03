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

use Craft;

use craft\web\Controller;
use superbig\countryredirect\CountryRedirect;

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
    protected array|int|bool $allowAnonymous = ['info'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/countryRedirect
     */
    public function actionDownloadDatabase(): \yii\web\Response
    {
        $response = CountryRedirect::$plugin->getDatabase()->downloadDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }

    public function actionUnpackDatabase(): \yii\web\Response
    {
        $response = CountryRedirect::$plugin->getDatabase()->unpackDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }

    public function actionUpdateDatabase(): \yii\web\Response
    {
        $response = CountryRedirect::$plugin->getDatabase()->downloadDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        $response = CountryRedirect::$plugin->getDatabase()->unpackDatabase();

        if (isset($response['error'])) {
            return $this->asJson($response['error']);
        }

        return $this->asJson($response);
    }

    public function actionClearLogs(): \yii\web\Response
    {
        Craft::$app->getSession()->setNotice(Craft::t('country-redirect', 'Cleared logs'));

        CountryRedirect::$plugin->getLog()->clearLogs();

        return $this->redirect('utilities/country-redirect');
    }

    public function actionInfo(): \yii\web\Response
    {
        $currentUrl = Craft::$app->getRequest()->getParam('currentUrl');
        $currentSiteHandle = Craft::$app->getRequest()->getParam('currentSiteHandle');
        $bannerModel = CountryRedirect::$plugin->getService()->getBanner($currentUrl, $currentSiteHandle);

        if ($bannerModel) {
            $banner = $bannerModel->toArray();
            $banner['text'] = $bannerModel->getTextRaw();
        }

        return $this->asJson([
            'info' => CountryRedirect::$plugin->getService()->getInfo(),
            'banner' => $banner ?? null,
        ]);
    }
}
