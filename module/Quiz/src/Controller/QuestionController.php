<?php

namespace Quiz\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use User\Auth\AuthManager;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Quiz\Model\Question;
use Quiz\Model\Category;
use Zend\Session\Container;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Quiz\Form\UploadForm;
use Zend\InputFilter\FileInput;
use Zend\InputFilter\InputFilter;

class QuestionController extends AbstractActionController
{

	private $questionTable;
	private $categoryTable;
	private $testTable;
	private $authManager;
	private $userTable;

	public function __construct($questionTable, $categoryTable, $testTable, $authManager, $userTable)
	{
		$this->questionTable = $questionTable;
		$this->categoryTable = $categoryTable;
		$this->testTable = $testTable;
		$this->authManager = $authManager;
		$this->userTable = $userTable;
	}

	public function indexAction()
	{
		$user = $this->authManager->getUser();

		if(!$this->authManager->isGranted('question.list-user'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if($this->authManager->isGranted('question.manage'))
		{
			$arr = $this->userTable->getAll();
			$users = [];
			foreach ($arr as $u) {
				if($u['role_id'] != 2)
					$users[] = $u;
			}

			return new ViewModel(['users' => $users, 'admin' => $user]);
		}
	}

	public function getlistAction()
	{
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);
		$user = $this->authManager->getUser();

		if(empty($data['action']))
		{
			return new JsonModel(['ok' => 0, 'msg' => 'Lỗi thiếu tham số']);
		}

		if($data['action'] == 'getAll')
		{
			if($this->authManager->isGranted('question.manage'))
				$arr = $this->questionTable->getAll();
			else
				$arr = $this->questionTable->getAllByUser($user->user_id);
			$questions = $this->getListQuestion($arr);
		}
		else if($data['action'] == 'edit')
		{
			$arr = $this->questionTable->getAllByTest($data['test_id']);
			$questions = $this->getListQuestion($arr);
		}
		else if($data['action'] == 'import_by_category')
		{
			$test = $this->testTable->getById($data['test_id']);
			$arr = $this->questionTable->getQuestionByCategory($data['category_id'], $data['test_id'], $test['user_id']);
			$questions = $this->getListQuestion($arr);
		}
		else if($data['action'] == 'import_by_test')
		{
			$arr = $this->questionTable->getAllByTest($data['test_id']);
			$questions = $this->getListQuestion($arr);
		}
		else
		{
			$test = $this->testTable->getById($data['test_id']);

			if($test['is_login'])
			{
				if($test['is_private'] && !$this->authManager->isGranted('test.own.modifier', $test))
				{
					$container = new Container('TestStart');

					if($container->data != $test->test_id)
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Bạn không có quyền xem đề thi này']);
					}
				}
			}
			else
			{
				if($test['is_private'] && !$this->authManager->isGranted('test.own.modifier', $test))
				{
					$container = new Container('TestStart');

					if($container->data != $test->test_id)
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Bạn không có quyền xem đề thi này']);
					}
				}
			}

			if($data['action'] == 'resume')
			{
				$arr = $this->questionTable->getAllByResult($data['result_id']);
			}
			else
			{
				$arr = $this->questionTable->getAllByTest($data['test_id']);
			}

			$questions = $this->getListQuestion($arr);

			if($data['action'] == 'start' || $data['action'] == 'resume')
			{
				if($data['action'] == 'start')
				{

					if(!empty($test['random_from_category']))
					{
						$random = json_decode($test['random_from_category'], true);

						foreach($random as $key => $value)
						{
							$questions = array_merge($questions, $this->getListQuestion($this->questionTable->getRandomByCategory($key, $test['test_id'], $value)));
						}
					}
				}

				foreach($questions as $question)
				{
					switch(intval($question->question_type_id))
					{
						case 1:
						case 2:
							foreach($question->question_options as $option)
							{
								unset($option->is_correct);
							}
							break;
						case 3:
							foreach($question->question_options as $option)
							{
								unset($option->index);
							}

							shuffle($question->question_options);

							break;
						case 4:
							$source = [];
							$target = [];
							foreach($question->question_options as $option)
							{
								$source[] = $option->source;
								$target[] = $option->target;
							}

							shuffle($source);
							shuffle($target);

							$question->question_options = [ 'source' => $source, 'target' => $target ];
							break;
						case 5:
							$group_text = [];
							$group_items = [];
							foreach($question->question_options as $option)
							{
								$group_text[] = [ 'id' => $option->id, 'data' => $option->group_text];
								$group_items = array_merge($group_items, explode(';', $option->group_items));
							}

							shuffle($group_text);
							shuffle($group_items);

							$question->question_options = [
								'group_text' => $group_text,
								'group_items' => $group_items
							];
							break;
						case 6:
							$question->question_options = [];
							break;
						case 8:
							foreach($question->question_options as $option)
							{
								unset($option->fill_words);
							}
							break;
						case 7:
						case 9:
							break;
					}
				}

				if($test->shuffle)
					shuffle($questions);
			}
		}

