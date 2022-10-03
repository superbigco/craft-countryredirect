<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a site based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\models;

use Craft;

use craft\base\Model;
use craft\helpers\Template;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Banner extends Model implements \Stringable
{
    public string $text = '';

    public string|null $url = null;

    public string $countryName = '';

    public string $siteHandle = '';

    public string $siteName = '';

    public function __toString(): string
    {
        return $this->text;
    }

    public function getName(): string
    {
        return $this->countryName;
    }

    public function getId(): string
    {
        return $this->siteHandle;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getText(): \Twig\Markup
    {
        return Template::raw($this->getTextRaw());
    }

    public function getTextRaw(): string
    {
        return Craft::t('country-redirect', $this->text, ['countryName' => $this->countryName, 'url' => $this->url]);
    }
}
