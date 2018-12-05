<?php

namespace Quiz\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Quiz\Model\Question;

class ResultController extends AbstractActionController
{
	private $resultTable;
	private $testTable;
	private $classTable;
	private $authManager;
	private $questionTable;
	private $userTable;
	private $rootPath;

	public function __construct($authManager, $resultTable, $testTable, $questionTable, $userTable, $config)
	{
		$this->authManager = $authManager;
		$this->resultTable = $resultTable;
		$this->testTable = $testTable;
		$this->questionTable = $questionTable;
		$this->userTable = $userTable;
		$this->rootPath = $config['app']['root_path'];
	}

	public function indexAction()
	{
		$test_id = $this->params()->fromRoute('test_id');
		$user = $this->authManager->getUser();
		$params = $this->params()->fromQuery('by');

		if(isset($test_id))
		{
			$test = $this->testTable->getById($test_id);

			if($this->authManager->isGranted('test.own.modifier', $test))
			{
				$result = $this->resultTable->getByTest($test_id);

				return new ViewModel(['result' => $result, 'test' => $test]);
			}
			else
			{
				return new ViewModel(['error' => 'Bạn không có quyền xem']);
			}
		}
		else
		{
			return new ViewModel(['error' => 'Không tồn tại đề thi này']);
		}
	}

	public function detailAction()
	{
		$result_id = $this->params()->fromRoute("result_id");
		$result = $this->resultTable->getById($result_id);

		if($this->getRequest()->isPost())
		{
			$data = $this->getRequest()->getPost();

			$test = $this->testTable->getById($result['test_id']);

			if(!$this->authManager->isGranted('test.own.mark', $test))
			{
				return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
			}

			if(!empty($data['btnMark']))
			{
				$this->redirect()->toRoute('test/mark', [], [ 'query' => [ 'result_id' => $result_id, 'callback_url' => $this->url()->fromRoute('result/detail', [ 'result_id' => $result_id ]) ] ]);
				return;
			}

			if(!empty($data['question_id']))
			{

				$answer = $this->resultTable->getAnswer($result_id, $data['question_id']);
				$response = json_decode($answer['response']);
				$response->point = floatval($data['point']);
				$this->resultTable->updateAnswer([ 'response' => json_encode($response) ], [ 'result_id' => $result_id, 'question_id' => $data['question_id'] ]);

				$this->resultTable->updateCount($result_id);

				$this->redirect()->toRoute('result/detail', [ 'result_id' => $result_id ], [ 'fragment' => 'q'.$data['question_id'] ]);
				return;
			}
		}

		if(!$result)
		{
			return new ViewModel(['error' => 'Không tồn tại kết quả này']);
		}

		$user = $this->authManager->getUser();
		$test = $this->testTable->getById($result['test_id']);

		if(empty($result['user_id']))
		{
			if(!$this->authManager->isGranted('result.detail'))
			{
				return new ViewModel(['error' => 'Bạn không có quyền xem kết quả này']);
			}
		}
		else
		{
			if(!$this->authManager->isGranted('result.own.detail', $result) && !$this->authManager->isGranted('test.own.mark', $test))
			{
				return new ViewModel(['error' => 'Bạn không có quyền xem kết quả này']);
			}
		}

		$arr = $this->questionTable->getAllByResult($result_id);
		
		$questions = [];

		$total_point = 0;
		foreach($arr as $item)
		{
			$ques = new Question();
			$ques->exchangeArray($item);
			$total_point += $ques->question_settings->point;
			$questions[] = $ques;
		}

		$total_question = $arr->count();

		$arr = $this->resultTable->getListAnswer($result_id);
		$answers = [];

		$result['point'] = 0;
		foreach ($arr as $item) {
			$response = json_decode($item['response']);
			$answers[$item['question_id']] = $response;
			$result['point'] += max($response->point, 0);
		}

		return new ViewModel([
			'user_result' => $this->userTable->getById($result['user_id']),
			'result' => $result,
			'questions' => $questions,
			'answers' => $answers,
			'test' => $test,
			'total_question' => $total_question,
			'total_point' => $total_point,
			'edit' => $this->authManager->isGranted('test.own.mark', $test)
		]);
	}

