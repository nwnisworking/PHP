<?php
namespace SQL;

use Error;
use PDO;

class Driver extends DB{
	public function connect(){
		$host = $this->host;
		$port = $this->port;
		$user = $this->user;
		$pass = $this->pass;
		$database = $this->database;
		$sql_option = [
			PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES=>false
		];

		if($this->db_type === 'mysql')
			$this->conn = new PDO("mysql:host=$host;dbname=$database;port=$port", $user, $pass, $sql_option);
		else
			$this->conn = new PDO("sqlite:$database");

		return $this;
	}

	public function close(){
		$this->conn = null;
	}

	public function execute(){
		if($this->type === null) throw new Error('Statement not created');

		if(!isset($this->conn))
			$this->connect();

		if(empty($this.'')) return '';

		$query = $this.'';
		$prepare = $this->conn->prepare($query);

		foreach($this->values as $k=>$v)
			$prepare->bindValue($k + 1, $v, self::type($v));

		if(!$prepare->execute()) throw new Error('Error with query statement');

		return $prepare->fetchAll();
	}

	public static function type(mixed $value){
		switch(true){
			case is_string($value) : return PDO::PARAM_STR;
			case is_numeric($value) : return PDO::PARAM_INT;
			case is_bool($value) : return PDO::PARAM_BOOL;
			case is_null($value) :
			default :  return PDO::PARAM_NULL;
		}
	}


	public static function file(string $filename){
		$db = new static;
		$db->db_type = 'sqlite3';
		$db->database = $filename;
		return $db;
	}
}
