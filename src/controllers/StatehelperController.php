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

        if (!Craft::$app->user->isLoggedIn()) {
            return false;
        }

        $userId = Craft::$app->user->getUser()->id;

        foreach ($request->getPost() as $key => $value) {
            $data[$key] = $value;
        }

        $model = new StatehelperModel();
        $model->userId = $userId;
        $model->name   = $data['name'];
        $model->value  = $data['value'];

        if ($model->validate()) {
            $response = StatehelperService::saveState($model);
        }

        if ($request->getAcceptsJson()) {
            if ($response) {
                return $this->asJson(['success' => $response]);
            } else {
                return $this->asJson(['errors' => [Craft::t("Couldn't save state.")]]);
            }
        } else {
            Craft::$app->urlManager->setRouteVariables(array(
                'state' => $model
            ));

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
        $name = $request->getParam('name');

        if (!Craft::$app->user->isLoggedIn()) {
          return false;
        }

        $userId = Craft::$app->user->getUser()->id;
        $record = StatehelperService::getState($userId, $name);

        if ($request->getIsAjax()) {
            if ($record) {
                return $this->asJson(array(
                    'success' => true,
                    'name'    => $name,
                    'value'   => $record->value
                ));
            }
            else {
                return $this->returnErrorJson(Craft::t("Couldn't get state"));
            }
        }
        else {
            return $this->redirect($request->getReferrer());
        }
    }
}
