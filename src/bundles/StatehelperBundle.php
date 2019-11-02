<?php

/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

 namespace nzmebooks\statehelper\bundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class StateHelperBundle
 *
 * @author    meBooks
 * @package   StateHelper
 * @since     1.2.0
 */
class StatehelperBundle extends AssetBundle
{
    public function init()
    {
        // Define the path that your publishable resources live
        $this->sourcePath = '@nzmebooks/statehelper/resources';

        // Define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // Define the relative path to CSS/JS files that should be registered
        // with the page when this asset bundle is registered
        $this->js = [
            'js/state-helper.js',
        ];

        $this->css = [
            'css/state-helper.css',
        ];

        parent::init();
    }
}
