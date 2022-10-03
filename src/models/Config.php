<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\models;

use craft\base\Model;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Config extends Model
{
    public string $overrideLocaleParam = 'selected-locale';

    public string $redirectedParam = 'redirected';

    public string $cookieName = 'countryRedirect';

    public string $cookieNameBanner = 'countryRedirectBanner';

    public bool $ignoreBots = true;

    public bool $enabled = false;

    public array $countryMap = [];

    public array $ignoreSegments = [];

    public array $banners = [];

    public ?string $ipAddress = null;

    /**
     * @return array<int, array<string[]|string>>
     */
    public function rules(): array
    {
        return [
            [['overrideLocaleParam', 'redirectedParam', 'cookieName', 'cookieNameBanner'], 'string'],
        ];
    }
}
