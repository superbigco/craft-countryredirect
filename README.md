# Country Redirect plugin for Craft CMS 3.x

Easily redirect visitors to a locale based on their country of origin

![Screenshot](resources/icon.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require sjelfull/country-redirect

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Country Redirect.

## Country Redirect Overview

@TODO

## Using the plugin

Before you start using the plugin, you have to do 3 things:

1. Download a updated country database database from MaxMind through the plugin settings

2. Copy the configuration file `config.php` as `country-redirect.php` into the Craft `config` directory, usually `config/`

3. Modify the configuration file to match preferences, and make sure you setup the `countryMap` setting to match your site handles.

## Configuration

```php
<?php
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
         * Enable logging
         */
        'enableLogging'          => true,

        /*
         * Don't redirect bots/crawlers like GoogleBot, Bing etc.
         */
        'ignoreBots'          => true,

        /*
         * Add any special URL segments you want to ignore
         * Eg. 'ignoreSegments' => [
         *     'this/page/only/exists/in/one/locale',
         *     'global-page'
         * ],
         */
        'ignoreSegments'      => [ ],

        /*
         * Cookie name
         */
        'cookieName'          => 'countryRedirect',

        /*
         * The URL parameter that let a user manually select which locale they want to see
         */
        'overrideLocaleParam' => 'selected-locale',

        /*
         * The URL parameter that indicates that a user was redirect
         */
        'redirectedParam' => 'redirected',

        /*
         * Map a countrys two-letter ISO code to a Craft Site Handle, and/or define a catch-all with a * asterix
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
        'countryMap'          => [ ],

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
        'banners'             => [ ],

        /*
         * Override the detected IP - useful for testing, or when no IP address can be detected
         */
        'overrideIp'          => null,
    ],
];
```

For a list of the ISO country codes, [check out this overview](http://www.nationsonline.org/oneworld/country_code_list.htm)

### Navigation

```twig
<h1>Select country</h1>

{% set titles = {
	'en_us': 'Go to our US site',
	'fr': 'Go to our French site',
	'da': 'Go to our Danish site',
} %}
<nav class="nav">
	{% for locale in craft.countryRedirect.getLinks() %}
		{{locale.getLink({ title: titles[locale.getId()] })}}
	{% endfor %}
</nav>
```

### Banner prompts

If you want to show a banner that prompts visitors to their matching locale instead of redirecting them, you can define
the text for each locale with the `banners` setting.

You can then access the text like this:

```twig
{% set banner = craft.countryRedirect.getBanner() %}
{% if banner %}
    <div class="banner">
        <p>{{ banner.getText() }}</p>
    </div>
{% endif %}
```

## Testing

You can test it by using a free VPN account from [TunnelBear](https://www.tunnelbear.com/)

## Database

Geolocation data provided by [MaxMind](http://www.maxmind.com)

### Accuracy

[According to MaxMind](http://dev.maxmind.com/faq/how-accurate-are-the-geoip-databases/), their databases is 99.8% accurate on a country level.

Brought to you by [Superbig](https://superbig.co)
