<?php

namespace User\Auth;

use Zend\Authentication\Adapter\AdapterInterface;
use User\Model\UserTable;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Result;
use User\Model\User;
use Zend\Session\SessionManager;

class AuthAdapter implements AdapterInterface
{
	private $username;
	private $password;
	private $remember;

	private $table;
	private $sessionManager;

	public function __construct(UserTable $table, SessionManager $sessionManager)
	{
		$this->table = $table;
		$this->sessionManager = $sessionManager;
	}

	public function setUserName($username)
	{
		$this->username = $username;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function setRemember($remember)
	{
		$this->remember = $remember;
	}

	public function authenticate()
	{
		$user = $this->table->getByUserName($this->username);

		if(!$user)
		{
			return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, [
				'Tên tài khoản, mật khẩu không đúng'
			]);
		}

		$crypt = new Bcrypt();

		if($crypt->verify($this->password, $user['password']))
		{
			$obj = new User();
			unset($user['password']);
			$obj->exchangeArray($user);

			if($obj->is_active == 0)
			{
				return new Result(Result::FAILURE_UNCATEGORIZED, null, [
					'Tài khoản này chưa kích hoạt. Hãy kiểm tra lại email',
				]);
			}

			if($this->table->checkForgotCodeUser($obj->user_id))
			{
				return new Result(Result::FAILURE_UNCATEGORIZED, null, [
					'Tài khoản này đang trong trạng thái khôi phục mật khẩu',
				]);
			}

			if($obj->role_id == 1)
			{
				if($this->table->checkActiveTeacher($obj->user_id)['state'] == 0)
					return new Result(Result::FAILURE_UNCATEGORIZED, null, [
					'Tài khoản này chưa được cấp quyền tạo đề thi. Hãy liên hệ người quản trị',
				]);
			}

			if($this->remember == 1)
			{
				$this->sessionManager->rememberMe(60*60*24*30);
			}
			else
			{
				$this->sessionManager->forgetMe();
			}

			return new Result(Result::SUCCESS, $obj, [
				'Đăng nhập thành công'
			]);
		}
		else
		{
			return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, [
				'Tên tài khoản, mật khẩu không đúng'
			]);
		}
	}
}