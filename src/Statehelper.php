<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 * @license   MIT License https://opensource.org/licenses/MIT
 */

namespace nzmebooks\statehelper;

use nzmebooks\statehelper\services\StatehelperService;
use nzmebooks\statehelper\variables\StatehelperVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\elements\User;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * Class Statehelper
 *
 * @author    meBooks
 * @package   Statehelper
 * @since     1.0.0
 *
 * @property  StatehelperService statehelper
 */
class Statehelper extends Plugin
{
    /**
     * @var Statehelper
     */
    public static $plugin;

    /**
     *  @inheritdoc
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->name = $this->getName();


        $this->setComponents([
            'statehelperController' => StatehelperController::class,
            'statehelperService' => StatehelperService::class,
        ]);

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('statehelper', StatehelperVariable::class);
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_DELETE,
            function (Event $event) {
                $user = $event->sender;
                $userId = $user->id;

                StatehelperService::deleteStateByUserId($userId);
            }
        );

        // register the actions
        // Event::on(
        //     UrlManager::class,
        //     UrlManager::EVENT_REGISTER_SITE_URL_RULES,
        //     function($event) {
        //         $event->rules['POST statehelper/statehelper/save-state'] = 'statehelper/statehelper/save-state';
        //     }
        // );
/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'statehelper',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Returns the user-facing name of the plugin, which can override the name
     * in composer.json
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('statehelper', 'State Helper');
    }
}
