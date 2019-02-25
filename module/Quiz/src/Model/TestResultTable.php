<?php

namespace Quiz\Model;

use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Update;

class TestResultTable
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

	public function getById($result_id)
	{
		return $this->table->select(['result_id' => $result_id])->current();
	}

	public function insert($result)
	{
		$this->table->insert($result);
		return $this->table->lastInsertValue;
	}

	public function update($result)
	{
		$this->table->update($result, ['result_id' => $result['result_id']]);
	}

	public function delete($result_id)
	{
		$this->table->delete(['result_id' => $result_id]);
	}

	public function deleteList($test_id)
	{
		$this->table->delete(['test_id' => $test_id]);
	}

	public function getByUser($user_id)
	{
		$sql = "select result.result_id, count(distinct answer.question_id) as question_count, test_name, count, time_start, time_submit, sum(json_extract(response, '$.point')) as point, count(json_extract(question_settings, '$.point')) as total_point from result join test on result.test_id = test.test_id join answer on answer.result_id = result.result_id join question on question.question_id = answer.question_id where result.user_id = ? group by result.result_id";
		return $this->table->adapter->createStatement($sql)->execute([$user_id]);
	}

	public function getByTest($test_id)
	{
		$sql = "select result.result_id, count(distinct answer.question_id) as total_question, information, fullname, ip_address, count, time_start, time_submit, sum(json_extract(response, '$.point')) as point, count(json_extract(question_settings, '$.point')) as total_point from result join answer on answer.result_id = result.result_id join question on question.question_id = answer.question_id where test_id = ? group by result.result_id";
		return $this->table->adapter->createStatement($sql)->execute([$test_id]);
	}

	public function getByTestUser($test_id, $user_id)
	{
		$sql = "select * from result where test_id = ? and user_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$test_id, $user_id]);
	}

	public function getByIp($test_id, $ip)
	{
		$sql = "select * from result where test_id = ? and ip_address = ?";
		return $this->table->adapter->createStatement($sql)->execute([$test_id, $ip]);
	}

	public function getAnswerRight($test_id, $question_id)
	{
		$sql = "select count(*) as count from result join answer on result.result_id = answer.result_id and test_id = ? and question_id = ? where json_extract(response, '$.point') > 0";
		$right = $this->table->adapter->createStatement($sql)->execute([$test_id, $question_id])->current()['count'];

		$sql = "select count(*) as count from result join answer on result.result_id = answer.result_id and test_id = ? and question_id = ?";
		$total = $this->table->adapter->createStatement($sql)->execute([$test_id, $question_id])->current()['count'];

		return [ 'right' => $right, 'total' => $total ];
	}

	public function getListAnswer($result_id)
	{
		$sql = "select * from answer where result_id = ? and response is not null";
		return $this->table->adapter->createStatement($sql)->execute([$result_id]);
	}

	public function getAnswer($result_id, $question_id)
	{
		$sql = "select * from answer where result_id = ? and question_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$result_id, $question_id])->current();
	}

	public function insertAnswer($answer)
	{
		$insert = new Insert('answer');
		$insert->values($answer);
		$st = $this->table->getSql()->prepareStatementForSqlObject($insert);
		$st->execute();
	}

	public function updateAnswer($response, $where)
	{
		$update = new Update('answer');
		$update->set($response);
		$update->where($where);
		$st = $this->table->getSql()->prepareStatementForSqlObject($update);
		$st->execute();
	}

	public function submit($result_id)
	{
		$time_submit = date('Y-m-d H:i:s');
		$sql = "update result set time_submit = ? where result_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$time_submit, $result_id]);
	}

	public function updateCount($result_id)
	{
		$sql = "update result set count = (select count(*) from answer where json_extract(response, '$.point') > 0 and result_id = ?) where result_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$result_id, $result_id]);
	}

	public function getRankTest($test_id)
	{
		$sql = "select result.fullname, count from result where result.test_id = ? order by result.count desc limit 5";
		return $this->table->adapter->createStatement($sql)->execute([$test_id]);
	}
}
