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
    public string|null $driver = null;

    protected string $tableName = '{{%countryredirect_log}}';

    public function safeUp(): bool
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

    public function safeDown(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    protected function createTables(): bool
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema($this->tableName);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                $this->tableName,
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->notNull(),
                    'userId' => $this->integer()->null()->defaultValue(null),
                    'city' => $this->string()->null()->defaultValue(null),
                    'country' => $this->string()->null()->defaultValue(null),
                    'ipAddress' => $this->string()->null()->defaultValue(null),
                    'userAgent' => $this->string(255)->null()->defaultValue(null),
                    'snapshot' => $this->text()->null()->defaultValue(null),
                ]
            );
        }

        return $tablesCreated;
    }

    protected function createIndexes(): void
    {
        $this->createIndex(
            $this->db->getIndexName(),
            $this->tableName,
            'userId',
            false
        );

        if ($this->driver == DbConfig::DRIVER_MYSQL) {
        } elseif ($this->driver == DbConfig::DRIVER_PGSQL) {
        }
    }

    protected function addForeignKeys(): void
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(),
            $this->tableName,
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(),
            $this->tableName,
            'userId',
            '{{%users}}',
            'id',
            'SET NULL',
            'SET NULL'
        );
    }

    protected function insertDefaultData(): void
    {
    }

    protected function removeTables(): void
    {
        $this->dropTableIfExists($this->tableName);
    }
}
