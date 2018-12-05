<?php

namespace Quiz\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class TestTable
{
	private $table;

	public function __construct($tableGateway)
	{
		$this->table = $tableGateway;
	}

	public function getAll()
	{
		$sql = "select * from user join test on user.user_id = test.user_id";
		return $this->table->adaper->createStatement($sql)->execute();
	}

	public function getAllByUser($user_id, $query = null, $paginated = false)
	{
		if(!$paginated)
			return $this->table->select(['user_id' => $user_id]);
		else
			return $this->getAllByUserPaginated($user_id, $query);
	}

	public function getByTestName($test_name)
	{
		$sql = "select * from test where lower(test_name) = convert(lower(?), binary)";
		return $this->table->adapter->createStatement($sql)->execute([$test_name])->current();
	}

	public function getByTestCode($test_code)
	{
		$sql = "select * from test where test_code = ? and is_private = 1";
		return $this->table->adapter->createStatement($sql)->execute([$test_code])->current();
	}

	public function getByCategory($c_id)
	{
		$sql = "select * from test join category_test on test.category_test_id = category_test.id where test.is_private = '0' and category_test.id = ? and is_enable = 1 limit 10";
		return $this->table->adapter->createStatement($sql)->execute([$c_id]);
	}

	public function swapIndexQuestion($test_id, $question_id_a, $index_a, $question_id_b, $index_b)
	{
		$sql = "update test_question set index_question = ? where test_id = ? and question_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$index_b, $test_id, $question_id_a ]);

		$sql = "update test_question set index_question = ? where test_id = ? and question_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$index_a, $test_id, $question_id_b]);
	}

	public function getAllByUserPaginated($user_id, $query = null)
	{
		$select = new Select($this->table->getTable());

		$where = new Where();

		if($user_id > 0)
			$where->equalTo('user_id', $user_id);

		if($query)
		{
			$where->and->like('test_name', '%'.$query['test_name'].'%');

			if(!empty($query['from_date']) && !empty($query['to_date']))
			{
				$from_date = date_format(date_create_from_format('d/m/Y', $query['from_date']), 'Y-m-d H:i:s');
				$to_date = date_format(date_create_from_format('d/m/Y', $query['to_date']), 'Y-m-d H:i:s');
				$where->between('created_date', $from_date, $to_date);
			}

			if(isset($query['is_private']) && strval($query['is_private']) != "")
			{
				$where->equalTo('is_private', $query['is_private']);
			}

			if(isset($query['is_enable']) && strval($query['is_enable']) != "")
			{
				$where->equalTo('is_enable', $query['is_enable']);
			}

			if(isset($query['category_test_id']) && $query['category_test_id'] != "")
			{
				$where->equalTo('category_test_id', $query['category_test_id']);
			}
		}

		$select->where($where);

		$resultPrototype = new ResultSet();
		$resultPrototype->setArrayObjectPrototype(new Test());

		$paginatorAdapter = new DbSelect(
			$select, $this->table->getAdapter(), $resultPrototype
		);

		$paginator = new Paginator($paginatorAdapter);

		return $paginator;
	}

	public function searchTestPublic($query = null, $paginated = null)
	{
		$select = new Select($this->table->getTable());

		$where = new Where();

		$where->equalTo('is_private', 0);

		if(!isset($query['search_test_own']))
			$where->AND->equalTo('is_enable', "1");

		if(isset($query['except_user_id']))
			$where->AND->notEqualTo('user_id', $query['except_user_id']);

		if($query)
		{
			if(!empty($query['keywords']))
			{
				$where->AND->expression("match(test_name, description) against (? in boolean mode)", $query['keywords']);
			}

			if(!empty($query['user_id']))
			{
				$where->AND->equalTo('user_id', $query['user_id']);
			}

			if(!empty($query['category_test_id']))
			{
				$where->equalTo('category_test_id', $query['category_test_id']);
			}
		}

		$select->where($where);

		if(!empty($query['offset']))
		{
			$select->limit($query['limit'])->offset($query['offset']);
		}

		if(!$paginated)
		{
			return $this->table->getSql()->prepareStatementForSqlObject($select)->execute();
		}

		$resultPrototype = new ResultSet();
		$resultPrototype->setArrayObjectPrototype(new Test());

		$paginatorAdapter = new DbSelect(
			$select, $this->table->getAdapter(), $resultPrototype
		);

		$paginator = new Paginator($paginatorAdapter);

		return $paginator;
	}

	public function getById($test_id)
	{
		return $this->table->select(['test_id' => $test_id])->current();
	}

	public function checkQuestionByTest($user_id, $question_id, $f, $admin = false)
	{
		if($admin)
		{
			if($f)
				$sql = "select test_id, concat(test.test_name, ' - Người tạo: ', user.fullname) as test_name from test join user on test.user_id = user.user_id where test.test_id in ( select test_id from question inner join test_question on question.question_id = test_question.question_id where test_question.question_id = ?)";
			else
				$sql = "select test_id, concat(test.test_name, ' - Người tạo: ', user.fullname) as test_name from test join user on test.user_id = user.user_id where test.test_id not in ( select test_id from question inner join test_question on question.question_id = test_question.question_id where test_question.question_id = ?)";
			$st = $this->table->adapter->createStatement($sql);
			return $st->execute([$question_id]);
		}
		else
		{
			if($f)
				$sql = "select * from test where test.user_id = ? and test.test_id in ( select test_id from question inner join test_question on question.question_id = test_question.question_id where test_question.question_id = ?)";
			else
				$sql = "select * from test where test.user_id = ? and test.test_id not in ( select test_id from question inner join test_question on question.question_id = test_question.question_id where test_question.question_id = ?)";
			$st = $this->table->adapter->createStatement($sql);
			return $st->execute([$user_id, $question_id]);
		}

		
	}

	public function addQuestion($data)
	{
		$sql ="insert into test_question(test_id, question_id) values(?, ?)";
		$this->table->adapter->createStatement($sql)->execute([$data['test_id'], $data['question_id']]);
	}

	public function removeQuestion($data)
	{
		$sql ="delete from test_question where test_id = ? and question_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$data['test_id'], $data['question_id']]);
	}

	public function insert($test)
	{
		$this->table->insert($test);
		return $this->table->lastInsertValue;
	}

	public function update($test)
	{
		if(!empty($test['random_from_category']))
			$test['random_from_category'] = json_encode($test['random_from_category']);
		$this->table->update($test, ['test_id' => $test['test_id']]);
	}

	public function delete($test_id)
	{
		$this->table->delete(['test_id' => $test_id]);
	}

	public function getTestRecently()
	{
		$sql = "select * from user join test on user.user_id = test.user_id where is_private = 0 and is_enable = 1 order by created_date desc limit 10";
		return $this->table->adapter->createStatement($sql)->execute();
	}

	public function getTestTop()
	{
		$sql = "select test.*, user.* from test join user on test.user_id = user.user_id and test.is_private = 0 and test.is_enable = 1 join result on test.test_id = result.test_id group by test.test_id order by count(result.result_id) desc limit 10";
		return $this->table->adapter->createStatement($sql)->execute();
	}

	public function getCountQuestion($test_id)
	{
		$sql = "select count(*) as count from test_question where test_id = ?";
		$total = $this->table->adapter->createStatement($sql)->execute([$test_id])->current()['count'];
		
		$random = json_decode($this->getById($test_id)['random_from_category'], true);

		if(!empty($random))
			foreach ($random as $key => $value) {
				$total += $value;
			}

		return $total;
	}

}