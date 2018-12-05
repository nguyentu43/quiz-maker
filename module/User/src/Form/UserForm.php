<?php

namespace User\Form;

use Zend\Form\Form;

class UserForm extends Form
{
	public function __construct($name = null)
	{
		parent::__construct('user');

		$this->add([
			'name' => 'user_id',
			'type' => 'hidden',
		]);

		$this->add([
			'name' => 'role_id',
			'type' => 'hidden',
		]);

		$this->add([
			'name' => 'username',
			'type' => 'text',
			'options' => [
				'label' => 'Tên tài khoản (*)',
			]
		]);

		$this->add([
			'name' => 'password',
			'type' => 'password',
			'options' => [
				'label' => 'Mật khẩu (*)',
			]
		]);

		$this->add([
			'name' => 'rpassword',
			'type' => 'password',
			'options' => [
				'label' => 'Nhập lại mật khẩu (*)',
			]
		]);

		$this->add([
			'name' => 'email',
			'type' => 'email',
			'options' => [
				'label' => 'Địa chỉ Email (*)',
			]
		]);

		$this->add([
			'name' => 'fullname',
			'type' => 'text',
			'options' => [
				'label' => 'Họ và tên (*)',
			]
		]);

		$this->add([
			'type' => 'submit',
			'name' => 'submit',
			'attributes' => [
				'value' => 'Submit'
			]
		]);
	}
}