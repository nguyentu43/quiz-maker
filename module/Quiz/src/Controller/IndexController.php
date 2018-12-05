<?php

namespace Quiz\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{

	private $authManager;
	private $testTable;
	private $resultTable;
	private $questionTable;
	private $user;
	private $c_testTable;

	public function __construct($authManager, $testTable, $resultTable, $questionTable, $userTable, $c_testTable)
	{
		$this->authManager = $authManager;
		$this->user = $authManager->getUser();
		$this->testTable = $testTable;
		$this->resultTable = $resultTable;
		$this->questionTable = $questionTable;
		$this->userTable = $userTable;
		$this->c_testTable = $c_testTable;
	}

	public function indexAction()
	{
		$arr = $this->c_testTable->getAll();

		$categories_test = [];

		foreach ($arr as $item) {
			$arr_test = $this->testTable->getByCategory($item->id);

			$tests = [];
			foreach ($arr_test as $t) {
				$t['question_count'] = $this->testTable->getCountQuestion($t['test_id']);
				$t['fullname'] = $this->userTable->getById($t['user_id'])->fullname;
				$t['result_count'] = $this->resultTable->getByTest($t['test_id'])->count();
				$tests[] = $t;
			}

			$item->tests = $tests;

			$categories_test[] = $item;
		}

		$arr = $this->testTable->getTestRecently();
		$tests_recently = [];
		foreach ($arr as $t) {
			$t['question_count'] = $this->testTable->getCountQuestion($t['test_id']);
			$t['result_count'] = $this->resultTable->getByTest($t['test_id'])->count();
			$tests_recently[] = $t;
		}

		$arr = $this->testTable->getTestTop();
		$tests_top = [];
		foreach ($arr as $t) {
			$t['question_count'] = $this->testTable->getCountQuestion($t['test_id']);
			$t['result_count'] = $this->resultTable->getByTest($t['test_id'])->count();
			$tests_top[] = $t;
		}

		$user = $this->authManager->getUser();

		return new ViewModel(['categories_test' => $categories_test, 'user' => $user, 'tests_recently' => $tests_recently, 'tests_top' => $tests_top]);
	}

	public function enteridAction()
	{

		if(!$this->authManager->isGranted('test.start'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$view = new ViewModel();

		$request = $this->getRequest();

		if($request->isPost())
		{
			$test_code = $request->getPost()['test_code'];
			$test = $this->testTable->getByTestCode($test_code);

			if(!$test || ($test && $test['is_private'] == 0))
			{
				$error = "Mã đề không tồn tại";
				$view->setVariable('error', $error);
			}
			else
			{
				$container = new Container('TestStart');
				$container->data = $test['test_id'];
				$this->redirect()->toRoute('test/start', ['id' => $test['test_id']]);
			}
		}

		return $view;
	}

	public function resultAction()
	{
		
		if(!$this->authManager->isGranted('result.list-user'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$user_id = $this->authManager->getUser()->user_id;
		$result = $this->resultTable->getByUser($user_id);
		$view = new ViewModel();
		$view->setVariable('result', $result);
		return $view;
	}
}