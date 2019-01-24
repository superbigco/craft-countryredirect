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

    public $text        = '';
    public $url         = null;
    public $countryName = '';
    public $siteHandle  = '';
    public $siteName    = '';

    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->text;
    }

    public function getName()
    {
        return $this->countryName;
    }

    public function getId()
    {
        return $this->siteHandle;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return \Twig_Markup
     */
    public function getText()
    {
        return Template::raw($this->getTextRaw());
    }

    public function getTextRaw()
    {
        return Craft::t('country-redirect', $this->text, ['countryName' => $this->countryName, 'url' => $this->url]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ];
    }
}
