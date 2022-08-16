<?php
namespace SQL;

use PDO;
use mysqli;

abstract class DB extends Builder{
	protected PDO|mysqli|null $conn;
	protected string $user = 'root';
	protected string $pass = '';
	protected string $database = '';
	protected string $host = 'localhost';
	protected int $port = 3306;
	protected string $db_type;

	abstract public function connect();

	abstract public function execute();

	abstract public function close();

	/**
	 * Parse url as login details
	 *
	 * @param string $url
	 * @return static
	 */
	public static function url(string $url){
		$url = parse_url('//'.$url);
		return self::create_db(['database'=>trim($url['path'], '/'), 'host'=>'localhost', 'port'=>3306, ...$url]);
	}

	/**
	 * Parse array as login details
	 *
	 * @param array $detail
	 * @return void
	 */
	public static function array(array $detail){
		return self::create_db(['host'=>'localhost', 'port'=>3306, ...$detail]);
	}

	/**
	 * Parse var as login details
	 *
	 * @param string $user
	 * @param string $pass
	 * @param string $database
	 * @param string $host
	 * @param integer $port
	 * @return void
	 */
	public static function credential(string $user, string $pass, string $database, string $host = 'localhost', int $port = 3306){
		return self::create_db(['user'=>$user, 'pass'=>$pass, 'database'=>$database, 'host'=>$host, 'port'=>$port]);
	}
	
	/**
	 * Create inherited DB 
	 *
	 * @param array $data
	 * @return DB
	 */
	private static function create_db(array $data){
		$keys = array_keys(get_class_vars(static::class));
		$db = new static;
		$db->db_type = 'mysql';

		foreach($keys as $v)
			if(isset($data[$v]))
				$db->{$v} = $data[$v];

		return $db;
	}
	
}