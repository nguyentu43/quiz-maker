<?php

namespace Quiz\Model;

class Test
{

	public $test_id;
	public $user_id;
	public $test_name;
	public $description;
	public $created_date;
	public $modified_date;
	public $is_private;
	public $is_enable;
	public $time_limit;
	public $attemps;
	public $category_test_id;
	public $shuffle;
	public $test_code;
	public $is_login;
	public $random_from_category;
	public $show_point;
	public $img;

	public function exchangeArray($data)
	{
		$this->test_id = !empty($data['test_id']) ? $data['test_id'] : null;
		$this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
		$this->test_name = !empty($data['test_name']) ? $data['test_name'] : null;
		$this->description = !empty($data['description']) ? $data['description'] : null;
		$this->created_date = !empty($data['created_date']) ? $data['created_date'] : null;
		$this->modified_date = !empty($data['modified_date']) ? $data['modified_date'] : null;
		$this->is_private = !empty($data['is_private']) ? $data['is_private'] : null;
		$this->is_enable = !empty($data['is_enable']) ? $data['is_enable'] : null;
		$this->time_limit = !empty($data['time_limit']) ? $data['time_limit'] : null;
		$this->attemps = !empty($data['attemps']) ? $data['attemps'] : null;
		$this->category_test_id = !empty($data['category_test_id']) ? $data['category_test_id'] : null;
		$this->shuffle = !empty($data['shuffle']) ? $data['shuffle'] : null;
		$this->test_code = !empty($data['test_code']) ? $data['test_code'] : null;
		$this->is_login = !empty($data['is_login']) ? $data['is_login'] : null;
		$this->random_from_category = !empty($data['random_from_category']) ? json_decode($data['random_from_category']) : [];
		$this->show_point = !empty($data['show_point']) ? $data['show_point'] : null;
		$this->img = !empty($data['img']) ? $data['img'] : null;
	}

	public function getArray()
	{
		return [
			'test_id' => $this->test_id,
			'user_id' => $this->user_id,
			'test_name' => $this->test_name,
			'description' => $this->description,
			'created_date' => $this->created_date,
			'modified_date' => $this->modified_date,
			'is_private' => $this->is_private,
			'is_enable' => $this->is_enable,
			'time_limit' => $this->time_limit,
			'attemps' => $this->attemps,
			'category_test_id' => $this->category_test_id,
			'shuffle' => $this->shuffle,
			'test_code' => $this->test_code,
			'is_login' => $this->is_login,
			'random_from_category' => $this->random_from_category,
			'show_point' => $this->show_point,
			'img' => $this->img
		];
	}
}