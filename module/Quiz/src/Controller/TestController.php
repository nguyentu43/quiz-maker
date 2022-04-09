<?php

namespace Quiz\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Quiz\Model\Test;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use Zend\Session\Container;
use Zend\Math\Rand;
use Quiz\Model\Question;

class TestController extends AbstractActionController
{

	private $authManager;
	private $testTable;
	private $questionTable;
	private $resultTable;
	private $userTable;
	private $c_testTable;
	private $path_dir_img;

	public function __construct($authManager, $testTable, $questionTable, $resultTable, $userTable, $c_testTable, $categoryTable, $config)
	{
		$this->authManager = $authManager;
		$this->testTable = $testTable;
		$this->questionTable = $questionTable;
		$this->resultTable = $resultTable;
		$this->userTable = $userTable;
		$this->c_testTable = $c_testTable;
		$this->categoryTable = $categoryTable;
		$this->path_dir_img = $config['app']['root_path'].'public/img/tests/';
	}

	public function indexAction()
	{

		$user = $this->authManager->getUser();
		$user_id = $user->user_id;

		if(!$this->authManager->isGranted('test.list-user'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if($this->authManager->isGranted('user.manage'))
		{
			$user_id = 0;
		}

		$page = (int) $this->params()->fromQuery('page', 1);
		$test_name = $this->params()->fromQuery('test_name' , '');
		$from_date = $this->params()->fromQuery('from_date', '');
		$to_date = $this->params()->fromQuery('to_date', '');
		$is_private = $this->params()->fromQuery('is_private', '');
		$is_enable = $this->params()->fromQuery('is_enable', '');
		$category_test_id = $this->params()->fromQuery('category_test_id', '');

		$query = ['test_name' => $test_name, 'from_date' => $from_date, 'to_date' => $to_date, 'is_private' => $is_private, 'is_enable' => $is_enable, 'category_test_id' => $category_test_id];

		$page = $page < 1 ? 1 : $page;

		$tests = $this->testTable->getAllByUser($user_id, $query, true);
		$tests->setCurrentPageNumber($page);
		$tests->setItemCountPerPage(10);

		foreach ($tests as $t) {
			$t->question_count = $this->testTable->getCountQuestion($t->test_id);
			$t->result_count = $this->resultTable->getByTest($t->test_id)->count();
		}

		if($this->authManager->isGranted('user.manage'))
		{
			foreach ($tests as $t) {
				$t->fullname = $this->userTable->getById($t->user_id)->fullname;
			}
		}

		return new ViewModel(['tests' => $tests, 'query' => $query, 'page' => $page]);
	}

	public function createAction()
	{
		$request = $this->getRequest();
		$user = $this->authManager->getUser();
		$user_id = $user->user_id;

		if(!$this->authManager->isGranted('test.create'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$categories = $this->c_testTable->getAll();

		$test_id_copy = $this->params()->fromQuery('test_id');

		if(!empty($test_id_copy))
		{
			$test = $this->testTable->getById($test_id_copy);

			if(empty($test))
				return new ViewModel(['error' => 'Bạn đề thi không tồn tại']);

			if($test['user_id'] != $user->user_id)
				return new ViewModel(['error' => 'Bạn không có quyền truy cập']);

			$test_id_source = $test['test_id'];

			unset($test['test_id']);
			$test['test_name'] .= ' - (copy '.uniqid().')';
			$test['created_date'] = date('Y-m-d');
			$test['test_code'] = empty($test['test_code']) ? null : $this->generateRandomString();

			if(!empty($test['img']))
			{
				$filename = uniqid().$test['img'];
				copy($this->path_dir_img.$test['img'], $this->path_dir_img.$filename);
				$test['img'] = $filename;
			}
			else
			{
				$test['img'] = null;
			}

			$test_id = $this->testTable->insert((array)$test);

			$result = $this->questionTable->getAllByTest($test_id_source);

			foreach ($result as $item) {
				$this->testTable->addQuestion(['question_id' => $item['question_id'], 'test_id' => $test_id]);
			}

			$this->redirect()->toRoute('test/edit', ['id' => $test_id]);
		}

		if($request->isPost())
		{
			$data = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());

			$img = null;

			if(!empty($data['img']) && $data['img']['error'] == UPLOAD_ERR_OK)
			{
				$img = uniqid().$data['img']['name'];
				move_uploaded_file($data['img']['tmp_name'], $this->path_dir_img.$img);
			}

			$test = [
				'test_name' => $data['test_name'],
				'description' => $data['description'],
				'created_date' => date("Y-m-d h:i:s"),
				'is_login' => !empty($data['is_login']),
				'user_id' => $user_id,
				'is_private' => !empty($data['is_private']),
				'is_enable' => 1,
				'time_limit' => empty($data['time_limit']) ? 0 : $data['time_limit'],
				'attemps' => $data['attemps'],
				'category_test_id' => $data['category_test_id'],
				'shuffle' => !empty($data['shuffle']),
				'test_code' => $this->generateRandomString(),
				'show_point' => !empty($data['show_point']),
				'start_time' => empty($data['start_time']) ? null : date_format(date_create($data['start_time']), 'Y-m-d h:i:s'),
				'end_time' => empty($data['end_time']) ? null : date_format(date_create($data['end_time']), 'Y-m-d h:i:s'),
				'img' => empty($img) ? null : $img
			];

			$test_by_test_name = $this->testTable->getByTestName($test['test_name']);

			if(!$test_by_test_name || $test_by_test_name['user_id'] != $user_id)
			{
				$test_id = $this->testTable->insert($test);

				$this->redirect()->toRoute('test/edit', ['id' => $test_id]);
			}
			else
			{
				return new ViewModel(['alert' => 'Đã tồn tại tên đề thi này. Hãy chọn tên khác', 'categories_test' => $categories]);
			}
		}
		else
		{
			return new ViewModel(['categories_test' => $categories]);
		}
	}

	public function editAction()
	{
		$test_id = $this->params()->fromRoute("id", 0);
		$test = $this->testTable->getById($test_id);
		$user = $this->authManager->getUser();

		if(!$test)
		{
			return new ViewModel(['error' => 'Đề thi này đã bị xoá']);
		}

		if(!$this->authManager->isGranted('test.own.modifier', $test))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$categories = $this->c_testTable->getAll();
		$request = $this->getRequest();

		$message = null;

		if($request->isPost())
		{
			$data = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());

			if(!empty($data['test_name']))
			{
				$testCheck = $this->testTable->getByTestName($data['test_name']);
				if($testCheck && $testCheck['test_id'] != $test_id && $testCheck['user_id'] == $user_id)
				{
					$message = [
						'type' => 'danger',
						'content' => 'Trùng tên đề thi. Hãy chọn một tên khác'
					];
				}
			}

			
			if(empty($data['test_code']))
			{
				$data['test_code'] = $this->generateRandomString();
			}
			else
			{
				$testCheck = $this->testTable->getByTestCode($data['test_code']);
				if($testCheck && $testCheck['test_id'] != $test_id)
				{
					$message = [
						'type' => 'danger',
						'content' => 'Lỗi trùng mã đề. Hãy chọn một mã đề khác'
					];
				}
			}

			$img = $test['img'];

			if(!empty($data['img']) && $data['img']['error'] == UPLOAD_ERR_OK)
			{
				$img = uniqid().$data['img']['name'];
				move_uploaded_file($data['img']['tmp_name'], $this->path_dir_img.$img);

				if(!empty($test['img']))
					unlink($this->path_dir_img.$test['img']);
			}

			if(!empty($data['action']))
			{
				$data['random_from_category'] = null;

				$category = [];

				foreach ($data as $key => $value) {
					unset($data[$key]);
					if($value > 0)
						$category[substr($key, 1)] = intval($value);
				}

				if(count($category))
					$data['random_from_category'] = $category;
				unset($data['action']);
			}
			else
			{
				$data['start_time'] = empty($data['start_time']) ? null : date_format(date_create($data['start_time']), 'Y-m-d h:i:s');
				$data['end_time'] = empty($data['end_time']) ? null : date_format(date_create($data['end_time']), 'Y-m-d h:i:s');
				$data['is_login'] = !empty($data['is_login']);
				$data['is_private'] = !empty($data['is_private']);
				$data['shuffle'] = !empty($data['shuffle']);
				$data['is_enable'] = !empty($data['is_enable']);
				$data['show_point'] = !empty($data['show_point']);
				$data['img'] = $img;
			}

			$data['test_id'] = $test_id;
			$data['modified_date'] = date("Y-m-d h:i:s");

			if($message == null) 
			{
				$this->testTable->update($data);
				$message = [
					'type' => 'success',
					'content' => 'Đã cập nhật thông tin'
				];
			}
		}

		$test = $this->testTable->getById($test_id);

		if($this->authManager->isGranted('test.manage'))
		{
			return new ViewModel([
				'test' => $test,
				'users' => $this->userTable->getAll(),
				'admin' => $user,
				'categories_test' => $categories,
				'categories' => $this->categoryTable->getAllByUser($test['user_id']),
				'message' => $message
			]);
		}

		return new ViewModel([
			'test' => $test,
			'categories_test' => $categories,
			'categories' => $this->categoryTable->getAllByUser($test['user_id']),
			'message' => $message
		]);
	}

	public function deleteAction()
	{
		$request = $this->getRequest();
		$data = $request->getPost();

		if($request->isPost() && isset($data['delete']) && !empty($data['test_id']))
		{
			$test = $this->testTable->getById($data['test_id']);

			if(!$this->authManager->isGranted('test.own.modifier', $test))
			{
				return new JsonModel(['error' => 'Bạn không có quyền truy cập']);
			}

			$this->testTable->delete($data['test_id']);
			return new JsonModel(['ok' => 1]);
		}

		return false;
	}

	public function resumeAction()
	{
		$request = $this->getRequest();
		$result_id = $request->getPost()['result_id'];

		if(!$this->authManager->isGranted('test.start'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$container = new Container('TestStart');

		if(empty($container->result_id) || $container->result_id != $result_id)
		{
			return new JsonModel([
				'ok' => 0, 'message' => 'Lỗi không thể tồn tại kết quả'
			]);
		}

		$answers = $this->resultTable->getListAnswer($result_id);

		$data = [];

		foreach ($answers as $item) {
			$response = json_decode($item['response']);
			$data[$item['question_id']] = $response->data;
		}

		return new JsonModel([
			'ok' => 1, 'data' => $data
		]);
	}

	public function startAction()
	{
		$test_id = $this->params()->fromRoute("id");
		$user = $this->authManager->getUser();
		$request = $this->getRequest();
		$test = $this->testTable->getById($test_id);
		$is_preview = $this->params()->fromQuery('preview', false) && $this->authManager->isGranted('test.own.modifier', $test);

		$container = new Container('TestStart');

		$resume = null;

		if(!empty($container->result_id))
		{
			$result_id = $container->result_id;
			$result = $this->resultTable->getById($result_id);

			if(empty($result))
			{
				unset($container->result_id);
			}
			else
			{
				$test_result = $this->testTable->getById($result['test_id']);
				$container->data = $result['test_id'];
				$resume = [
					'test' => $test_result,
					'result' => $result
				];
			}
		}

		if(!$this->authManager->isGranted('test.start'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if(!$test)
		{
			return new ViewModel(['error' => "Không tồn tại đề thi này"]);
		}

		if(empty($user) && $test['is_login'])
		{
			return new ViewModel(['error' => "Yêu cầu đăng nhập"]);
		}

		if(!$is_preview)
		{

			if($test['is_enable'] == 0)
			{
				return new ViewModel(['error' => "Bạn thi đóng tạm thời"]);
			}

			if(!empty($test['start_time']) && $test['start_time'] <= date('Y-m-d h:i:s') && $test['end_time'] >= date('Y-m-d h:i:s'))
			{
				return new ViewModel(['error' => "Bạn thi đóng tạm thời"]);
			}

			if($test['is_private'] == 1)
			{
				$container = new Container('TestStart');

				if($container->data != $test->test_id)
				{
					return new ViewModel(['error' => "Bạn chưa nhập mã đề thi"]);
				}
			}
		}

		$ip = $request->getServer('REMOTE_ADDR');

		if(!empty($user))
			$attemps = $this->resultTable->getByTestUser($test_id, $user->user_id)->count();
		else
			$attemps = $this->resultTable->getByIp($test_id, $ip)->count();

		$test_user = $this->userTable->getById($test->user_id);
		$test['question_count'] = $this->testTable->getCountQuestion($test['test_id']);
		$test['category_test_name'] = $this->c_testTable->getById($test['category_test_id'])->name;
		$test['result_count'] = $this->resultTable->getByTest($test['test_id'])->count();

		if($request->isPost())
		{
			$data = Json::decode($request->getContent(), Json::TYPE_ARRAY);

			$user_id = null;

			if(empty($user))
			{
				$fullname = $data['fullname'];
			}
			else
			{
				$fullname = $user->fullname;
				$user_id = $user->user_id;
			}

			$information = $data['information'];

			if($test['attemps'] > 0 && $attemps >= $test['attemps'])
			{
				if($container->data)
				{
					unset($container->data);
				}
				return new JsonModel(['ok' => 0,'msg' => "Bạn đã làm đủ số lần cho phép"]);
			}

			if($test['is_enable'] == 0 || (!empty($test['start_time']) && ($test['start_time'] >= date("Y-m-d h:i:s") || $test['end_time'] >= date("Y-m-d h:i:s"))))
			{
				return new JsonModel(['ok' => 0,'msg' => "Đề thi đã bị đóng tạm thời"]);
			}

			$result_id = $this->resultTable->insert([
				'test_id' => $test_id,
				'user_id' => $user_id,
				'time_start' => date('Y-m-d H:i:s'),
				'count' => 0,
				'fullname' => $fullname,
				'information' => $information,
				'ip_address' => $ip
			]);

			$container->result_id = $result_id;

			$questions = $data['questions'];

			foreach ($questions as $question_id) {
				$this->resultTable->insertAnswer(['result_id' => $result_id, 'response' => null, 'question_id' => $question_id]);
			}

			return new JsonModel(['ok' => 1, 'result_id' => $result_id]);
		}

		$preview = false;

		if($this->authManager->isGranted('test.own.modifier', $test) && !empty($this->params()->fromQuery('preview')))
		{
			$preview = true;
		}

		return new ViewModel(['test' => $test, 'fullname' => $test_user['fullname'], 'preview' => $preview, 'attemps' => $attemps, "ranks" => $this->resultTable->getRankTest($test->test_id), 'user' => $user, 'ip' => $ip, 'resume' => $resume ]);
	}

	public function submitAction()
	{
		$request = $this->getRequest();
		$user = $this->authManager->getUser();

		$ip = $request->getServer('REMOTE_ADDR');

		if(!$this->authManager->isGranted('test.start'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if($request->isPost())
		{
			$data = Json::decode($request->getContent(), Json::TYPE_ARRAY);

			$test = $this->testTable->getById($data['test_id']);

			$container = new Container('TestStart');
			unset($container->data);

			if($test['is_enable'] == 0 || (!empty($test['start_time']) && ($test['start_time'] > date("Y-m-d h:i:s") || $test['end_time'] > date("Y-m-d h:i:s"))))
			{
				return new JsonModel(['ok' => 0, 'msg' => "Đề thi đã đóng"]);
			}

			if(!empty($user))
				$attemps = $this->resultTable->getByTestUser($test['test_id'], $user->user_id)->count();
			else
				$attemps = $this->resultTable->getByIp($test_id, $ip)->count();

			if($test['attemps'] > 0 && $attemps >= $test['attemps'] && !$container->result_id)
			{
				return new JsonModel(['ok' => 0, 'msg' => "Bạn đã làm đủ số lần cho phép"]);
			}

			if(!empty($data['action']))
			{
				$this->resultTable->submit($data['result_id']);
				$this->markResult($data['result_id']);	
				unset($container->result_id);
				return new JsonModel(['ok' => 1]);
			}
			else
			{
				if($data['response'] != null)
					$response = json_encode(['data' => $data['response'] ]);
				else
					$response = null;
				$this->resultTable->updateAnswer(['response' => $response], [ 'result_id' => $data['result_id'], 'question_id' => $data['question_id'] ]);
				return new JsonModel(['ok' => 1]);
			}
		}
	}

	public function markAction()
	{
		$user = $this->authManager->getUser();
		$result_id = $this->params()->fromQuery('result_id');
		$url = $this->params()->fromQuery('callback_url');

		if(empty($result_id) && empty($url))
		{
			$this->redirect()->toRoute('home');
		}

		$result = $this->resultTable->getById($result_id);
		$test = $this->testTable->getById($result['test_id']);

		if(!$this->authManager->isGranted('test.own.mark', $test))
		{
			$this->redirect()->toRoute('home');
		}

		$this->markResult($result_id);
		$this->redirect()->toUrl($url);
	}

	private function markResult($result_id)
	{
		$listAnswer = $this->resultTable->getListAnswer($result_id);
		$count = 0;

		foreach($listAnswer as $answer)
		{
			$question = new Question();
			$question->exchangeArray($this->questionTable->getById($answer['question_id']));
			$response = json_decode($answer['response']);
			$is_correct = false;

			if(in_array(intval($question->question_type_id), [7, 9]))
			{
				if(!(!empty($response->point) && $response->point > -1))
					$response->point = -1;
				else
					$is_correct = true;
			}
			else
				$response->point = 0;

			switch(intval($question->question_type_id))
			{
				case 1:
				case 2:
					$list = [];
					foreach($question->question_options as $option)
					{
						if($option->is_correct)
							$list[] = $option->id;
					}
					if(count(array_diff($response->data, $list)) == 0)
						$is_correct = true;
					break;
				case 3:
					$list = array_map(function($item) { return $item->id; }, $question->question_options);

					if(implode(';', $list) === implode(';', $response->data))
						$is_correct = true;

					break;
				case 4:
					$i = 0;
					foreach($question->question_options as $option)
					{
						$from = $option->source->id;
						$pair = [ $from, $option->target->id ];

						foreach($response->data as $item)
						{
							if($item->from == $from)
							{
								if(count(array_diff($pair, [ $item->from, $item->to ])) == 0)
									$i++;
							}
						}
					}

					if($i == count($question->question_options))
						$is_correct = true;
					break;
				case 5:
					$i = 0;
					$is_correct = true;
					foreach($question->question_options as $option)
					{
						foreach($response->data as $item)
						{
							if($item->id === $option->id)
							{
								if(count(array_diff($item->data, explode(';', $option->group_items))) > 0)
								{
									$is_correct = false;
									break;
								}
							}
						}

						if($is_correct == false)
							break;
					}
					break;
				case 6:
					foreach($question->question_options as $option)
					{
						if(mb_strtolower($response->data, 'UTF-8') === mb_strtolower($option->option_text, 'UTF-8'))
						{
							$is_correct = true;
							break;
						}
					}
					break;
				case 8:
					$is_correct = true;

					foreach($question->question_options as $option)
					{
						$fill_words = array_map(function($value) { return mb_strtolower($value, 'UTF-8'); }, $option->fill_words);

						foreach($response->data as $item)
						{
							if($option->id === $item->id)
							{
								$data = array_map(function($value) { return mb_strtolower($value, 'UTF-8'); }, $item->data);

								if(count(array_diff($data, $fill_words)) > 0)
								{
									$is_correct = false;
									break;
								}
							}
						}

						if($is_correct == false)
							break;
					}
				case 7:
				case 9:
					break;
			}

			if($is_correct)
			{
				$response->point = intval($question->question_settings->point);
				$count++;
			}

			$this->resultTable->updateAnswer([ 'response' => json_encode($response) ], [ 'result_id' => $result_id, 'question_id' => $answer['question_id'] ]);
		}

		$this->resultTable->update(['count' => $count, 'result_id' => $result_id]);
	}

	public function publictestAction()
	{

		$test_id = $this->params()->fromQuery('detail');
		$current_user = $this->authManager->getUser();
		$view = new ViewModel();
		$test = $this->testTable->getById($test_id);

		if(!empty($test_id))
		{
			if(!$this->authManager->isGranted('test.read-detail'))
			{
				return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
			}
			else
			{
				$test = $this->testTable->getById($test_id);

				if(!$test || $test->is_private == 1)
				{
					$view->setVariable('error', 'Lỗi đường dẫn');
					return $view;
				}

				$arr = $this->questionTable->getAllByTest($test_id);
		
				$questions = [];

				foreach($arr as $item)
				{
					$ques = new Question();
					$ques->exchangeArray($item);
					$questions[] = $ques;
				}

				$view->setVariable('test', $test);
				$view->setVariable('questions', $questions);
				$view->setVariable('detail', true);
				$view->setVariable('categories_test', $this->c_testTable->getAll());
				return $view;
			}
		}

		$author_id = $this->params()->fromQuery('author_id', '');
		$category_test_id = $this->params()->fromQuery('category_test_id', '');
		$keywords = $this->params()->fromQuery('keywords', '');

		if(!empty($author_id))
		{
			$view->setVariable('author', $this->userTable->getById($author_id));
		}

		$query = [
			'user_id' => $author_id,
			'category_test_id' => $category_test_id,
			'keywords' => $keywords
		];

		$page = (int) $this->params()->fromQuery('page', 1);
		$page = $page < 1 ? 1 : $page;

		$tests = $this->testTable->searchTestPublic($query, true);
		$tests->setCurrentPageNumber($page);
		$tests->setItemCountPerPage(10);

		foreach ($tests as $t) {
			$t->question_count = $this->testTable->getCountQuestion($t->test_id);
			$t->fullname = $this->userTable->getById($t->user_id)->fullname;
			$t->result_count = $this->resultTable->getByTest($t->test_id)->count();
		}

		if($current_user)
			$view->setVariable('user', $current_user);
		$view->setVariable('tests', $tests);
		$view->setVariable('query', $query);
		$view->setVariable('page', $page);
		$view->setVariable('categories_test', $this->c_testTable->getAll());

		return $view;
	}

	public function ajaxAction()
	{
		$user = $this->authManager->getUser();
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);

		switch ($data['action'])
		{
			case 'searchTestPublic':
				if(!$this->authManager->isGranted('test.read-detail'))
				{
					return JsonModel(['ok' => 0, 'msg' => 'Bạn chưa được cấp quyền']);
				}
				break;
			case 'getTestByQuestion':
				$question = $this->questionTable->getById($data['question_id']);
				if(!$this->authManager->isGranted('question.own.modifier', $question))
				{
					return JsonModel(['ok' => 0, 'msg' => 'Bạn chưa được cấp quyền']);
				}
				break;
			case 'addQuestion':
			case 'removeQuestion':
			case 'swapIndexQuestion':
				$test = $this->testTable->getById($data['data']['test_id']);
				if(!$this->authManager->isGranted('test.own.modifier', $test))
				{
					return JsonModel(['ok' => 0, 'msg' => 'Bạn chưa được cấp quyền']);
				}
				break;
			default:
				return new JsonModel(['ok' => 0]);
		}

		switch ($data['action'])
		{
			case 'searchTestPublic';
				return $this->searchTestPublic($data, $user);
			case 'addQuestion':
				return $this->addQuestion($data, $user);
			case 'removeQuestion':
				return $this->removeQuestion($data, $user);
			case 'getTestByQuestion':
				return $this->getTestByQuestion($data, $user);
			case 'swapIndexQuestion':
				return $this->swapIndexQuestion($data, $user);
			default:
				return new JsonModel(['ok' => 0]);
		}
	}

	private function searchTestPublic($data, $user){

		$query = ['keywords' => $data['search_name'], 'desc' => $data['search_name'], 'limit' => 10, 'offset' => $data['offset'], 'except_user_id' => $user->user_id];

		$tests = $this->testTable->searchTestPublic($query, false);

		$result = [];

		foreach ($tests as $t) {
			$result[] = [
				'test_id' => $t['test_id'],
				'test_name' => $t['test_name'],
				'user_id' => $t['user_id'],
				'name_create' => $this->userTable->getById($t['user_id'])['fullname'],
				'question_count' => $this->testTable->getCountQuestion($t['test_id']),
				'description' => $t['description']
			];
		}

		return new JsonModel(['ok' => 1, 'tests' => $result]);
	}

	private function getTestByQuestion($data, $user)
	{

		if(!$this->authManager->isGranted('question.manage'))
			$user_id = $user->user_id;
		else
			$user_id = null;


		if(empty($user_id))
			$result = $this->testTable->checkQuestionByTest($user_id, $data['question_id'], true, true);
		else
			$result = $this->testTable->checkQuestionByTest($user_id, $data['question_id'], true);

		$arr = [];

		foreach($result as $item)
		{
			$arr[] = [
				'test_id' => $item['test_id'],
				'test_name' => $item['test_name'],
				'in_test' => true
			];
		}

		if(empty($user_id))
			$result = $this->testTable->checkQuestionByTest($user_id, $data['question_id'], false, true);
		else
			$result = $this->testTable->checkQuestionByTest($user_id, $data['question_id'], false);

		foreach($result as $item)
		{
			$arr[] = [
				'test_id' => $item['test_id'],
				'test_name' => $item['test_name'],
				'in_test' => false
			];
		}

		return new JsonModel(['data' => $arr]);
	}

	private function addQuestion($data)
	{
		if(isset($data['data']['list']))
		{
			foreach ($data['data']['list'] as $item) {
				$this->testTable->addQuestion(['test_id' => $data['data']['test_id'], 'question_id' => $item]);
			}
			return new JsonModel(['ok' => 1]);
		}

		$this->testTable->addQuestion($data['data']);

		return new JsonModel(['ok' => 1]);
	}

	private function removeQuestion($data)
	{
		$this->testTable->removeQuestion($data['data']);
		return new JsonModel(['ok' => 1]);
	}

	private function swapIndexQuestion($data)
	{
		$this->testTable->swapIndexQuestion($data['data']['test_id'], $data['data']['question_id_a'], $data['data']['index_a'], $data['data']['question_id_b'], $data['data']['index_b']);
		return new JsonModel(['ok' => 1]);
	}

	private function generateRandomString() {
	    return Rand::getString(12);
	}
}
