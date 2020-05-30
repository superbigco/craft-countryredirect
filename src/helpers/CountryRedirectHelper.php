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
use yii\base\InvalidConfigException;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.8
 *
 */
class CountryRedirectHelper
{
    private static $_sites;

    public static function getPathInfo($url = null): string
    {
        $request  = Craft::$app->getRequest();
        $pathInfo = $url ?? $request->getUrl();

        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = urldecode($pathInfo);

        // try to encode in UTF8 if not so
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (!preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $pathInfo)
        ) {
            $pathInfo = utf8_encode($pathInfo);
        }

        $scriptUrl = $request->getScriptUrl();
        $baseUrl   = $request->getBaseUrl();
        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        }
        elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        }
        elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        }
        else {
            throw new InvalidConfigException('Unable to determine the path info of the current request.');
        }

        if (substr($pathInfo, 0, 1) === '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        return (string)$pathInfo;
    }

    /**
     * @param bool $includeOverrideParam
     *
     * @return array
     */
    public static function getSiteLinks($includeOverrideParam = true): array
    {
        if (!self::$_sites) {
            $allSites = Craft::$app->getSites()->getAllSites();

            foreach ($allSites as $site) {
                $url = self::getCurrentLinkForSite($site);
                $r[] = ['site' => $site, 'url' => $url];
            }

            self::$_sites = array_map(function(Site $site) {
                $url = self::getCurrentLinkForSite($site);

                return [
                    'site' => $site,
                    'url'  => $url,
                ];
            }, $allSites);
        }

        return self::$_sites;
    }

    /**
     * @param Site $site
     * @param bool $elementMatchOnly
     *
     * @return bool|string
     */
    public static function getCurrentLinkForSite(Site $site, $elementMatchOnly = false)
    {
        $url = $elementMatchOnly ? null : $site->baseUrl;

        /** @var Element $currentElement */
        $currentElement = Craft::$app->getUrlManager()->getMatchedElement();

        if ($currentElementUrl = $currentElement->url ?? null) {
            $targetElement = Craft::$app->getElements()->getElementById($currentElement->id, \get_class($currentElement), $site->id);

            if ($targetElementUrl = $targetElement->url ?? null) {
                $url = $targetElement->url;
            }
        }

        return \Yii::getAlias($url);
    }

    /**
     * @return bool
     * @throws \craft\errors\MissingComponentException
     */
    public static function wasRedirected(): bool
    {
        $param = CountryRedirect::$plugin->getSettings()->redirectedParam;

        return Craft::$app->getSession()->hasFlash($param) || Craft::$app->getRequest()->getParam($param);
    }

    /**
     * @return mixed
     */
    public static function wasOverridden()
    {
        $param = CountryRedirect::$plugin->getSettings()->overrideLocaleParam;

        return Craft::$app->getRequest()->getParam($param);
    }

    public static function isAcceptedLanguage($criteriaVal, $minimumAcceptLanguageQuality)
    {
    }

    public static function normalizeCountry(string $country)
    {
        return strtolower($country);
    }

    public static function normalize(string $value)
    {
        return strtolower($value);
    }
}
