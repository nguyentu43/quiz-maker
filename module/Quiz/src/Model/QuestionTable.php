<?php

namespace Quiz\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\ResultInterface;

class QuestionTable
{
	private $table;

	public function __construct($tableGateway)
	{
		$this->table = $tableGateway;
	}

	public function getAll()
	{
		return $this->table->select();
	}

	public function getAllByUser($user_id)
	{
		return $this->table->select(['user_id' => $user_id]);
	}

	public function getAllByTest($test_id)
	{
		$select = $this->table->getSql()->select();
		
		$select->join('test_question', 'question.question_id = test_question.question_id', [ 
			'question_id'
		], 'inner');

		$select->where(['test_id' => $test_id]);
		$select->order('index_question asc');

		$st = $this->table->getSql()->prepareStatementForSqlObject($select);
		$result = $st->execute();
		return $result;
	}

	public function getAllByResult($result_id)
	{
		$sql = "select * from question where question_id in (select question_id from answer where result_id = ?)";
		$result = $this->table->adapter->createStatement($sql)->execute([$result_id]);
		return $result;
	}

	public function getRandomByCategory($category_id, $test_id, $limit = 1)
	{
		$sql = "select * from question where category_id = ? and question_id not in (select question_id from test_question where test_id = ?) order by rand() limit $limit";
		$st = $this->table->adapter->createStatement($sql);
		return $st->execute([$category_id, $test_id]);
	}

	public function getQuestionByCategory($category_id, $test_id, $user_id)
	{
		if($category_id == 0)
		{
			$sql = "select * from question where user_id = ? and question_id not in (select question_id from test_question where test_id = ?)";
			$st = $this->table->adapter->createStatement($sql);
			return $st->execute([$user_id, $test_id]);
		}
		else
		{
			$sql = "select * from question where user_id = ? and category_id = ? and question_id not in (select question_id from test_question where test_id = ?)";
			$st = $this->table->adapter->createStatement($sql);
			return $st->execute([$user_id, $category_id, $test_id]);
		}
	}

	public function checkQuestionByAnwser($question_id)
	{
		$sql = "select * from answer where question_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$question_id]);
	}

	public function getById($question_id)
	{
		return $this->table->select(['question_id' => $question_id])->current();
	}

	public function insert($question)
	{
		$question['question_options'] = json_encode($question['question_options']);
		$question['question_settings'] = json_encode($question['question_settings']);
		$this->table->insert($question);
		return $this->table->lastInsertValue;
	}

	public function update($question)
	{
		$question['question_options'] = json_encode($question['question_options']);
		$question['question_settings'] = json_encode($question['question_settings']);
		$this->table->update($question, ['question_id' => $question['question_id']]);
	}

	public function delete($question_id)
	{
		$this->table->delete(['question_id' => $question_id]);
	}

	public function getQuestionType()
	{
		$sql = new Sql($this->table->getAdapter());
		$select = $sql->select('question_type');
		$st = $sql->prepareStatementForSqlObject($select);
		$result = $st->execute();
		return $result;
	}

	public function getStatistical($question_id)
	{
		$sql = "SELECT count(*) as count FROM answer WHERE question_id = ? and json_extract(response, '$.point') > 0";
		$count = $this->table->adapter->createStatement($sql)->execute([$question_id])->current()['count'];

		$sql = "SELECT count(*) as count FROM answer WHERE question_id = ?";
		$total = $this->table->adapter->createStatement($sql)->execute([$question_id])->current()['count'];

		$question = $this->getById($question_id);
		$question_options = json_decode($question['question_options']);

		$detail = [];

		function getList($result)
		{
			$list = [];
			foreach ($result as $item) {

				if(!empty($item['user_id']) && !in_array("{$item['fullname']} (Tài khoản: {$item['username']})", $list))
				{
					array_push($list, "{$item['fullname']} (Tài khoản: {$item['username']})");
				}
				else if(in_array("{$item['fullname']} (IP: {$item['ip_address']})", $list))
				{
					array_push($list, "{$item['fullname']} (IP: {$item['ip_address']})");
				}
			}

			return $list;
		}

		if($question['question_type_id'] == 1 || $question['question_type_id'] == 2)
		{
			foreach($question_options as $option)
			{
				$sql = "select result.fullname, user.username, ip_address, result.user_id from answer join result on answer.result_id = result.result_id left join user on result.user_id = user.user_id where question_id = ? and json_contains(response, ?, '$.data') = 1";

				$result = $this->table->adapter->createStatement($sql)->execute([$question['question_id'], strval($option->id)]);

				$list = getlist($result);

				array_push($detail, [ 'name' => $option->index, 'count' => $result->count(), 'list' => $list]);
			}

			$sql = "select result.fullname, user.username, ip_address, result.user_id from answer join result on answer.result_id = result.result_id left join user on result.user_id = user.user_id where question_id = ? and response is null";

			$result = $this->table->adapter->createStatement($sql)->execute([$question['question_id']]);

			$list = getlist($result);

			array_push($detail, [ 'name' => 'Chưa trả lời', 'count' => $result->count(), 'list' => $list]);
		}

		return ['count' => $count, 'total' => $total, 'detail' => $detail];
	}
}
