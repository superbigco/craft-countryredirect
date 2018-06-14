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
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Default Command
 *
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class UpdateController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Update Maxmind geolocation database
     *
     * @return mixed
     */
    public function actionUpdate()
    {
        $service = CountryRedirect::$plugin->database;
        $steps   = [
            'downloadDatabase',
            'unpackDatabase',
        ];

        foreach ($steps as $step) {
            if (method_exists($service, $step)) {
                $response = $service->$step();

                if (isset($response['error'])) {
                    $this->stdout('Error: ' . $response['error'] . PHP_EOL);

                    return ExitCode::UNSPECIFIED_ERROR;
                }

                $this->stdout("Step {$step} was successful" . PHP_EOL);
            }
        }

        return ExitCode::OK;
    }
}
