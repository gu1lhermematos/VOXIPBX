<?php
class User extends Model {
	public $name = 'User';
	public $useTable = 'users';
	public $primaryKey = 'user_id';
	public $validate = array(
		'username' => array(
			'rule'    => 'isUnique',
			'message' => 'Usuario ja existe no banco de dados.'
		)
	);
	
	
	
}

