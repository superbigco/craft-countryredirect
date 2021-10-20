<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

/**
 * Country Redirect config.php
 *
 * This file exists only as a template for the Country Redirect settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'country-redirect.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    '*' => [
        /*
         * Enable for all users
         */
        //'enabled'             => true,
        /*
         * Or specifically logged in/anon users
         */
        'enabled'             => [
            'loggedIn'  => true,
            'anonymous' => true,
        ],
        /*
         * MaxMind.com License Key
         */
        'licenseKey' => null,
        /*
         * Don't redirect bots/crawlers like GoogleBot, Bing etc.
         */
        'ignoreBots'          => true,
        /*
         * Add any special URL segments you want to ignore
         * Eg. 'ignoreSegments' => [
         *     'this/page/only/exists/in/one/locale',
         *     'global-page'
         *     'fr/'
         * ],
         */
        'ignoreSegments'      => [],
        /*
         * Cookie name
         */
        'cookieName'          => 'countryRedirect',
        /*
         * The URL parameter that let a user manually select which locale they want to see
         */
        'overrideLocaleParam' => 'selected-locale',
        /*
         * The URL parameter that indicates that a user was redirected
         */
        'redirectedParam'     => 'redirected',
        /*
        * The value of query params
        */
        'queryParamsValue' => '1',
        /*
         * Map a country's two-letter ISO code to a Craft Site Handle, and/or define a catch-all with a * asterix
         * Here is a list of ISO country codes: http://www.nationsonline.org/oneworld/country_code_list.htm
         * Example:
         * 'countryMap'       => [
         *   'FR' => 'frenchSite',
         *   'DK' => 'danishSite',
         *   // You can also send visitors to an arbitrary URL
         *   'DE' => 'http://google.de',
         *   '*' => 'default',
         * ]
         *
         * If you within a country have different regional languages, you can map the different languages to locales.
         * Take Switzerland, with German, French, Italian and Romansh, as an example:
         *
         * 'countryMap'       => [
         *   'CH' => [
         *     'fr' => 'frenchSite',
         *     'de' => 'germanSite',
         *   ],
         *   '*' => 'default',
         * ]
         *
         * This works by checking the Accept-Language header of the browser.
         *
         */
        'countryMap'          => [],
        /*
         * If you want to show a banner that prompts visitors to their matching locale instead of redirecting them,
         * you can define these here.
         *
         * The key here is the Craft locale id, not the country code. The variables {countryName} and {url} will
         * be replaced.
         *
         * 'banners' => [
         *   'en_us' => 'It looks like your visiting from {countryName}. Do you <a href="{url}">want to visited the international site?</a>'
         * ],
         */
        'banners'             => [],

        /*
         * Override the detected IP - useful for testing, or when no IP address can be detected
         */
        'overrideIp'          => null,
    ],
];