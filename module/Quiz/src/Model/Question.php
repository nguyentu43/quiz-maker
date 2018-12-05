<?php

namespace Quiz\Model;

class Question
{
	public $question_id;
	public $category_id;
	public $user_id;
	public $question_type_id;
	public $question_text;
	public $question_options;
	public $question_settings;

	public function exchangeArray($data)
	{
		$this->question_id = !empty($data['question_id']) ? $data['question_id'] : null;
		$this->category_id = !empty($data['category_id']) ? $data['category_id'] : null;
		$this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
		$this->question_type_id = !empty($data['question_type_id']) ? $data['question_type_id'] : null;
		$this->question_text = !empty($data['question_text']) ? $data['question_text'] : null;
		$this->question_options = !empty($data['question_options']) ? json_decode($data['question_options']) : [];
		$this->question_settings = !empty($data['question_settings']) ? json_decode($data['question_settings']) : new StdClass;

	}

	public function getArray()
	{
		return [
			'question_id' => $this->question_id,
			'user_id' => $this->user_id,
			'category_id' => $this->category_id,
			'question_type_id' => $this->question_type_id,
			'question_text' => $this->question_text,
			'question_options' => $this->question_options,
			'question_settings' => $this->question_settings
		];
	}
}