<?php

namespace User\Model;
use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;
use Zend\Validator\EmailAddress;
use Zend\Validator\Hostname;
use Zend\Validator\Identical;
use Zend\Filter\ToInt;

class User
{
	public $user_id;
	public $username;
	public $password;
	public $email;
	public $fullname;
	public $role_id;
	public $is_active;
	public $verification_code;

	public function exchangeArray($data)
	{
		$this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
		$this->username = !empty($data['username']) ? $data['username'] : null;
		$this->password = !empty($data['password']) ? $data['password'] : null;
		$this->email = !empty($data['email']) ? $data['email'] : null;
		$this->fullname = !empty($data['fullname']) ? $data['fullname'] : null;
		$this->role_id = !empty($data['role_id']) ? $data['role_id'] : null;
		$this->is_active = !empty($data['is_active']) ? $data['is_active'] : null;
		$this->verification_code = !empty($data['verification_code']) ? $data['verification_code'] : null;
	}

	public function getArray()
	{
		return [
			'user_id' => $this->user_id,
			'username' => $this->username,
			'password' => $this->password,
			'email' => $this->email,
			'fullname' => $this->fullname,
			'role_id' => $this->role_id,
			'is_active' => $this->is_active,
			'verification_code' => $this->verification_code
		];
	}

	public function getInputFilter()
	{
		$inputFilter = new InputFilter();

		$inputFilter->add([
			'name' => 'role_id',
			'required' => true
		]);

		$inputFilter->add([
			'name' => 'fullname',
			'required' => true,
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'min' => 5,
						'max' => 60,
						'messages' => [
							StringLength::TOO_SHORT => 'Họ và tên phải nhiều hơn %min% kí tự',
							StringLength::TOO_LONG => 'Họ và tên phải nhỏ hơn %max% kí tự'
						]
					],
				]
			]
		]);

		$inputFilter->add([
			'name' => 'username',
			'required' => true,
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'min' => 5,
						'max' => 25,
						'messages' => [
							StringLength::TOO_SHORT => 'Tên tài khoản phải nhiều hơn %min% kí tự',
							StringLength::TOO_LONG => 'Tên tài khoản phải nhỏ hơn %max% kí tự'
						]
					]
				]
			]
		]);

		$inputFilter->add([
			'name' => 'password',
			'required' => true,
			'validators' => [
				[
					'name' => StringLength::class,
					'options' => [
						'min' => 6,
						'max' => 40,
						'messages' => [
							StringLength::TOO_SHORT => 'Mật khẩu phải nhiều hơn %min% kí tự',	
							StringLength::TOO_LONG => 'Tên tài khoản phải nhỏ hơn %max% kí tự'
						]
					]
				]
			]
		]);

		$inputFilter->add([
			'name' => 'rpassword',
			'required' => true,
			'validators' => [
				[
					'name' => Identical::class,
					'options' => [
						'token' => 'password',
						'messages' => [
							Identical::NOT_SAME => 'Mật khẩu nhập lại không đúng'
						]
					]
				]
			]
		]);

		$inputFilter->add([
			'name' => 'email',
			'validators' => [
				[
					'name' => EmailAddress::class,
					'messages' => [
						Hostname::UNKNOWN_TLD => 'Địa chỉ Email không hợp lệ'
					]
				]
			]
		]);

		return $inputFilter;
	}
}