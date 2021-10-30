<?php
/**
 * craft-state-helper plugin for Craft CMS 3.x
 *
 * A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\statehelper\controllers;

use nzmebooks\statehelper\Statehelper;
use nzmebooks\statehelper\services\StatehelperService;

use Craft;
use craft\web\Controller;
use craft\helpers\DateTimeHelper;
use yii\web\Cookie;

/**
 * Class DefaultController
 *
 * @author    meBooks
 * @package   StateHelper
 * @since     1.2.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     *  Our index action
     *
     * @return mixed
     */
    public function actionIndex()
    {
        // $state = StateHelper::$plugin->state->getState();

        $this->renderTemplate('statehelper/home/index', array(
          'tab' => 'home',
          // 'state' => $state,
        ));
    }

    /**
     * Download export of users pathway progress.
     *
     * @return string CSV
     */
    public function actionDownloadPathwayProgress()
    {
        // Get data
        $results = StatehelperService::getPathwayProgress();
        $csv = StatehelperService::formatAsCsv($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'statehelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($csv, "state_helper_export_pathway_progress_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));

        Craft::$app->end();
    }

    /**
     * Download export of users currently-selected pathway.
     *
     * @return string CSV
     */
    public function actionDownloadCurrentPathway()
    {
        // Get data
        $results = StatehelperService::getCurrentPathway();
        $csv = StatehelperService::formatAsCsv($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'statehelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($csv, "state_helper_export_current_pathway_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));

        Craft::$app->end();
    }

    /**
     * Download export of events marked as attended by users .
     *
     * @return string CSV
     */
    public function actionDownloadEventsAttended()
    {
        // Get data
        $results = StatehelperService::getEventsAttended();
        $csv = StatehelperService::formatAsCsv($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'statehelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($csv, "state_helper_export_events_attended_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));

        Craft::$app->end();
    }

    /**
     * Download export of users progress through the scenarios.
     *
     * @return string CSV
     */
    public function actionDownloadScenarioProgress()
    {
        // Get data
        $results = StatehelperService::getScenarioProgress();
        $csv = StatehelperService::formatAsCsv($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'statehelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($csv, "state_helper_export_scenario_progress_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));

        Craft::$app->end();
    }
}
