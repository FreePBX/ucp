<?php
namespace FreePBX\modules\Ucp\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
use League\OAuth2\Server\AuthorizationServer;
use \Ramsey\Uuid\Uuid;
use \Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
class Dashboard extends Base {
	protected $module = 'ucp';
	public static function getScopes() {
		return [
			'read:dashboard' => [
				'description' => _('Read UCP Dashboard Information'),
			],
			'write:dashboard' => [
				'description' => _('Write UCP Dashboard Information'),
			]
		];
	}
	public function setupRoutes($app) {
		//get all dashboards
		$app->get('/dashboard/tab', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboards');
			$dashboards = is_array($dashboards) ? $dashboards : array();
			return $response->withJson($dashboards);
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//update dashboard tab layout
		$app->post('/dashboard/tab/layout', function ($request, $response, $args) {
			$order = $request->getParsedBody();
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboards');
			$dashboards = is_array($dashboards) ? $dashboards : array();
			@usort($dashboards, function($a,$b) use ($order) {
				$keya = array_search($a['id'],$order);
				$keyb = array_search($b['id'],$order);
				return ($keya < $keyb) ? -1 : 1;
			});
			$this->freepbx->Ucp->setSettingByID($user,'Global','dashboards',$dashboards);
			return $response->withJson(array("status" => true));
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//add new dashboard
		$app->put('/dashboard/tab', function ($request, $response, $args) {
			$params = $request->getParsedBody();
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboards');
			$dashboards = is_array($dashboards) ? $dashboards : array();
			$id = (string)Uuid::uuid4();
			$dashboards[] = array(
				"id" => $id,
				"name" => $params['name']
			);
			$this->freepbx->Ucp->setSettingByID($user,'Global','dashboards',$dashboards);
			return $response->withJson(array("status" => true, "id" => $id));
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//update dashboard
		$app->post('/dashboard/tab/{dashboard_id}', function ($request, $response, $args) {
			$params = $request->getParsedBody();
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboards');
			$dashboards = is_array($dashboards) ? $dashboards : array();
			$res = array("status" => false, "message" => "Invalid Dashboard ID");
			foreach($dashboards as $k => $d) {
				if($d['id'] == $args['dashboard_id']) {
					$dashboards[$k]['name'] = $params['name'];
					$this->freepbx->Ucp->setSettingByID($user,'Global','dashboards',$dashboards);
					$res = array("status" => true, "id" => $d['id']);
					break;
				}
			}
			return $response->withJson($res);
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//delete dashboard
		$app->delete('/dashboard/tab/{dashboard_id}', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboards');
			$dashboards = is_array($dashboards) ? $dashboards : array();
			$res = array("status" => false, "message" => "Invalid Dashboard ID");
			foreach($dashboards as $k => $d) {
				if($d['id'] == $args['dashboard_id']) {
					unset($dashboards[$k]);
					$this->freepbx->Ucp->setSettingByID($user,'Global','dashboards',$dashboards);
					$this->freepbx->Ucp->setSettingByID($user,'Global','dashboard-layout-'.$args['dashboard_id'],null);
					$res = array("status" => true);
					break;
				}
			}
			return $response->withJson($res);
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//get dashboard widgets
		$app->get('/dashboard/{dashboard_id}/widget', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$widgets = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-layout-'.$args['dashboard_id']);
			$widgets = json_decode($widgets,true);
			return $response->withJson($widgets);
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//update dashboard widget layout
		$app->post('/dashboard/{dashboard_id}/widget/layout', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$this->freepbx->Ucp->setSettingByID($user,'Global','dashboard-layout-'.$args['dashboard_id'],json_encode($request->getParsedBody()));
			return $response->withJson(array("status" => true));
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//get widget content
		$app->get('/dashboard/{dashboard_id}/widget/{widget_id}/content', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$ucp = $this->freepbx->Ucp->getUCPObject($user);

			$widgets = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-layout-'.$args['dashboard_id']);

			$widgets = json_decode($widgets,true);

			foreach($widgets as $widget) {
				if($widget['id'] === $args['widget_id']) {
					if($ucp->Modules->moduleHasMethod($widget['rawname'], 'getWidgetDisplay')) {
						$module = ucfirst(strtolower($widget['rawname']));
						return $response->withJson($ucp->Modules->$module->getWidgetDisplay($widget['widget_type_id'], $widget['id']));
					}
				}
			}
			return $response->withJson(array());
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//get widget setting content
		$app->get('/dashboard/{dashboard_id}/widget/{widget_id}/setting/content', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$ucp = $this->freepbx->Ucp->getUCPObject($user);

			$widgets = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-layout-'.$args['dashboard_id']);

			$widgets = json_decode($widgets,true);

			foreach($widgets as $widget) {
				if($widget['id'] === $args['widget_id']) {
					if($ucp->Modules->moduleHasMethod($widget['rawname'], 'getWidgetDisplay')) {
						$module = ucfirst(strtolower($widget['rawname']));
						return $response->withJson($ucp->Modules->$module->getWidgetSettingsDisplay($widget['widget_type_id'], $widget['id']));
					}
				}
			}
			return $response->withJson(array());
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//get side widgets
		$app->get('/dashboard/side', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$dashboards = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-simple-layout');
			$dashboards = json_decode($dashboards,true);
			$dashboards = is_array($dashboards) ? $dashboards : array();
			return $response->withJson($dashboards);
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//update side layout
		$app->post('/dashboard/side/layout', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$this->freepbx->Ucp->setSettingByID($user,'Global','dashboard-simple-layout',json_encode($request->getParsedBody()));
			return $response->withJson(array('status' => true));
		})->add($this->checkWriteScopeMiddleware('dashboard'));

		//get side widget content
		$app->get('/dashboard/side/{widget_id}/content', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$widgets = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-simple-layout');
			$widgets = json_decode($widgets,true);

			$ucp = $this->freepbx->Ucp->getUCPObject($user);

			foreach($widgets as $widget) {
				if($widget['id'] === $args['widget_id']) {
					if($ucp->Modules->moduleHasMethod($widget['rawname'], 'getSimpleWidgetDisplay')) {
						$module = ucfirst(strtolower($widget['rawname']));
						return $response->withJson($ucp->Modules->$module->getSimpleWidgetDisplay($widget['widget_type_id'], $widget['id']));
					}
					if($ucp->Modules->moduleHasMethod($widget['rawname'], 'getWidgetDisplay')) {
						$module = ucfirst(strtolower($widget['rawname']));
						return $response->withJson($ucp->Modules->$module->getWidgetDisplay($widget['widget_type_id'], $widget['id']));
					}
				}
			}

			return $response->withJson(array());
		})->add($this->checkReadScopeMiddleware('dashboard'));

		//get side widget setting content
		$app->get('/dashboard/side/{widget_id}/setting/content', function ($request, $response, $args) {
			$user = $request->getAttribute('oauth_user_id');
			$ucp = $this->freepbx->Ucp->getUCPObject($user);

			$widgets = $this->freepbx->Ucp->getSettingByID($user,'Global','dashboard-simple-layout');

			$widgets = json_decode($widgets,true);

			foreach($widgets as $widget) {
				if($widget['id'] === $args['widget_id']) {
					if($ucp->Modules->moduleHasMethod($widget['rawname'], 'getSimpleWidgetSettingsDisplay')) {
						$module = ucfirst(strtolower($widget['rawname']));
						return $response->withJson($ucp->Modules->$module->getSimpleWidgetSettingsDisplay($widget['widget_type_id'], $widget['id']));
					}
				}
			}
			return $response->withJson(array());
		})->add($this->checkReadScopeMiddleware('dashboard'));
	}
}
