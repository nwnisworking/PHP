<?php
namespace SQL;

use mysqli;
use Error;

class MySQL extends DB{
	public function connect(){
		$this->conn = new mysqli($this->host ?? 'localhost', $this->user, $this->pass, $this->database, $this->port ?? 3306);
	}

	public function close(){
		$this->conn->close();
		$this->conn = null;
	}

	public function execute(){
		if($this->type === null) throw new Error('Statement not created');

		if(!isset($this->conn))
			$this->connect();

		if(empty($this.'')) return '';

		$query = $this.'';
		$prepare = $this->conn->prepare($query);
		$type = '';
		
		foreach($this->values as $v)
			$type.= self::type($v);
			
		if(count($this->values) > 0)
			$prepare->bind_param($type, ...$this->values);

		if(!$prepare->execute()) return null;
		else return $prepare->get_result()->fetch_all(MYSQLI_ASSOC);
	}

	public static function type(mixed $value){
		switch(true){
			case is_numeric($value) : 
			case is_integer($value) : return 'i';
			case is_double($value) : return 'd';
			case is_string($value) : 
			default : return 's';
		}
	}
}