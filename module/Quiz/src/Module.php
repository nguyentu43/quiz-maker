<?php

namespace Quiz;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\Factory\InvokableFactory;
use User\Auth\AuthManager;
use User\Model\UserTable;
use Zend\Mvc\MvcEvent;
use Zend\Config\Config;
use Zend\Mvc\Controller\LazyControllerAbstractFactory;

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
				Model\TestTable::class => function($container)
				{
					return new Model\TestTable($container->get(Model\TestTableGateway::class));
				},
				Model\TestTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\Test());
					return new TableGateway('test', $adapter, null, $resultSet);
				},
				Model\QuestionTable::class => function($container)
				{
					return new Model\QuestionTable($container->get(Model\QuestionTableGateway::class));
				},
				Model\QuestionTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\Question());
					return new TableGateway('question', $adapter, null, $resultSet);
				},
				Model\TestResultTable::class => function($container)
				{
					return new Model\TestResultTable($container->get(Model\TestResultTableGateway::class));
				},
				Model\TestResultTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\TestResult());
					return new TableGateway('result', $adapter, null, $resultSet);
				},
				Model\CategoryTable::class => function($container)
				{
					return new Model\CategoryTable($container->get(Model\CategoryTableGateway::class));
				},
				Model\CategoryTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\Category());
					return new TableGateway('category', $adapter, null, $resultSet);
				},
				Model\CategoryTestTable::class => function($container)
				{
					return new Model\CategoryTestTable($container->get(Model\CategoryTestTableGateway::class));
				},
				Model\CategoryTestTableGateway::class => function($container)
				{
					$adapter = $container->get(AdapterInterface::class);
					$resultSet = new ResultSet(new Model\CategoryTest());
					return new TableGateway('category_test', $adapter, null, $resultSet);
				}
			]
		];
	}

	public function getControllerConfig()
	{
		return [
			'factories' => [
				Controller\IndexController::class => function($container)
				{
					return new Controller\IndexController($container->get(AuthManager::class), $container->get(Model\TestTable::class), $container->get(Model\TestResultTable::class), $container->get(Model\QuestionTable::class), $container->get(UserTable::class), $container->get(Model\CategoryTestTable::class));
				},
				Controller\TestController::class => function($container)
				{
					return new Controller\TestController($container->get(AuthManager::class), $container->get(Model\TestTable::class), $container->get(Model\QuestionTable::class), $container->get(Model\TestResultTable::class), $container->get(UserTable::class), $container->get(Model\CategoryTestTable::class), $container->get(Model\CategoryTable::class), $container->get('config'));
				},
				Controller\QuestionController::class => function($container)
				{
					return new Controller\QuestionController($container->get(Model\QuestionTable::class), $container->get(Model\CategoryTable::class), $container->get(Model\TestTable::class), $container->get(AuthManager::class), $container->get(UserTable::class));
				},
				Controller\ResultController::class => function($container)
				{
					return new Controller\ResultController($container->get(AuthManager::class), $container->get(Model\TestResultTable::class), $container->get(Model\TestTable::class), $container->get(Model\QuestionTable::class), $container->get(UserTable::class), $container->get('config'));
				},
				Controller\CategoryController::class => function($container)
				{
					return new Controller\CategoryController($container->get(AuthManager::class), $container->get(Model\CategoryTestTable::class), $container->get('config'));
				}
			]
		];
	}

	public function init(ModuleManager $moduleManager)
	{
		$moduleName = $moduleManager->getEvent()->getModuleName();
		if($moduleName == 'Quiz'){
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
		$controller = $e->getTarget();
		$layout = $controller->layout();

		$auth = $sm->get('User\Auth\AuthManager');
		$config = $sm->get('config');
		$layout->app_config = $config['app'];

		if($auth->checkLogin())
		{
			$layout->user = $auth->getUser();
		}
		else
		{
			if(!(($controllerName == 'Quiz\Controller\QuestionController' && in_array($action, ['getlist', 'download'])) || ($controllerName == 'Quiz\Controller\IndexController' && in_array($action, ['index', 'enterid'])) || ($controllerName == 'Quiz\Controller\TestController' && in_array($action, ['start', 'submit', 'publictest'])) || ($controllerName == 'Quiz\Controller\ResultController' && in_array($action, ['detail', 'upload', 'download']))))
				return $controller->redirect()->toRoute('login');
		}
	}
}