<?php

namespace Quiz\Model;

class CategoryTest
{
	public $id;
	public $name;

	public function exchangeArray($data)
	{
		$this->id = !empty($data['id']) ? $data['id'] : null;
		$this->name = !empty($data['name']) ? $data['name'] : null;
	}

	public function getArray()
	{
		return [
			'id' => $this->id,
			'name' => $this->name
		];
	}
}