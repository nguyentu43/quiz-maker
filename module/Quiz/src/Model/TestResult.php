<?php

namespace Quiz\Model;

class TestResult
{
	public $result_id;
	public $user_id;
	public $test_id;
	public $time_start;
	public $time_submit;
	public $count;

	public function exchangeArray($data)
	{
		$this->result_id = !empty($data['result_id']) ? $data['result_id'] : null;
		$this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
		$this->test_id = !empty($data['test_id']) ? $data['test_id'] : null;
		$this->test_id = !empty($data['class_id']) ? $data['class_id'] : null;
		$this->time_start = !empty($data['time_start']) ? $data['time_start'] : null;
		$this->time_submit = !empty($data['time_submit']) ? $data['time_submit'] : null;
		$this->count = !empty($data['count']) ? $data['count'] : null;
		$this->ip_address = !empty($data['ip_address']) ? $data['ip_address'] : null;
		$this->fullname = !empty($data['ip_address']) ? $data['fullname'] : null;
		$this->information = !empty($data['information']) ? $data['information'] : null;
	}

	public function getArray()
	{
		return [
			'test_id' => $this->test_id,
			'user_id' => $this->user_id,
			'result_id' => $this->result_id,
			'time_start' => $this->time_start,
			'time_submit' => $this->time_submit,
			'count' => $this->count,
			'ip_address' => $this->ip_address,
			'fullname' => $this->fullname,
			'information' => $this->information
		];
	}
}