<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\helpers;

use Craft;
use craft\base\Component;
use craft\models\Site;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\IgnoreSegment;
use superbig\countryredirect\models\Redirect;
use superbig\countryredirect\models\RedirectRequest;
use yii\base\InvalidConfigException;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.8
 *
 */
class RedirectHelper
{
    public static function isIgnoredSegment(RedirectRequest $request)
    {
        //if (!Craft::$app->getRequest()->getIsCpRequest() || !Craft::$app->getRequest()->getIsCpRequest()) {
            $currentUri     = Craft::$app->getRequest()->resolveRequestUri();
            $path           = parse_url($currentUri, PHP_URL_PATH);
            $ignoreSegments = CountryRedirect::$plugin->getSettings()->getIgnoredSegments();

            $matchingSegments = array_filter($ignoreSegments, function(IgnoreSegment $ignoreSegment) use ($path) {
                return $ignoreSegment->match($path);
            });

            if (!empty($matchingSegments)) {
                $request->shouldRedirect = false;
            }
        // }
    }

    public static function mapLegacyCountryToRedirectMap(array $map = [])
    {
        $newMap = [];

        foreach ($map as $countryCode => $siteHandleOrRegions) {
            if (is_array($siteHandleOrRegions)) {
                foreach ($siteHandleOrRegions as $languageCode => $siteHandle) {
                    $newMap[] = new Redirect([
                        'country'          => [CountryRedirectHelper::normalize($countryCode)],
                        'language'         => [CountryRedirectHelper::normalize($languageCode)],
                        'targetSiteHandle' => $siteHandle,
                    ]);
                }
            }
            else {
                $newMap[] = new Redirect([
                    'country'          => [CountryRedirectHelper::normalize($countryCode)],
                    'targetSiteHandle' => $siteHandleOrRegions,
                ]);
            }
        }

        return $newMap;
    }
}
