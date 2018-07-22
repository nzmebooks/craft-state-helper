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
