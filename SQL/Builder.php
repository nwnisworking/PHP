<?php
namespace SQL;

use SQL\Enums\Type;
use SQL\Enums\Op;
use Error;

class Builder{
	/** SQL statement type */
	protected ?Type $type = null;
	/** Query table */
	private string $table = '';
	/** Unsanitize query values */
	public array $values = [];
	/** Query columns */
	private array $columns = [];
	/** Is the column set an associative array */
	private bool $is_column_assoc = false;
	/** Query in order of its execution. */
	private array $query = [
		'JOIN'=>[],
		'WHERE'=>[],
		'GROUP'=>[],
		'HAVING'=>[],
		'ORDER'=>[],
		'LIMIT'=>null,
		'OFFSET'=>null
	];
	#region statement 
	/**
	 * Selects data from the database 
	 *
	 * @param string $table
	 * @param array $column
	 * @return Builder
	 */
	public function select(string $table, array $column = ['*']){
		if($this->is_assoc($column))
			throw new Error('Column does not accept associative array');

		$this->type = Type::SELECT;
		$this->table = $table;
		$this->columns = $column;

		return $this;
	}

	/**
	 * Inserts data from the database
	 *
	 * @param string $table
	 * @param array $column
	 * @return Builder
	 */
	public function insert(string $table, array $column){
		$this->type = Type::INSERT;
		$this->table = $table;
		$this->columns = $column;

		if($this->is_assoc($column)){
			$this->add_value(array_values($column));
			$this->is_column_assoc = true;
		}

		return $this;
	}

	/**
	 * Updates data from the database
	 *
	 * @param string $table
	 * @param array $column
	 * @return Builder
	 */
	public function update(string $table, array $column){
		$this->type = Type::UPDATE;
		$this->table = $table;
		$this->columns = $column;

		if($this->is_assoc($column)){
			$this->add_value(array_values($column));
			$this->is_column_assoc = true;
		}
		else{
			throw new Error('Update must contain key and value');
		}

		return $this;
	}

	/**
	 * Delete data from the database
	 *
	 * @param string $table
	 * @return Builder
	 */
	public function delete(string $table){
		$this->type = Type::DELETE;
		$this->table = $table;

		return $this;
	}

	#endregion

	#region query
	/**
	 * Join is used to combine rows from two or more tables, based on a related column between them
	 *
	 * @param string $type INNER, FULL, LEFT, RIGHT
	 * @param string $table
	 * @param array $match 
	 * @return Builder
	 */
	public function join(string $type, string $table, array $columns = []){
		if(!in_array($type, ['INNER', 'FULL', 'LEFT', 'RIGHT']))
			throw new Error('Unrecognized Join type');
		
		if(!$this->is_assoc($columns))
			throw new Error('column param needs associative array');

		$this->add_query('JOIN', "$type JOIN $table ON ".$this->concat_assoc($columns, ' AND '));
		return $this;
	}

	/**
	 * Filters row by matching the column conditions
	 *
	 * @param string $column
	 * @param Op|string $op
	 * @param mixed $value
	 * @param string $glue
	 * @return void
	 */
	public function where(string $column, Op|string $op, mixed $value, string $glue = 'AND'){
		$this->add_query('WHERE', $this->__where_having($column, $op, $value, $glue));
		return $this;
	}

	/**
	 * Filters row by matching conditions where WHERE cannot be used with aggregate function
	 *
	 * @param string $column
	 * @param Op|string $op
	 * @param mixed $value
	 * @param string $glue
	 * @return void
	 */
	public function having(string $column, Op|string $op, mixed $value, string $glue = 'AND'){
		$this->add_query('HAVING', $this->__where_having($column, $op, $value, $glue));
		return $this;
	}

	/**
	 * Groups rows that have the same values into summary rows
	 *
	 * @param string ...$columns
	 * @return void
	 */
	public function group(string ...$columns){
		$this->add_query('GROUP', $columns);
		return $this;
	}

	/**
	 * Orders row by columns
	 *
	 * @param string $column
	 * @param string $dir
	 * @return void
	 */
	public function order(string $column, string $dir = 'ASC'){
		$this->add_query('ORDER', "$column $dir");
		return $this;
	}