	public function deleteAction()
	{
		$request = $this->getRequest();

		if($request->isPost())
		{
			$data = $request->getPost();

			if($data['delete'] == 'list')
			{
				$test_id = $data['test_id'];

				$test = $this->testTable->getById($test_id);
				if(!$this->authManager->isGranted('test.own.modifier', $test))
				{
					return false;
				}

				$this->resultTable->deleteList($test_id);
				return new JsonModel(['ok' => 1]);
			}
			else if($data['delete'] == 'item')
			{
				$result_id = $data['result_id'];
				$test_id = $this->resultTable->getById($result_id)['test_id'];
				$test = $this->testTable->getById($test_id);
				if(!$this->authManager->isGranted('test.own.modifier', $test))
				{
					return false;
				}

				$this->resultTable->delete($result_id);
				return new JsonModel(['ok' => 1]);
			}
		}

		return false;
	}

	public function uploadAction()
	{

		$request = $this->getRequest();
		$data = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
		$result_id = $data['result_id'];

		if($result_id != -1)
		{
			$result = $this->resultTable->getById($result_id)['test_id'];

			if(!empty($result['user_id']))
				if(!$this->authManager->isGranted('test.own.modifier', $result))
				{
					return new JsonModel(['ok' => 0, 'message' => 'Bạn chưa cấp quyền']);
				}
		}

		$path = $this->rootPath.'resources/results';

		if(!empty($data['action']) && $data['action'] == 'delete')
		{
			if(!is_file("$path/{$data['result_id']}/{$data['name']}"))
				return new JsonModel(['ok' => 0, 'message' => 'Lỗi file không tồn tại']);

			if(is_dir("$path/{$data['result_id']}") && unlink("$path/{$data['result_id']}/{$data['name']}"))
				return new JsonModel(['ok' => 1]);
			else
				return new JsonModel(['ok' => 0, 'message' => 'Lỗi xoá file']);
		}
		else
		{
			if(!empty($data['file']) && $data['file']['error'] == UPLOAD_ERR_OK)
			{
				if(!is_dir("$path/{$data['result_id']}"))
					mkdir("$path/{$data['result_id']}");
				$filename = uniqid().$data['file']['name'];
				move_uploaded_file($data['file']['tmp_name'], "$path/{$data['result_id']}/{$filename}");
			}
			else
			{
				return new JsonModel([ 'ok' => 0, 'message' => 'Lỗi tải file']);
			}
		}

		return new JsonModel([ 'ok' => 1, 'result_id' => $data['result_id'], 'file' => [ 'name' => $filename, 'path' => $this->url()->fromRoute('result/download', [], ['query' => [ 'result_id' => $data['result_id'], 'name' => $filename ]]), 'size' => filesize("$path/{$data['result_id']}/{$filename}") ] ]);
	}

	public function downloadAction()
	{
		$result_id = $this->params()->fromQuery('result_id');
		$name = $this->params()->fromQuery('name');

		$result = $this->resultTable->getById($result_id)['test_id'];

		if(!empty($result['user_id']))
			if(!$this->authManager->isGranted('test.own.modifier', $result))
			{
				$this->getResponse()->setStatusCode(403);
					return;
			}

		if(empty($result_id) && empty($name))
			$this->getResponse()->setStatusCode(404);

		$path = $this->rootPath.'resources/results';
		$file = "$path/{$result_id}/{$name}";

		if(!is_file($file))
		{
			$this->getResponse()->setStatusCode(404);
			return;
		}

	    $response = new \Zend\Http\Response\Stream();
	    $response->setStream(fopen($file, 'r'));
	    $response->setStatusCode(200);
	    $response->setStreamName(basename($file));
	    $headers = new \Zend\Http\Headers();
	    $headers->addHeaders(array(
	        'Content-Disposition' => 'attachment; filename="' . basename($file) .'"',
	        'Content-Type' => 'application/octet-stream',
	        'Content-Length' => filesize($file),
	        'Expires' => '@0', // @0, because zf2 parses date as string to \DateTime() object
	        'Cache-Control' => 'must-revalidate',
	        'Pragma' => 'public'
	    ));
	    $response->setHeaders($headers);
	    return $response;
	}
}