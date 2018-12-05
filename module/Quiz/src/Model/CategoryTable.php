<?php

namespace Quiz\Model;

use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Driver\ResultInterface;

class CategoryTable
{
	private $table;

	public function __construct($tableGateway)
	{
		$this->table = $tableGateway;
	}

	public function getById($category_id)
	{
		return $this->table->select(['category_id' => $category_id])->current();
	}

	public function getAll()
	{
		$sql = "select fullname, username, category.category_id, category_name, user.user_id from category join user on category.user_id = user.user_id";
		$result = $this->table->adapter->createStatement($sql)->execute();

		$arr = [];

		foreach ($result as $value) {
			$value['question_count'] = $this->getQuestionCount($value['category_id'])['question_count'];
			$arr[] = $value;
		}
		
		return $arr;
	}

	public function getAllByUser($user_id)
	{
		$result = $this->table->select(['user_id' => $user_id]);

		$arr = [];

		foreach ($result as $value) {
			$value['question_count'] = $this->getQuestionCount($value->category_id)['question_count'];
			$arr[] = $value;
		}
		
		return $arr;
	}

	public function getByName($category_name, $user_id)
	{
		return $this->table->select(['category_name' => $category_name, 'user_id' => $user_id])->current();
	}

	public function getAllByTest($test_id)
	{
		$sql = "select distinct category.category_name, category.category_id from category join question on category.category_id = question.category_id join test_question on test_question.question_id = question.question_id where test_question.test_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$test_id]);
	}

	public function getQuestionCount($category_id)
	{
		$sql = $this->table->getSql();
		$select = new Select('question');

		$select->where(['category_id' => $category_id]);
		$select->columns([
			'question_count' => new Expression('COUNT(question_id)')
		]);

		$st = $sql->prepareStatementForSqlObject($select);
		return $st->execute()->current();
	}

	public function checkCategory($user_id, $category_name)
	{
		return $this->table->select(['category_name' => $category_name, 'user_id' => $user_id])->current();
	}

	public function insert($category)
	{
		$this->table->insert($category);
		return $this->table->lastInsertValue;
	}

	public function update($category)
	{
		$this->table->update($category, ['category_id' => $category['category_id']]);
	}

	public function delete($category_id)
	{
		$this->table->delete(['category_id' => $category_id]);
	}

	public function getCategoryQuestion($category_id)
	{
		$sql = "select * from category join question on category.category_id = question.category_id where category.category_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$category_id]);
	}
}