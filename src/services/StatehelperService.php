<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\statehelper\services;

use nzmebooks\statehelper\Statehelper;
use nzmebooks\statehelper\models\StatehelperModel;
use nzmebooks\statehelper\records\StatehelperRecord;
use nzmebooks\statehelper\events\StatehelperEvent;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\StringHelper;

/**
 * StatehelperService Service
 *
 * @author    meBooks
 * @package   Statehelper
 * @since     1.0.0
 */
class StatehelperService extends Component
{
    // Constants
    // =========================================================================
    /**
     * @event SaveStateEvent The event that is triggered when state is saved
     */
    const EVENT_ON_SAVE_STATE = 'onSaveState';

    // Public Methods
    // =========================================================================

    /**
     * Save the supplied state against the user in the database
     *
     * From any other plugin file, call it like this:
     *     Statehelper::$plugin->statehelperService->saveState()
     *
     * @method saveState
     * @param object $model A State object.
     * @return boolean
     *
     */
    public function saveState(StatehelperModel $model)
    {
        $userId = $model->userId;
        $name   = $model->name;
        $value  = $model->value;

        $params = array(
            'userId' => $userId,
            'name'   => $name
        );

        $record = StatehelperRecord::find()->where($params)->one();

        if (!$record) {
            // We've not saved this name for this user before,
            // so add a new record.
            $model = new StatehelperModel();
            $model->userId = $userId;
            $model->name   = $name;
            $model->value  = $value;

            $record = new StatehelperRecord();
            $record->userId = $model->userId;
            $record->name   = $model->name;
            $record->value  = $model->value;

            $record->validate();
            $model->addErrors($record->getErrors());

            if (!$model->hasErrors()) {
                $record->save(false);
                $model->id = $record->id;

                $this->onSaveState($model);
            }
        } else {
            // We've saved this name for this user before,
            // so amend the existing record.
            $record->value = $value;
            $record->save();
        }

        return true;
    }

    /**
     * Delete the supplied state against the user in the database
     *
     * From any other plugin file, call it like this:
     *     Statehelper::$plugin->statehelperService->deleteState()
     *
     * @method deleteState
     * @param object $model A State object.
     * @return boolean
     *
     */
    public function deleteState(StatehelperModel $model)
    {
        $userId = $model->userId;
        $name   = $model->name;

        $params = array(
            'userId' => $userId,
            'name'   => $name
        );

        $records = StatehelperRecord::deleteAll($params);

        return true;
    }

    /**
     * Get the state for the supplied userId and name
     *
     * @method getState
     * @param string  $userId The id of the currently logged-in user.
     * @param string  $name The state name to be retrieved.
     * @return object $record A State object.
     *
     */
    public function getState($userId, $name)
    {
        $params = array(
            'userId' => $userId,
            'name'   => $name
        );

        $record = StatehelperRecord::find()->where($params)->one();

        return $record
          ? $record
          : false;
    }

    /**
     * Get the pathways progress for the all users
     *
     * @method getPathwayProgress
     * @return Array  An array of results.
     *
     */
    public static function getPathwayProgress()
    {
        $sql = "
          SELECT users.email, users.firstName, users.lastName, contentPathways.title AS pathwayTitle, contentCollections.title AS collectionTitle, contentEntries.title AS entryTitle
          FROM users, categorygroups
          JOIN categories                        ON categories.groupId            = categorygroups.id
          JOIN content   AS contentPathways      ON contentPathways.elementId     = categories.id
          JOIN relations AS relationsPathways    ON relationsPathways.sourceId    = categories.id
          JOIN content   AS contentCollections   ON contentCollections.elementId  = relationsPathways.targetId
          JOIN relations AS relationsCollections ON relationsCollections.sourceId = relationsPathways.targetId
          JOIN entries                           ON entries.id                    = relationsCollections.targetId
          JOIN content   AS contentEntries       ON contentEntries.elementId      = relationsCollections.targetId
          WHERE name = 'Pathways'
          AND users.id IN (
            SELECT statehelper.userId
            FROM statehelper
            JOIN entries ON entries.id = SUBSTRING(statehelper.name, 8)
            WHERE statehelper.name LIKE 'bottom:%'
            AND statehelper.userId = users.id
            AND entries.id = relationsCollections.targetId
          )
          ORDER BY users.id, relationsPathways.sourceId, relationsPathways.sortOrder, relationsCollections.sortOrder;
        ";

        // We need to return the column headers as well as the data
        $command = Craft::$app->getDb()->createCommand($sql);
        $reader = $command->query();
        $statement = $command->pdoStatement;

        $results = [];
        $headers = [];

        for ($j = 0; $j < $statement->columnCount(); $j++) {
            $headers[] = $statement->getColumnMeta($j)['name'];
        }

        $results['headers'] = $headers;
        $results['rows'] = $reader->readAll();

        return $results;
    }

    /**
     * Format the supplied data as a csv.
     *
     * @param array $data
     *
     * @return string
     *
     * @throws Exception
     */
    public static function formatAsCsv($data)
    {
        // Get max power
        App::maxPowerCaptain();

        // Get delimiter
        $delimiter = ',';

        // Open output buffer
        ob_start();

        // Write to output stream
        $export = fopen('php://output', 'w');

        // If there is data, process
        if (is_array($data) && count($data)) {

            // Gather headers
            $headers = array();

            foreach ($data['headers'] as $header) {
                // Encode and add to rows
                $headers[] = StringHelper::convertToUTF8($header);
            }
            // Add rows to export
            fputcsv($export, $headers, $delimiter);

            // Loop through rows
             foreach ($data['rows'] as $fields) {

                // Gather row data
                $rows = array();

                // Loop through the fields
                foreach ($fields as $field) {
                    // Encode and add to rows
                    $rows[] = StringHelper::convertToUTF8($field);
                }

                // Add rows to export
                fputcsv($export, $rows, $delimiter);
            }
        }

        // Close buffer and return data
        fclose($export);
        $data = ob_get_clean();

        // Use windows friendly newlines
        $data = str_replace("\n", "\r\n", $data);

        // Return the data to controller
        return $data;
    }

    public static function deleteStateByUserId($userId)
    {
        $params = array(
            'userId' => $userId
        );

        $records = StatehelperRecord::deleteAll($params);

        return true;
    }

    public function onSaveState(StatehelperModel $model)
    {
        $event = new StatehelperEvent([
          'id'     => $model->id,
          'userId' => $model->userId,
          'name'   => $model->name,
          'value'  => $model->value,
        ]);

        $this->trigger(self::EVENT_ON_SAVE_STATE, $event);
    }
}
