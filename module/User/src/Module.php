<?php

namespace User;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\Factory\InvokableFactory;
use User\Auth\AuthManager;
use User\Auth\AuthAdapter;
use User\Model\UserTable;
use Zend\Mvc\MvcEvent;
use Zend\Session\SessionManager;
use User\Controller\UserController;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
	{
		return [
			'factories' => [
				UserTable::class => function($container)
				{
					return new Model\UserTable($container->get(Model\UserTableGateway::class));
				},
				Model\UserTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\User());
					return new TableGateway('user', $adapter, null, $resultSet);
				},
				AuthAdapter::class => function($container)
				{
					return new AuthAdapter($container->get(UserTable::class), $container->get(SessionManager::class));
				},
				AuthManager::class => function($container)
				{
					return new AuthManager($container->get(AuthenticationService::class), $container->get(AuthAdapter::class), $container->get(UserTable::class));
				},
				AuthenticationService::class => InvokableFactory::class,
			]
		];
	}

	public function getControllerConfig()
	{
		return [
			'factories' => [
				UserController::class => function($container)
				{
					return new UserController($container->get(AuthManager::class), $container->get(UserTable::class), $container->get('config'));
				}
			]
		];
	}

	public function init(ModuleManager $moduleManager)
	{
		$moduleName = $moduleManager->getEvent()->getModuleName();
		if($moduleName == 'User'){
			$events = $moduleManager->getEventManager();
			$sharedEvents = $events->getSharedManager();
			$sharedEvents->attach(__NAMESPACE__, 'dispatch', [$this, 'initAuth'], 100);
		}
	}

	public function initAuth(MvcEvent $e)
	{
		$app = $e->getApplication();
		$routerMatch = $e->getRouteMatch();
		$module = $routerMatch->getMatchedRouteName();
		$controllerName = $routerMatch->getParam('controller');
		$action = $routerMatch->getParam('action');

		$sm = $app->getServiceManager();
		$auth = $sm->get('User\Auth\AuthManager');

		$controller = $e->getTarget();
		$layout = $controller->layout();
		$config = $sm->get('config');
		$layout->app_config = $config['app'];

		if($controllerName == 'User\Controller\UserController')
		{
			$redirect = [ 'login', 'register', 'activeuser', 'forgot' ];

			if(in_array($action, $redirect) && $auth->checkLogin())
			{
				return $controller->redirect()->toRoute('home');
			}

			if(!in_array($action, $redirect) && !$auth->checkLogin())
			{
				return $controller->redirect()->toRoute('login');
			}
		}

		if($auth->checkLogin())
		{
			$layout->user = $auth->getUser();
		}
		
	}
}
