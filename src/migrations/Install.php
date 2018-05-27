<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\statehelper\migrations;

use nzmebooks\statehelper\Statehelper;
use nzmebooks\statehelper\records\StatehelperRecord as StatehelperRecord;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * craft-state-helper Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    meBooks
 * @package   Statehelper
 * @since     1.0.0
 */
class Install extends Migration
{
    // Properties
    // =========================================================================

    private $_usersTable;
    private $_statehelperTable;

    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->_usersTable = $this->db->getSchema()->getRawTableName('{{%users}}');
        $this->_statehelperTable = $this->db->getSchema()->getRawTableName(StatehelperRecord::tableName());

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
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->_usersTable = $this->db->getSchema()->getRawTableName('{{%users}}');
        $this->_statehelperTable = $this->db->getSchema()->getRawTableName(StatehelperRecord::tableName());

        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema($this->_statehelperTable);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                $this->_statehelperTable,
                [
                    'id' => $this->primaryKey(),
                    'userId' => $this->integer()->notNull(),
                    'name' => $this->string(255)->notNull()->defaultValue(''),
                    'value' => $this->text()->notNull()->defaultValue(''),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName($this->_statehelperTable, 'name', true),
            $this->_statehelperTable,
            'name',
            true
        );
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->_statehelperTable, 'userId'),
            $this->_statehelperTable,
            'userId',
            $this->_usersTable,
            'id',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists($this->_statehelperTable);
    }
}
