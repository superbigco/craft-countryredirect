<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\services;

use craft\helpers\FileHelper;
use GuzzleHttp\Client;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\CountryRedirect;

use Craft;
use craft\base\Component;
use superbig\countryredirect\models\Link;
use superbig\countryredirect\models\LogModel;
use superbig\countryredirect\records\LogRecord;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class CountryRedirect_LogService extends Component
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
    }

    public function getAllLogs($offset, $limit = 20)
    {
        $query = LogRecord::find();

        if ($offset !== null) {
            $query = $query
                ->offset($offset)
                ->limit($limit);
        }

        $logs = $query->all();

        if (!$logs) {
            return null;
        }

        return array_map(function(LogRecord $record) {
            return LogModel::createFromRecord($record);
        }, $logs);
    }

    /**
     * @param LogModel $model
     *
     * @return bool
     */
    public function saveRecord(LogModel &$model)
    {
        if ($model->id) {
            $record = LogRecord::findOne($model->id);
        }
        else {
            $record = new LogRecord();
        }

        $record->userId    = $model->userId;
        $record->siteId    = $model->siteId;
        $record->ipAddress = $model->ipAddress;
        $record->userAgent = $model->userAgent;
        $record->city      = $model->city;
        $record->country   = $model->country;
        $record->snapshot  = $model->snapshot;

        if (!$record->save()) {
            Craft::error(
                Craft::t('country-redirect', 'An error occured when saving country-redirect log record: {error}',
                    [
                        'error' => print_r($record->getErrors(), true),
                    ]),
                'country-redirect');

            return false;
        }
        $model->id = $record->id;

        return true;
    }
}
