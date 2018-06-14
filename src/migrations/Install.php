<?php
/**
 * Country Redirect plugin for Craft CMS 3.x
 *
 * Easily redirect visitors to a site based on their country of origin
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\countryredirect\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    Superbig
 * @package   CountryRedirect
 * @since     2.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================
    /**
     * @var string The database driver to use
     */
    public    $driver;
    protected $tableName = '{{%countryredirect_log}}';
    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }
    // Protected Methods
    // =========================================================================
    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;
        $tableSchema   = Craft::$app->db->schema->getTableSchema($this->tableName);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                $this->tableName,
                [
                    'id'          => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    'siteId'      => $this->integer()->notNull(),
                    'userId'      => $this->integer()->null()->defaultValue(null),
                    'city'        => $this->string()->null()->defaultValue(null),
                    'country'     => $this->string()->null()->defaultValue(null),
                    'ipAddress'   => $this->string()->null()->defaultValue(null),
                    'userAgent'   => $this->string(255)->null()->defaultValue(null),
                    'snapshot'    => $this->text()->null()->defaultValue(null),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                $this->tableName,
                'userId',
                false
            ),
            $this->tableName,
            'userId',
            false
        );

        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->tableName, 'siteId'),
            $this->tableName,
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->tableName, 'userId'),
            $this->tableName,
            'userId',
            '{{%users}}',
            'id',
            'SET NULL',
            'SET NULL'
        );
    }

    /**
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists($this->tableName);
    }
}