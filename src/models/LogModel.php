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

use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\records\LogRecord;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class LogModel extends Model
{
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

    public static function createFromRecord(LogRecord $record): static
    {
        $model = new static();
        $model->setAttributes($record->getAttributes(), false);

        if (is_string($model->snapshot)) {
            $model->snapshot = @json_decode($model->snapshot, true, 512, JSON_THROW_ON_ERROR);
        }

        return $model;
    }

    public function getFromUrl(): string
    {
        return urldecode($this->getSnapshotValue('url'));
    }

    public function getTargetUrl(): string
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

    public function addSnapshotValue($key, $data): static
    {
        if (!is_array($this->snapshot)) {
            $this->snapshot = [];
        }

        $this->snapshot[ $key ] = $data;

        return $this;
    }
}