	/**
	 * Limit returning row to set length
	 *
	 * @param integer $length
	 * @return void
	 */
	public function limit(int $length){
		$this->add_query('LIMIT', $length);
		return $this;
	}

	/**
	 * Skips rows based on the offset
	 *
	 * @param integer $offset
	 * @return void
	 */
	public function offset(int $offset){
		$this->add_query('OFFSET', $offset);
		return $this;
	}

	#endregion

	#region helper
	/**
	 * Concatenate key and value together as a string and bind together with the rest of key and value
	 *
	 * @param array $assoc
	 * @param string $bind
	 * @return void
	 */
	private function concat_assoc(array $assoc, string $bind){
		if(!$this->is_assoc($assoc))
			throw new Error('assoc is not an associative array');

		return urldecode(http_build_query($assoc, '', $bind));
	}

	/**
	 * Add query 
	 *
	 * @param string $query
	 * @param mixed $value
	 * @return void
	 */
	private function add_query(string $query, mixed $value){
		if(is_array($this->query[$query]))
			array_push($this->query[$query], ...(is_array($value) ? $value : [$value]));
		else
			$this->query[$query] = $value;
	}

	/**
	 * Create question mark with comma 
	 *
	 * @param integer $total
	 * @return string
	 */
	private function qn(int $total){
		return trim(str_repeat('?,', $total), ',');
	}

	/**
	 * Push value whether it's an array or string | int
	 *
	 * @param mixed $value
	 * @return void
	 */
	private function add_value(mixed $value){
		array_push($this->values, ...(is_array($value) ? $value : [$value]));
	}

	/**
	 * Identify if given array is an associative array 
	 *
	 * @param array $arr
	 * @return boolean
	 */
	private function is_assoc(array $arr){
		$key = array_keys($arr);
		return $key !== array_keys($key);
	}

	/**
	 * Base method for where and having
	 *
	 * @param string $column
	 * @param Op|string $op
	 * @param mixed $value
	 * @param string $glue
	 * @return void
	 */
	private function __where_having(string $column, Op|string $op, mixed $value, string $glue = 'AND'){
		if(is_string($op) && !($op = Op::tryFrom($op)))
			throw new Error('Operator not valid');

		$opr = $op->parse($column, $value);

		if($op === Op::EXISTS)
			$value = get_object_vars((object)$value)['values'];
		

		$this->add_value($value);
		return "$opr $glue";
	}

	#endregion

	public function __toString(){
		$table = $this->table;
		$key = array_keys($this->columns);
		$value = array_values($this->columns);
		$query = '';
		$v_qn = function(){return '?';};

		switch($this->type){
			case Type::SELECT : 
				$query = "SELECT ".implode(',', $value);
				
				if(!empty($table))
					$query.= " FROM $table";

			break;
			case Type::INSERT : 
				$query = sprintf("INSERT INTO $table(%s)", implode(',', $this->is_column_assoc ? $key : $value));

				if($this->is_column_assoc)
					$query.= sprintf(" VALUES(%s)", $this->qn(count($key)));
			break;
			case Type::UPDATE : 
				$query = sprintf("UPDATE $table SET %s", $this->concat_assoc(array_map($v_qn, $this->columns), ','));
			break;
			case Type::DELETE : 
				$query = "DELETE FROM $table";
			break;
		}

		$query.= ' ';

		foreach($this->query as $k=>$v){
			if(!isset($v) || is_array($v) && count($v) === 0) continue;

			switch($k){
				case 'JOIN' : 
					$query.= implode(' ', $v).' ';
				break;
				case 'WHERE' : 
				case 'HAVING' :
					$query.= "$k ".implode(' ', $v);
					$query = rtrim(rtrim($query, 'AND '), 'OR ').' ';
				break; 
				case 'GROUP' : 
				case 'ORDER' : 
					$query.= "$k BY ".implode(',', $v).' ';
				break;
				case 'LIMIT' : 
				case 'OFFSET' : 
					$query.= "$k $v";
				break;
			}
		}

		return trim($query);
	}
}