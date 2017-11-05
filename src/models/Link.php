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
class Link extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $locale      = '';
    public $locale_name = '';
    public $url         = '';

    /**
     * @var boolean
     */
    public $catchAll = false;

    // Public Methods
    // =========================================================================

    /**
     * Use the ID as the string representation of locales.
     *
     * @return string
     */
    public function __toString ()
    {
        return $this->locale;
    }

    public function getLink ($options = [])
    {
        $defaultOptions = [
            'title' => $this->getName(),
        ];
        $options        = array_merge($defaultOptions, $options);
        $parts          = [
            '<a href="' . $this->url . '"',
        ];
        if ( isset($options['class']) ) {
            $parts[] = ' class="' . $options['class'] . '"';
        }
        $parts[] = '>';
        $parts[] = $options['title'];
        $parts[] = '</a>';

        return Template::raw(implode('', $parts));
    }

    public function getName ()
    {
        return $this->locale_name;
    }

    public function getId ()
    {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function rules ()
    {
        return [
            [ 'locale', 'string' ],
            [ 'locale_name', 'string' ],
            [ 'url', 'string' ],
            //[ 'someAttribute', 'default', 'value' => 'Some Default' ],
        ];
    }
}
