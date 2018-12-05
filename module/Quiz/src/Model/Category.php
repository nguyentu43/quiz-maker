<?php

namespace Quiz\Model;

class Category
{
	public $category_id;
	public $user_id;
	public $category_name;

	public function exchangeArray($data)
	{
		$this->category_id = !empty($data['category_id']) ? $data['category_id'] : null;
		$this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
		$this->category_name = !empty($data['category_name']) ? $data['category_name'] : null;
	}

	public function getArray()
	{
		return [
			'category_id' => $this->category_id,
			'user_id' => $this->user_id,
			'category_name' => $this->category_name
		];
	}
}