		return new JsonModel(['ok' => 1, 'questions' => $questions]);
	}

	private function getListQuestion($arr)
	{
		$questions = [];
		$i = 1;
		foreach($arr as $item)
		{
			$ques = new Question();
			$ques->exchangeArray($item);
			$ques->index = $i;
			$i++;
			$questions[] = $ques;
		}

		return $questions;
	}

	public function getAction()
	{
		$result = $this->questionTable->getById($id);
		return new JsonModel(['question' => $result]);
	}

	public function insertAction()
	{
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);
		$user = $this->authManager->getUser();
		$user_id = $user->user_id;

		if(!$this->authManager->isGranted('question.create'))
		{
			return new JsonModel(['ok' => 0, 'msg' => 'Bạn không có quyền truy cập']);
		}

		if($this->authManager->isGranted('question.manage'))
		{
			if(isset($data['user_id']))
				$user_id = $data['user_id'];
		}

		if(isset($data['list']))
		{
			$category_id = $this->categoryTable->getByName('Chưa phân loại', $user_id)['category_id'];

			if(!isset($category_id))
			{
				$category_id = $this->categoryTable->insert([
					'category_name' => 'Chưa phân loại',
					'user_id' => $user_id
				]);
			}

			try
			{
				foreach($data['list'] as $id)
				{
					$question = $this->questionTable->getById($id);

					$question_id = $this->questionTable->insert([
						'user_id' => $user_id,
						'category_id' => $category_id,
						'question_type_id' => $question['question_type_id'],
						'question_text' => $question['question_text'],
						'question_options' => json_decode($question['question_options']),
						'question_settings' => json_decode($question['question_settings'])
					]);

					if(isset($data['test_id']))
					{
						$this->testTable->addQuestion(['test_id' => $data['test_id'], 'question_id' => $question_id]);
					}
				}
			}
			catch(InvalidQueryException $e)
			{
				return new JsonModel(['ok' => 0, 'msg' => 'Lỗi truy vấn']);
			}

			return new JsonModel(['ok' => 1]);
		}

		$data = $data['question'];

		$data['user_id'] = $user_id;

		$question_id = $this->questionTable->insert($data);

		return new JsonModel(['ok' => 1, 'question_id' => $question_id]);
	}

	public function updateAction()
	{
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);
		$user = $this->authManager->getUser();
		$question_id = $data['question']['question_id'];
		$question = $this->questionTable->getById($question_id);

		if(!$this->authManager->isGranted('question.own.modifier', $question))
		{
			new JsonModel(['ok' => 1, 'msg' => 'Bạn chưa cấp quyền']);
		}

		$this->questionTable->update($data['question']);

		return new JsonModel(['ok' => 1]);
	}

	public function deleteAction()
	{
		$user = $this->authManager->getUser();
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);

		$question = $this->questionTable->getById($data['question_id']);

		if(!$this->authManager->isGranted('question.own.modifier', $question))
		{
			return new ViewModel(['ok' => 0, 'msg' => 'Bạn không có quyền truy cập']);
		}

		if($this->questionTable->checkQuestionByAnwser($data['question_id'])->count() > 0)
		{
			return new JsonModel(['ok' => 0, 'msg' => 'Câu hỏi này đã được sử dụng trong đề thi. Bạn xoá các kết quả và bỏ câu hỏi này khỏi đề để xoá được câu hỏi này.']);
		}

		if($this->testTable->checkQuestionByTest($question['user_id'], $data['question_id'], true)->count() > 0)
		{
			return new JsonModel(['ok' => 0, 'msg' => 'Bạn cần bỏ câu hỏi này khỏi đề.']);
		}

		$this->questionTable->delete($data['question_id']);
		return new JsonModel(['ok' => 1]);
	}

	public function importAction()
	{

		if(!$this->authManager->isGranted('question.create'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$max_size = 1024*1024*2;
		$form = new UploadForm();

		$view = new ViewModel;
		$view->setVariable('form', $form);

		$request = $this->getRequest();

		if($request->isPost())
		{
			$post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());

			if(!empty($post['file']))
			{
				$file = $post['file'];
				$type = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];

				if(!in_array($file['type'], $type))
				{
					$error = 'Tập tin không đúng định dạng';
				}
				else if($file['error'] != UPLOAD_ERR_OK)
				{
					switch($file['error'])
					{
						case UPLOAD_ERR_NO_FILE:
							$error = 'Lỗi gửi tập tin';
							break;
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$error = 'Lỗi tập tin vượt quá kích thước cho phép';
							break;
						default:
							$error = 'Đã xảy ra lỗi';
					}
				}
				else if($file['size'] > $max_size)
				{
					$error = 'Lỗi tập tin vượt quá kích thước cho phép';
				}

				if(!isset($error))
				{
					$path = $file['tmp_name'];
					$objFile = \PhpOffice\PhpSpreadsheet\IOFactory::identify($path);
					$objData = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($objFile);

					$objData->setReadDataOnly(true);
					$objPHPExcel = $objData->load($path);

					$sheet = $objPHPExcel->setActiveSheetIndex(0);
					$totalRow = $sheet->getHighestRow();
					$lastColumn = $sheet->getHighestColumn();
					$totalColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);

					for($i = $totalRow; $i>=2; --$i)
					{
						$blank = true;
						for($j = 1; $j<=$totalColumn; ++$j)
						{
							if($sheet->getCellByColumnAndRow($j, $i)->getValue() != "")
							{
								$blank = false;
								break;
							}
						}
						if(!$blank)
						{
							$totalRow = $i;
							break;
						}
					}

					$question_type = ['một lựa chọn' => 1, 'nhiều lựa chọn' => 2];
					$colStd = 5;

					try
					{

						if($totalRow < 2)
						{
							throw new \Exception("Lỗi bảng tính không có câu hỏi");
						}

						$user_id = $this->authManager->getUser()->user_id;
						$result = $this->categoryTable->getAllByUser($user_id);
						$questions = [];
						$category = [];
						foreach ($result as $row) {
							$category[mb_strtolower($row['category_name'], 'utf8')] = $row['category_id'];
						}
					
						for($i=2; $i<=$totalRow; ++$i)
						{
							$category_name = $sheet->getCellByColumnAndRow(1, $i)->getValue();

							if(strval($category_name) == "")
								throw new \Exception("Lỗi danh mục chưa được điền (B$i)");

							if(!isset($category[mb_strtolower($category_name, 'utf8')]))
							{
								$category_id = $this->categoryTable->insert([
									'user_id' => $user_id,
									'category_name' => $category_name
								]);

								$category[mb_strtolower($category_name, 'utf8')] = $category_id;
							}

							$questions[] = [
								'category_id' => $category[mb_strtolower($category_name, 'utf8')],
								'user_id' => $user_id,
								'question_options' => [],
								'question_settings' => [
									'point' => 1,
									'feedback' => 'Phản hồi đáp án'
								]
							];
						}

						for($i=2; $i<=$totalRow; ++$i)
						{
							$type = $sheet->getCellByColumnAndRow(2, $i)->getValue();

							if(strval($type) == "")
								throw new \Exception("Lỗi loại câu phải khác rỗng (B$i)");

							if(!isset($question_type[mb_strtolower($type)]))
							{
								throw new \Exception("Lỗi loại câu không hợp lệ (B$i)");
							}

							$questions[$i - 2]['question_type_id'] = $question_type[mb_strtolower($type)];
						}

						//check empty question content
						for($i=2; $i<=$totalRow; ++$i)
						{
							$question_text = $sheet->getCellByColumnAndRow(3, $i)->getValue();

							if(strval($question_text) == "")
								throw new \Exception("Lỗi nội dung câu hỏi khác rỗng (C$i)");

							$questions[$i - 2]['question_text'] = $question_text;
						}

						//check option
						for($i=2; $i<=$totalRow; ++$i)
						{
							$correct = $sheet->getCellByColumnAndRow(4, $i)->getValue();
							if(strval($correct) == "")
								throw new \Exception("Lỗi đáp án đúng phải khác rỗng (D$i)");

							if(!preg_match('/^[a-zA-z]+$/', str_replace(' ', '', $correct)))
								throw new \Exception("Lỗi giá trị đáp án dúng phải là chữ (D$i)");

							$correct = trim(strtoupper($correct));
							$list_correct = explode(" ", $correct);
							sort($list_correct);

							if(count($list_correct) > 1 && $questions[$i - 2]['question_type_id'] == 1)
							{
								throw new \Exception("Lỗi câu hỏi một lựa chọn chỉ có một đáp án (D$i)");
							}

							$options = [];

							$maxColInRow = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn($i));

							$tmpMax = $maxColInRow;
							for($j = $colStd; $j <= $maxColInRow; ++$j)
							{
								if($sheet->getCellByColumnAndRow($j, $i)->getValue() == "")
								{
									$tmpMax--;
								}
							}

							$maxColInRow = $tmpMax;

							if(count($list_correct) > $maxColInRow - $colStd && $questions[$i - 2]['question_type_id'] == 2)
							{
								throw new \Exception("Lỗi chọn quá nhiều đáp án đúng (D$i)");
							}

							if($maxColInRow - $colStd == 1)
								throw new \Exception("Lỗi danh sách đáp án phải nhiều hơn 1 (E$i)");

							if($maxColInRow - $colStd > 15)
								throw new \Exception("Lỗi danh sách đáp án phải nhỏ hơn 15 (E$i)");

							if(chr($maxColInRow - $colStd + 65) < $list_correct[count($list_correct) - 1])
								throw new \Exception("Lỗi giá trị đáp án đúng vượt quá giá trị lựa chọn (D$i)");

							for($j=$colStd; $j<=$maxColInRow; ++$j)
							{
								$option_text = $sheet->getCellByColumnAndRow($j, $i)->getValue();

								if(strval($option_text) == "")
									throw new \Exception("Lỗi đáp án phải khác rỗng (". chr(65 + $j)."$i)");

								if(in_array(chr($j - $colStd + 65), $list_correct))
								{
									$questions[$i - 2]['question_options'][] = [
										'option_text' => $option_text,
										'is_correct' => 1,
										'id'=> $j,
										'index' => chr($j - $colStd + 65)
									];
								}
								else
								{
									$questions[$i - 2]['question_options'][] = [
										'option_text' => $option_text,
										'is_correct' => 0,
										'id' => $j,
										'index' => chr($j - $colStd + 65)
									];
								}
							}
						}
					}
					catch(\Exception $e)
					{
						$view->setVariable('error', $e->getMessage());
						return $view;
					}

					$test_id = $this->params()->fromRoute('id');

					if(isset($test_id))
					{
						$test = $this->testTable->getById($test_id);

						if(!$test)
						{
							$view->setVariable('error', "Không tồn tại đề thi này");
							return $view;
						}

						$listQuestionId = [];
						foreach ($questions as $q) {
							$listQuestionId[] = $this->questionTable->insert($q);
						}

						foreach ($listQuestionId as $item) {
							$this->testTable->addQuestion(['test_id' => $test_id, 'question_id' => $item]);
						}

						$view->setVariable('msg', 'Đã thêm thành công '.count($questions).' câu hỏi vào đề thi '.$test['test_name']);
					}
					else
					{
						foreach ($questions as $q) {
							$this->questionTable->insert($q);
						}

						$view->setVariable('msg', 'Đã thêm thành công '.count($questions).' câu hỏi');
					}
				}
				else
				{
					$view->setVariable('error', $error);
				}
			}
		}
		else
		{
			if($this->params()->fromQuery('template') == 1)
			{
				$user = $this->authManager->getUser();
				$excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				$excel->getDefaultStyle()->getFont()->setSize(12);

				$excel->setActiveSheetIndex(0);
				$sheet = $excel->getActiveSheet();
				$sheet->setTitle('Danh sách câu hỏi');

				$header = ['Danh mục', 'Loại câu', 'Nội dung', 'Đáp án đúng', 'Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D'];

				$maxColumn = 8;
				$maxRow = 40;

				if(!$this->authManager->isGranted('question.manage'))
					$result = $this->categoryTable->getAllByUser($user->user_id);
				else
					$result = $this->categoryTable->getAll();

				$valid_category = '';
				foreach ($result as $row) {
					$valid_category .= $row['category_name'] . ',';
				}

				$valid_question_type = 'Một lựa chọn,Nhiều lựa chọn';

				for($j = 0; $j<$maxColumn; ++$j)
				{
					$sheet->getCell(chr($j + 65)."1")->setValue($header[$j]);
				}

				function setValid($objValid, $formula)
				{
					$objValid->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
					$objValid->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
					$objValid->setShowDropDown(true);
					$objValid->setFormula1('"'.$formula.'"');
				}

				for($i = 2; $i<=$maxRow + 1; ++$i)
				{
					$objValid = $sheet->getCell("A$i")->getDataValidation();
					setValid($objValid, $valid_category);

					$objValid = $sheet->getCell("B$i")->getDataValidation();
					setValid($objValid, $valid_question_type);
				}

				$sheet->getStyle('A1:'.chr($maxColumn - 1 + 65).'1')->getFont()->setBold(true);
				$sheet->getStyle('A1:'.chr($maxColumn - 1 + 65).'1')->applyFromArray([
					'borders' => [
						'allBorders' => [
							'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						]
					],
					'fill' => [
						'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
						'startColor' => [
							'rgb' => 'dddddd'
						]
					]
				]);

				$sheet->getStyle('A1:'.chr($maxColumn - 1 + 65).($maxRow + 1))->applyFromArray([
					'borders' => [
						'outline' => [
							'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						]
					]
				]);

				for($col = 'A'; $col != chr($maxColumn - 1 + 65); ++$col)
				{
					$sheet->getColumnDimension($col)->setAutoSize(true);
				}

				for($row = 1; $row <= $maxRow + 1; ++$row)
				{
					$sheet->getRowDimension($row)->setRowHeight(30);
				}

				$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($excel, 'Xlsx');
				ob_start();
				$writer->save('php://output');
				$excelOutput = ob_get_clean();

				$this->response->getHeaders()->addHeaders([
					'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'Content-Disposition' => 'attachment;filename="question_template.xlsx"',
					'Cache-Control' => 'max-age=0'
				]);

				$this->response->setStatusCode(200);
				$this->response->setContent($excelOutput);
				return $this->response;
			}
		}

		return $view;
	}

	public function exportAction()
	{
		$test_id = $this->params()->fromRoute('id');
		$user_id = $this->authManager->getUser()->user_id;

		if(!$this->authManager->isGranted('question.list-user'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if($this->authManager->isGranted('question.manage'))
		{
			$result = $this->categoryTable->getAll();
			$questions = $this->getListQuestion($this->questionTable->getAll());
		}
		else
		{
			if(!empty($test_id))
			{
				$test = $this->testTable->getById($test_id);

				if(!$this->authManager->isGranted('test.own.modifier', $test))
				{
					return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
				}

				$questions = $this->getListQuestion($this->questionTable->getAllByTest($test_id));
				$result = $this->categoryTable->getAllByTest($test_id);
			}
			else
			{
				$questions = $this->getListQuestion($this->questionTable->getAllByUser($user_id));
				$result = $this->categoryTable->getAllByUser($user_id);
			}
		}

		$category = [];
		foreach ($result as $row) {
			$category[$row['category_id']] = $row['category_name'];
		}

		$excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		$excel->getDefaultStyle()->getFont()->setSize(12);

		$excel->setActiveSheetIndex(0);
		$sheet = $excel->getActiveSheet();
		$sheet->setTitle('Danh sách câu hỏi');

		$header = ['Danh mục', 'Loại câu', 'Nội dung', 'Đáp án đúng', 'Đáp án A', 'Đáp án B'];

		$question_type = ['Một lựa chọn', 'Nhiều lựa chọn'];

		$colStd = 4;
		$maxColumn = 6;

		for($j = 0; $j<$maxColumn; ++$j)
		{
			$sheet->getCell(chr($j + 65)."1")->setValue($header[$j]);
		}

		$questions = array_filter($questions, function($q) { return $q->question_type_id == 1 || $q->question_type_id == 2; });

		$i = 0;
		foreach($questions as $question)
		{
			$row = $i + 2;
			$sheet->getCell("A$row")->setValue($category[$question->category_id]);
			$sheet->getCell("B$row")->setValue($question_type[intval($question->question_type_id) - 1]);
			$sheet->getCell("C$row")->setValue(trim(strip_tags(html_entity_decode($question->question_text))));

			$list_correct = '';
			$countOp = count($question->question_options);

			if($countOp + $colStd > $maxColumn)
				$maxColumn = $countOp + $colStd;

			for($j = 0; $j < $countOp; ++$j)
			{
				$op = $question->question_options[$j];
				if($op->is_correct == 1)
					$list_correct.= $op->index. ' ';

				$col = chr($j + $colStd + 65);
				$sheet->getCell("$col$row")->setValue(trim(strip_tags(html_entity_decode($op->option_text))));
			}

			$sheet->getCell("D$row")->setValue($list_correct);

			$i++;
		}

		for($j = 6; $j<$maxColumn; ++$j)
		{
			$sheet->getCell(chr($j + 65)."1")->setValue('Đáp án '.chr($j + 65 - $colStd));
		}

		$arrStyle = [
			'borders' => [
				'outline' => [
					'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
				]
			]
		];

		$sheet->getStyle('A1:'.chr($maxColumn - 1 + 65).'1')->getFont()->setBold(true);
		$sheet->getStyle('A1:'.chr($maxColumn - 1 + 65).'1')->applyFromArray([
			'borders' => [
				'allborders' => [
					'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
				]
			],
			'fill' => [
				'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'startcolor' => [
					'rgb' => 'dddddd'
				]
			]
		]);

		$sheet->getStyle('A2:'.chr($maxColumn - 1 + 65).(count($questions) + 1))->getAlignment()->setWrapText(true);
		$sheet->getStyle('A2:'.chr($maxColumn - 1 + 65).(count($questions) + 1))->applyFromArray([
			'borders' => [
				'outline' => [
					'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
				]
			]
		]);

		for($col = 'A'; $col != chr($maxColumn + 65); ++$col)
		{
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}

		for($row = 1; $row <= count($questions) + 1; ++$row)
		{
			$sheet->getRowDimension($row)->setRowHeight(30);
		}

		function setValid($objValid, $formula)
		{
			$objValid->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
			$objValid->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
			$objValid->setShowDropDown(true);
			$objValid->setFormula1('"'.$formula.'"');
		}

		$valid_question_type = implode(',', $question_type);
		$valid_category = implode(',', array_map(function($name){
			return $name;
		}, $category));

		for($row = 2; $row <= count($questions) + 1; ++$row)
		{
			$objValid = $sheet->getCell("A$row")->getDataValidation();
			setValid($objValid, $valid_category);

			$objValid = $sheet->getCell("B$row")->getDataValidation();
			setValid($objValid, $valid_question_type);
		}

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($excel, 'Xlsx');
		ob_start();
		$writer->save('php://output');
		$excelOutput = ob_get_clean();

		$this->response->getHeaders()->addHeaders([
			'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'Content-Disposition' => 'attachment;filename="question_export.xlsx"',
			'Cache-Control' => 'max-age=0'
		]);

		$this->response->setStatusCode(200);
		$this->response->setContent($excelOutput);
		return $this->response;

	}

	public function questiontypeAction()
	{
		$result = $this->questionTable->getQuestionType();
		$arr = [];
		foreach ($result as $value) {
			$arr[] = [
				'question_type_id' => $value['question_type_id'],
				'question_type_name' => $value['question_type_name']
			];
		}

		return new JsonModel(['data' => $arr]);
	}

	public function categoryAction()
	{
		$user = $this->authManager->getUser();
		$user_id = $user->user_id;

		$request = $this->getRequest();

		if(!$this->authManager->isGranted('category.list-user'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		if($request->isPost())
		{

			$data = Json::decode($request->getContent(), Json::TYPE_ARRAY);

			switch ($data['action'])
			{
				case 'insert':

					if(!$this->authManager->isGranted('category.create'))
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Bạn chưa cấp quyền']);
					}

					if($this->authManager->isGranted('category.manage'))
					{
						if($this->categoryTable->checkCategory($data['data']['user_id'], $data['data']['category_name']))
							return new JsonModel(['ok' => 0, 'msg' => 'Lỗi trùng tên thể loại đã có']);
					}
					else
					{
						if($this->categoryTable->checkCategory($user_id, $data['data']['category_name']))
							return new JsonModel(['ok' => 0, 'msg' => 'Lỗi trùng tên thể loại đã có']);
					}

					if(!$this->authManager->isGranted('category.manage'))
						$data['data']['user_id'] = $user_id;
					$id = $this->categoryTable->insert($data['data']);
					return new JsonModel(['ok' => 1, 'category_id' => $id]);

				case 'update':

					$category = $this->categoryTable->getById($data['data']['category_id']);

					if(!$this->authManager->isGranted('category.own.modifier', $category))
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Bạn chưa cấp quyền']);
					}

					if(!$this->authManager->isGranted('category.manage'))
						$data['data']['user_id'] = $user_id;
					$this->categoryTable->update($data['data']);
					return new JsonModel(['ok' => 1]);

				case 'getlist':
					if(isset($data['test_id']))
						$result = $this->categoryTable->getAllByTest($data['test_id']);
					else
					{
						if($this->authManager->isGranted('category.list-user'))
							$result = $this->categoryTable->getAllByUser($user_id);
						if($this->authManager->isGranted('category.manage'))
						{
							if(isset($data['user_id']))
							{
								$user_id = $data['user_id'];
								$result = $this->categoryTable->getAllByUser($user_id);
							}
							else
							{
								$result = $this->categoryTable->getAll();
							}
						}
					}

					$category = [];
					foreach ($result as $value) {
						$category[] = $value;
					}

					$data = ['data' => $category ];
					return new JsonModel($data);

				case 'delete':

					$category = $this->categoryTable->getById($data['category_id']);
					if(!$this->authManager->isGranted('category.own.modifier', $category))
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Bạn chưa cấp quyền']);
					}

					if($this->categoryTable->getCategoryQuestion($data['category_id'])->count() == 0)
					{
						$this->categoryTable->delete($data['category_id']);
						return new JsonModel(['ok' => 1]);
					}
					else
					{
						return new JsonModel(['ok' => 0, 'msg' => 'Lỗi! Thể loại này đang có vài câu hỏi. Không thể xoá thể loại này.']);
					}

				default:
					return new JsonModel(['error' => 'errror']);
			}
		}

		if($user->role_id == 3)
		{
			$arr = $this->userTable->getAll();
			$users = [];

			foreach ($arr as $u) {
				if($u['role_id'] != 2)
					$users[] = $u;
			}
			return new ViewModel(['admin' => true, 'users' => $users]);
		}
	}

	public function ajaxAction()
	{
		$data = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);

		$question = $this->questionTable->getById($data['question_id']);

		if(!$this->authManager->isGranted('question.own.modifier', $question))
		{
			return new JsonModel(['ok' => 0, 'msg' => 'Bạn không có quyền truy cập']);
		}

		switch ($data['action']) {
			case 'statistical':
				return new JsonModel([ 'ok' => 1, 'data' => $this->questionTable->getStatistical($data['question_id'])]);
				break;
			
			default:
				# code...
				break;
		}
	}
}