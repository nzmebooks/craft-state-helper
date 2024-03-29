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
use nzmebooks\statehelper\models\StatehelperModel;
use nzmebooks\statehelper\services\StatehelperService;

use Craft;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    meBooks
 * @package   Statehelper
 * @since     1.0.0
 */
class StatehelperController extends Controller
{
    // We disable CSRF validation for the entire controller
    // as we need to let static html pages have access to the methods
    // on this controller.
    // This is not a security issue, as we always check for the logged-in user:
    //   $userId = Craft::$app->getUser()->id;
    public $enableCsrfValidation = false;

    // Public Methods
    // =========================================================================

    /**
     * Create and prep a State object to be sent to the Service. This
     * method also santizes user input as much as reasonably possible.
     *
     * @method actionSaveState
     * @return void
     *
     */
    public function actionSaveState()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $userId = Craft::$app->getUser()->id;

        if (!$userId) {
            return false;
        }

        $model = new StatehelperModel();
        $model->userId = $userId;
        $model->name   = $request->getBodyParam('name');
        $model->value  = $request->getBodyParam('value');

        if ($model->validate()) {
            $response = Statehelper::$plugin->statehelperService->saveState($model);
        }

        if ($request->getAcceptsJson()) {
            if ($response) {
                return $this->asJson(['success' => $response]);
            } else {
                return $this->asJson([
                  'success' => false,
                  'errors' => ["Couldn't save state for key $model->name."]
                ]);
            }
        } else {
            Craft::$app->getUrlManager()->setRouteParams([
              'state' => $model
            ]);

            return $this->redirectToPostedUrl();
        }
    }

    /**
     * Create and prep a State object to be sent to the Service. This
     * method also santizes user input as much as reasonably possible.
     *
     * @method actionDeleteState
     * @return void
     *
     */
    public function actionDeleteState()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $userId = Craft::$app->getUser()->id;

        if (!$userId) {
            return false;
        }

        $model = new StatehelperModel();
        $model->userId = $userId;
        $model->name   = $request->getBodyParam('name');

        if ($model->validate()) {
            $response = Statehelper::$plugin->statehelperService->deleteState($model);
        }

        if ($request->getAcceptsJson()) {
            if ($response) {
                return $this->asJson(['success' => $response]);
            } else {
                return $this->asJson([
                  'success' => false,
                  'errors' => ["Couldn't delete state for key: $model->name."],
                ]);
            }
        } else {
            Craft::$app->getUrlManager()->setRouteParams([
              'state' => $model
            ]);

            return $this->redirectToPostedUrl();
        }
    }

    /**
     * Retrieve a state object for the current user and specified name.
     *
     * @method actionGetState
     * @return array name and value
     *
     */
    public function actionGetState(array $variables = array())
    {
        $request = Craft::$app->getRequest();
        $name = $request->getBodyParam('name');

        $userId = Craft::$app->getUser()->id;

        if (!$userId) {
          return false;
        }

        $record = Statehelper::$plugin->statehelperService->getState($userId, $name);

        if ($request->getAcceptsJson()) {
          if ($record) {
            return $this->asJson([
              'success' => true,
              'value'   => $record->value,
            ]);
          } else {
              return $this->asJson([
                'success' => false,
                'value'   => null,
                'errors' => ["Couldn't find state for key: $name"],
              ]);
          }
        } else {
            return $this->redirectToPostedUrl();
        }
    }
}
