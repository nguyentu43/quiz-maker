<?php

namespace User\Form;

use Zend\Form\Form;

class LoginForm extends Form
{
	public function __construct($name = null)
	{
		parent::__construct('login');

		$this->add([
			'name' => 'username',
			'type' => 'text',
			'options' => [
				'label' => 'Tên tài khoản',
			]
		]);

		$this->add([
			'name' => 'password',
			'type' => 'password',
			'options' => [
				'label' => 'Mật khẩu',
			]
		]);

		$this->add([
			'name' => 'remember',
			'type' => 'checkbox',
			'options' => [
				'label' => 'Ghi nhớ đăng nhập',
			]
		]);

		$this->add([
			'type' => 'submit',
			'name' => 'login',
			'attributes' => [
				'value' => 'Submit'
			]
		]);
	}
}