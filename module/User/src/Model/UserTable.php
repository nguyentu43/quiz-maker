<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Crypt\Password\Bcrypt;
use Zend\Db\Sql\Select;

class UserTable
{
	private $table;

	public function __construct($table)
	{
		$this->table = $table;
	}

	public function getAll()
	{
		$sql = $this->table->getSql();

		$select = new Select('user');

		$select->join('role', 'role.role_id = user.role_id', ['role_id', 'name'], 'inner');

		$st = $sql->prepareStatementForSqlObject($select);

		return $st->execute();
	}

	public function getById($user_id)
	{
		return $this->table->select(['user_id' => $user_id])->current();
	}

	public function getByCode($uniq)
	{
		return $this->table->select(['verification_code' => $uniq])->current();
	}

	public function getByUserName($username)
	{
		$rowSet = $this->table->select(['username' => $username]);
		return $rowSet->current();
	}

	public function getByEmail($email)
	{
		$rowSet = $this->table->select(['email' => $email]);
		return $rowSet->current();
	}

	public function forgotPassword($user_id, $code)
	{
		$sql = "delete from forgot_password where user_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$user_id]);

		$sql = "insert into forgot_password values(?, ?, now())";
		$this->table->adapter->createStatement($sql)->execute([$user_id, $code]);
	}

	public function updatePassword($user_id, $password)
	{
		$sql = "delete from forgot_password where user_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$user_id]);

		$crypt = new Bcrypt();
		$password = $crypt->create($password);
		$sql = "update user set password = ? where user_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$user_id, $password]);
	}

	public function checkForgotCode($code)
	{
		$sql = "select * from forgot_password where code = ?";
		return $this->table->adapter->createStatement($sql)->execute([$code])->current();
	}

	public function checkForgotCodeUser($user_id)
	{
		$sql = "select * from forgot_password where user_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$user_id])->current();
	}

	public function saveUser($user)
	{

		if($user->user_id == 0)
		{
			$crypt = new Bcrypt();
			$user->password = $crypt->create($user->password);
			$this->table->insert($user->getArray());
		}
		else
		{
			$this->table->update($user->getArray(), ['user_id' => $user->user_id]);
		}
	}

	public function checkActiveTeacher($user_id)
	{
		$sql = "select * from active_teacher where user_id = ?";
		return $this->table->adapter->createStatement($sql)->execute([$user_id])->current();
	}

	public function activeTeacher($user_id, $state)
	{
		$sql = "update active_teacher set state = ? where user_id = ?";
		$this->table->adapter->createStatement($sql)->execute([$state, $user_id]);
	}

	public function deleteUser($user_id)
	{
		$this->table->delete(['user_id' => $user_id]);
	}
}