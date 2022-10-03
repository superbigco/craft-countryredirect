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

use Craft;

use craft\base\Component;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\LogModel;
use superbig\countryredirect\records\LogRecord;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class LogService extends Component
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
    }

    /**
     * @param     $offset
     *
     */
    public function getAllLogs($offset = null, int $limit = 20): ?array
    {
        $query = LogRecord::find()->orderBy('dateCreated desc');

        if ($offset !== null) {
            $query = $query
                ->offset($offset)
                ->limit($limit);
        }

        $logs = $query->all();

        if ($logs === []) {
            return null;
        }

        return array_map(static fn(LogRecord $record): \superbig\countryredirect\models\LogModel => LogModel::createFromRecord($record), $logs);
    }


    public function getLogCount(): int
    {
        return (int)LogRecord::find()->count();
    }

    /**
     * @param null $targetUrl
     */
    public function logRedirect($targetUrl = null): void
    {
        $redirectService = CountryRedirect::$plugin->countryRedirectService;
        $log = new LogModel();
        $log->siteId = Craft::$app->getSites()->currentSite->id;
        $request = Craft::$app->getRequest();
        $log->userAgent = $request->getUserAgent();
        $log->ipAddress = $redirectService->getIpAddress();

        if ($user = Craft::$app->getUser()->getIdentity()) {
            $log->userId = $user->id;
        }

        if ($info = $redirectService->getInfoFromIp($log->ipAddress)) {
            $log->addSnapshotValue('info', $info);
            $log->country = $info->country->isoCode ?? null;
        }

        if ($targetUrl) {
            try {
                $log->addSnapshotValue('url', $request->getAbsoluteUrl());
                $log->addSnapshotValue('targetUrl', $targetUrl);
            } catch (InvalidConfigException) {
            }
        }

        $this->saveRecord($log);
    }

    public function saveRecord(LogModel &$model): bool
    {
        if ($model->id) {
            $record = LogRecord::findOne($model->id);
        } else {
            $record = new LogRecord();
        }

        $record->userId = $model->userId;
        $record->siteId = $model->siteId;
        $record->ipAddress = $model->ipAddress;
        $record->userAgent = $model->userAgent;
        $record->city = $model->city;
        $record->country = $model->getCountry();
        $record->snapshot = $model->snapshot;

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

    public function clearLogs(): int
    {
        return LogRecord::deleteAll();
    }
}
