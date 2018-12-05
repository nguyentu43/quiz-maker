<?php

namespace Quiz\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class UploadForm extends Form
{
	public function __construct($name = null)
	{
		parent::__construct($name);

		$this->add([
			'name' => 'file',
			'type' => 'file',
			'options' => [
				'label' => 'Tập tin câu hỏi'
			]
		]);

		$this->add([
			'name' => 'submit',
			'type' => 'submit',
			'attributes' => [
				'value' => 'Tải lên'
			]
		]);
	}
}