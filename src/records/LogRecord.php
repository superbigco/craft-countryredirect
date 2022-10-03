<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a locale based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\records;

use craft\db\ActiveRecord;
use craft\elements\User;
use superbig\countryredirect\CountryRedirect;
use yii\db\ActiveQueryInterface;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 *
 * @property int       $id
 * @property int       $siteId
 * @property int       $userId
 * @property string    $ipAddress
 * @property string    $userAgent
 * @property string    $city
 * @property string    $country
 * @property string    $snapshot
 * @property \DateTime $dateCreated
 */
class LogRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%countryredirect_log}}';
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the log entry’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): \craft\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
