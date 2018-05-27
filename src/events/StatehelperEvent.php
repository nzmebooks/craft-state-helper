<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\statehelper\events;

use nzmebooks\statehelper\Statehelper;

use yii\base\Event;


class StatehelperEvent extends Event
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null User ID
     */
    public $userId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Value
     */
    public $value;
}
