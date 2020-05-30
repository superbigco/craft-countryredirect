<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\console\controllers;

use superbig\countryredirect\CountryRedirect;

use Craft;
use superbig\countryredirect\helpers\RedirectHelper;
use superbig\countryredirect\models\RedirectRequest;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Preview Command
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class PreviewController extends Controller
{
    // Public Methods
    // =========================================================================

    public $uri;

    public function options($actionID)
    {
        return ['uri'];
    }

    /**
     * Update Maxmind geolocation database
     *
     * @param string $ipAddress
     *
     * @return mixed
     */
    public function actionPreview(string $ipAddress = null)
    {
        $service = CountryRedirect::$plugin->getService();

        $this->stdout("> Previewing" . PHP_EOL, Console::FG_GREEN);
        $headers = [
            'Country',
            'City',
            'In EU',
        ];
        $request = new RedirectRequest([
            'ipAddress'         => $ipAddress,
            'acceptedLanguages' => 'nb-NO,nb;q=0.9,no;q=0.8,nn;q=0.7,en-US;q=0.6,en;q=0.5',
        ]);

        $newMapped = RedirectHelper::mapLegacyCountryToRedirectMap(CountryRedirect::$plugin->getSettings()->countryMap);

        if ($this->uri) {
            $request->currentUri = $this->uri;
        }

        $service->maybeRedirect($request);


        $this->stdout("> Finished preview" . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
