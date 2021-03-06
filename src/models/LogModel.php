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
use superbig\countryredirect\records\LogRecord;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class LogModel extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $siteId;
    public $userId;
    public $ipAddress;
    public $userAgent;
    public $url;
    public $city;
    public $country;
    public $snapshot;
    public $dateCreated;

    /**
     * @param LogRecord $record
     *
     * @return LogModel
     */
    public static function createFromRecord(LogRecord $record)
    {
        $model = new static();
        $model->setAttributes($record->getAttributes(), false);

        if (is_string($model->snapshot)) {
            $model->snapshot = @json_decode($model->snapshot, true);
        }

        return $model;
    }

    // Public Methods
    // =========================================================================

    public function getFromUrl()
    {
        return urldecode($this->getSnapshotValue('url'));
    }

    public function getTargetUrl()
    {
        return urldecode($this->getSnapshotValue('targetUrl'));
    }

    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param $key
     *
     * @return array|string|null
     */
    public function getSnapshotValue($key)
    {
        return $this->snapshot[ $key ] ?? null;
    }

    /**
     * @param $key
     * @param $data
     *
     * @return $this
     */
    public function addSnapshotValue($key, $data)
    {
        if (!is_array($this->snapshot)) {
            $this->snapshot = [];
        }

        $this->snapshot[ $key ] = $data;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        parent::rules();
    }
}
