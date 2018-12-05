<?php

namespace User\Auth;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\AdapterInterface;
use User\Model\UserTable;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

class AuthManager
{
	private $authService;
	private $authAdapter;
	private $userTable;
	private $rbac;

	public function __construct(AuthenticationService $authService, AdapterInterface $adapter, UserTable $userTable)
	{
		$this->authService = $authService;
		$this->authAdapter = $adapter;
		$this->userTable = $userTable;
		$this->createRbac();
	}

	public function checkLogin()
	{
		return $this->authService->hasIdentity();
	}

	public function getRole()
	{
		$user = $this->getUser();
		if(empty($user))
			return 'guest';
		else
		{
			if($user->role_id == 1)
				return 'teacher';
			if($user->role_id == 2)
				return 'student';
			return 'admin';
		}
	}

	private function createRbac()
	{
		$rbac = new Rbac();

		$rbac->addRole('admin');
		$rbac->addRole('teacher', 'admin');
		$rbac->addRole('student', 'teacher');
		$rbac->addRole('guest', 'student');

		//guest
		$rbac->getRole('guest')->addPermission('test.start');
		$rbac->getRole('guest')->addPermission('result.detail');

		//student
		$rbac->getRole('student')->addPermission('result.list-user');
		$rbac->getRole('student')->addPermission('result.own.detail');
		$rbac->getRole('student')->addPermission('user.edit');

		//teacher
		$rbac->getRole('teacher')->addPermission('category.list-user');
		$rbac->getRole('teacher')->addPermission('category.own.modifier');
		$rbac->getRole('teacher')->addPermission('category.create');
		$rbac->getRole('teacher')->addPermission('test.list-user');
		$rbac->getRole('teacher')->addPermission('test.own.modifier');
		$rbac->getRole('teacher')->addPermission('test.create');
		$rbac->getRole('teacher')->addPermission('test.own.mark');
		$rbac->getRole('teacher')->addPermission('test.read-detail');
		$rbac->getRole('teacher')->addPermission('question.own.modifier');
		$rbac->getRole('teacher')->addPermission('question.create');
		$rbac->getRole('teacher')->addPermission('question.list-user');

		//admin
		$rbac->getRole('admin')->addPermission('user.manage');
		$rbac->getRole('admin')->addPermission('test.manage');
		$rbac->getRole('admin')->addPermission('category.manage');
		$rbac->getRole('admin')->addPermission('category-test.manage');
		$rbac->getRole('admin')->addPermission('question.manage');

		$this->rbac = $rbac;
	}

	public function getUser()
	{
		$user = $this->authService->getIdentity();
		if($this->authService->getIdentity())
		{
			if($this->userTable->getByUserName($user->username))
			{
				return $user;
			}
			else
			{
				throw new Exception('Lỗi tài khoản đã bị xoá khỏi hệ thống');
			}
		}
	}

	public function login($username, $password, $remember)
	{
		$auth = $this->authAdapter;
		$auth->setUserName($username);
		$auth->setPassword($password);
		$auth->setRemember($remember);

		return $this->authService->authenticate($auth);
	}

	public function logout()
	{
		$this->authService->clearIdentity();
	}

	public function isGranted($permission, $obj = null)
	{
		$user = $this->getUser();

		if(strstr($permission, 'own'))
		{
			$assertion = function($rbac) use ($user, $obj)
			{
				if($obj == null) return false;
				return $this->getRole() == 'admin' || $user->user_id == $obj->user_id;
			};

			return $this->rbac->isGranted($this->getRole(), $permission, $assertion);
		}

		return $this->rbac->isGranted($this->getRole(), $permission);
	}

}