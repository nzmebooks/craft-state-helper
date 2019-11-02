<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\statehelper\variables;

use Craft;
use nzmebooks\statehelper\Statehelper;

/**
 * Statehelper template variables
 *
 * @author    meBooks
 * @package   Statehelper
 * @since     1.0.0
 */
class StatehelperVariable
{
    // Public Methods
    // =========================================================================
    public function getPluginName()
    {
      $pluginName = Statehelper::$plugin->getName();

        return $pluginName;
    }

    /**
     * From any Twig template, call it like this:
     *
     *     {{ craft.statehelper.getState(stateName) }}
     *
     * @param $name
     * @return string
     */
    public function getState($name = null)
    {
        if (!$name || !Craft::$app->getUser()->id) {
            return false;
        }

        $userId = Craft::$app->getUser()->id;

        $state = Statehelper::$plugin->statehelperService->getState($userId, $name);

        // Decode the JSON string so we can access properties in our templates
        $value = $state->value ?? false;
        if ($value) {
            $state->value = json_decode($value, true);
        }

        return $state;
    }
}
