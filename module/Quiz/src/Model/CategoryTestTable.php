<?php

namespace Quiz\Model;

class CategoryTestTable
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

	public function getById($id)
	{
		return $this->table->select(['id' => $id])->current();
	}

	public function getName($name)
	{
		return $this->table->select(['name' => $name]);
	}

	public function insert($c)
	{
		$this->table->insert($c->getArray());
	}

	public function update($c)
	{
		$this->table->update($c->getArray(), ['id' => $c->getArray()['id']]);
	}

	public function delete($c_id)
	{
		$this->table->delete(['id' => $c_id]);
	}
}