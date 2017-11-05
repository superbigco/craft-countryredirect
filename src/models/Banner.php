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

use craft\helpers\Template;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Banner extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $text = '';

    public $url = null;

    public $countryName = '';
    public $locale      = '';
    public $locale_name = '';

    // Public Methods
    // =========================================================================

    /**
     * Use the ID as the string representation of locales.
     *
     * @return string
     */
    public function __toString ()
    {
        return $this->text;
    }

    public function getName ()
    {
        return $this->countryName;
    }

    public function getId ()
    {
        return $this->locale;
    }

    public function getUrl ()
    {
        return $this->url;
    }

    /**
     * @return \Twig_Markup
     */
    public function getText ()
    {
        return Template::raw(Craft::t('country-redirect', $this->text, [ 'countryName' => $this->countryName, 'url' => $this->url, ]));
    }


    /**
     * @inheritdoc
     */
    public function rules ()
    {
        return [
            [ 'someAttribute', 'string' ],
            [ 'someAttribute', 'default', 'value' => 'Some Default' ],
        ];
    }
}
