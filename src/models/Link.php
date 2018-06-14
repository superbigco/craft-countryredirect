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
class Link extends Model
{
    // Public Properties
    // =========================================================================

    public $siteHandle = '';
    public $siteName   = '';
    public $url        = '';
    public $catchAll   = false;

    // Public Methods
    // =========================================================================

    /**
     * Use the ID as the string representation of siteHandle.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->siteHandle;
    }

    public function getLink($options = [])
    {
        $options = array_merge(['title' => $this->getName()], $options);
        $parts   = [
            '<a href="' . $this->url . '"',
        ];

        if (isset($options['class'])) {
            $parts[] = ' class="' . $options['class'] . '"';
        }

        $parts[] = '>';
        $parts[] = $options['title'];
        $parts[] = '</a>';

        return Template::raw(implode('', $parts));
    }

    public function getName()
    {
        return $this->siteName;
    }

    public function getId()
    {
        return $this->siteHandle;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['siteHandle', 'string'],
            ['siteName', 'string'],
            ['url', 'string'],
        ];
    }
}
