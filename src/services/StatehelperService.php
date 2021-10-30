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

    public function log($message)
    {
      $logfile = Craft::getAlias('@storage/logs/statehelper.log');
      $log = date('Y-m-d H:i:s').' '.$message."\n";
      \craft\helpers\FileHelper::writeToFile($logfile, $log, ['append' => true]);
    }

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

        if ($name === "scenario:1" || $name === "scenario:2") {
          $this->log("$name for $userId:");
          $this->log("  $value");
        }

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
          SELECT
            users.email,
            users.firstName,
            users.lastName,
            contentPathways.title    AS pathwayTitle,
            contentCollections.title AS collectionTitle,
            contentEntries.title     AS entryTitle,
            statehelper.dateUpdated  AS dateCompleted
          FROM users, statehelper, categorygroups
          JOIN categories                        ON categories.groupId            = categorygroups.id
          JOIN content   AS contentPathways      ON contentPathways.elementId     = categories.id
          JOIN relations AS relationsPathways    ON relationsPathways.sourceId    = categories.id
          JOIN content   AS contentCollections   ON contentCollections.elementId  = relationsPathways.targetId
          JOIN relations AS relationsCollections ON relationsCollections.sourceId = relationsPathways.targetId
          JOIN entries                           ON entries.id                    = relationsCollections.targetId
          JOIN content   AS contentEntries       ON contentEntries.elementId      = relationsCollections.targetId
          WHERE categorygroups.name = 'Pathways'
          AND statehelper.userId = users.id
          AND entries.id = SUBSTRING(statehelper.name, 8)
          AND statehelper.name LIKE 'bottom:%'
          AND entries.id = relationsCollections.targetId
          ORDER BY
            users.id,
            relationsPathways.sourceId,
            relationsPathways.sortOrder,
            relationsCollections.sortOrder;
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
     * Get the curently-selected pathway for the all users
     *
     * @method getCurrentPathway
     * @return Array  An array of results.
     *
     */
    public static function getCurrentPathway()
    {
        $sql = "
        SELECT
          users.email,
          users.firstName,
          users.lastName,
          contentPathways.title    AS pathwayTitle,
          statehelper.dateUpdated  AS dateUpdated
        FROM users, statehelper, categorygroups
        JOIN categories                        ON categories.groupId            = categorygroups.id
        JOIN content   AS contentPathways      ON contentPathways.elementId     = categories.id
        WHERE categorygroups.name = 'Pathways'
        AND statehelper.userId = users.id
        AND categories.id = REGEXP_SUBSTR(statehelper.value, '[0-9]+')
        AND statehelper.name = 'pathwayCurrent'
        ORDER BY
          users.email;
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
     * Get the events attended for all users
     *
     * @method getEventsAttended
     * @return Array  An array of results.
     *
     */
    public static function getEventsAttended()
    {
       $sql = "
        SELECT
          users.email,
          users.firstName,
          users.lastName,
          statehelper.value,
          statehelper.dateUpdated  AS dateUpdated
        FROM users
        JOIN statehelper ON users.id = statehelper.userId
        WHERE statehelper.name = 'events'
        AND statehelper.value <> '{}'
        ORDER BY
          users.email;
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
     * Get the progress of users through the scenarios
     *
     * @method getScenarioProgress
     * @return Array  An array of results.
     *
     */
    public static function getScenarioProgress()
    {
       $sql = "
        SELECT
          users.email,
          users.firstName,
          users.lastName,
          statehelper.value,
          statehelper.dateUpdated  AS dateUpdated
        FROM users
        JOIN statehelper ON users.id = statehelper.userId
        WHERE statehelper.name LIKE 'scenario:%'
        AND statehelper.value <> '{}'
        ORDER BY
          users.email,
          statehelper.name,
          statehelper.dateUpdated;
        ";

        // We need to return the column headers as well as the data
        $command = Craft::$app->getDb()->createCommand($sql);
        $reader = $command->query();
        $statement = $command->pdoStatement;

        $results = [];
        $headers = [];

        for ($j = 0; $j < $statement->columnCount(); $j++) {
          if ($statement->getColumnMeta($j)['name'] !== 'value'
          && $statement->getColumnMeta($j)['name'] !== 'dateUpdated') {
            $headers[] = $statement->getColumnMeta($j)['name'];
          }
        }

        // Add the additional headers that we need as a result of
        // flattening the arrays in the row
        $headers[] = 'scenarioID';
        $headers[] = 'domainAgency';
        $headers[] = 'scenarioStartDate';
        $headers[] = 'scenarioCompletionDate';
        $headers[] = 'attempt';
        $headers[] = 'scenarioCompleted';
        $headers[] = 'chenPresent';
        $headers[] = 'totalScore';
        $headers[] = 'lastVisitedSlide';
        $headers[] = 'mileStoneId';
        $headers[] = 'mileStoneName';
        $headers[] = 'mileStoneData';

        $results['headers'] = $headers;
        $rows = $reader->readAll();

        $progressRows = array();
        foreach ($rows as $row) {
          $value = json_decode($row['value']);
          foreach ($value->progress as $progress) {

            if (count($progress->mileStones)) {
              foreach ($progress->mileStones as $mileStone) {
                $progressRow = self::flattenRowProgress($row, $progress);
                $progressRow['mileStoneId'] = $mileStone->mileStoneId ?? '';
                $progressRow['mileStoneName'] = $mileStone->mileStoneName ?? '';
                $progressRow['mileStoneData'] = $mileStone->mileStoneData ?? '';
                $progressRows[] = $progressRow;
              }
            } else {
              $progressRow = self::flattenRowProgress($row, $progress);
              $progressRow['mileStoneId'] = '';
              $progressRow['mileStoneName'] = '';
              $progressRow['mileStoneData'] = '';
              $progressRows[] = $progressRow;
            }
          }
        }

        $results['rows'] = $progressRows;

        return $results;
    }

    /**
     * Format the row progress as a flattened array
     *
     * @method flattenRowProgress
     * @return Array  An array of the flattened progress fields for the row.
     *
     */
    private static function flattenRowProgress($row, $progress)
    {
      $progressRow = array();
      $progressRow['email'] = $row['email'] ?? '';
      $progressRow['firstName'] = $row['firstName'] ?? '';
      $progressRow['lastName'] = $row['lastName'] ?? '';
      // $progressRow['dateUpdated'] = $row['dateUpdated'] ?? '';
      $progressRow['scenarioID'] = $progress->scenarioID ?? '';
      $progressRow['domainAgency'] = $progress->domainAgency ?? '';
      $progressRow['scenarioStartDate'] = $progress->scenarioStartDate ?? '';
      $progressRow['scenarioCompletionDate'] = $progress->scenarioCompletionDate ?? '';
      $progressRow['attempt'] = $progress->attempt ?? '';
      $progressRow['scenarioCompleted'] = ($progress->scenarioCompleted ? 'true' : 'false') ?? '';
      $progressRow['chenPresent'] = ($progress->chenPresent ? 'true' : 'false') ?? '';
      $progressRow['totalScore'] = $progress->totalScore ?? '';
      $progressRow['lastVisitedSlide'] = $progress->lastVisitedSlide ?? '';

      return $progressRow;
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
