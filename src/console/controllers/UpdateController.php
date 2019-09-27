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
            'downloadDatabase' => 'Downloaded database',
            'unpackDatabase'   => 'Unpacked database',
        ];

        $currentStep = 1;
        foreach ($steps as $key => $step) {
            if (method_exists($service, $key)) {
                $response = $service->$key();

                if (isset($response['error'])) {
                    $this->stdout('Error: ' . $response['error'] . PHP_EOL, Console::FG_RED);

                    return ExitCode::UNSPECIFIED_ERROR;
                }

                Console::updateProgress($currentStep, 2, "{$step}");
            }

            $currentStep++;
        }

        Console::endProgress();
        $this->stdout("Finished update" . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
