<?php
namespace SQL\Enums;

use Error;
use SQL\Builder;

enum Op: string{
	case EQ = '=';
	case LT = '<';
	case GT = '>';
	case LTE = '<=';
	case GTE = '>=';
	case NOT = '<>';
	case BETWEEN = 'BETWEEN';
	case LIKE = 'LIKE';
	case IN = 'IN';
	case EXISTS = 'EXISTS';

	/**
	 * Parse column value to match the filter statement 
	 *
	 * @param string $column
	 * @param mixed $value
	 * @return void
	 */
	public function parse(string $column, mixed $value){
		$op = $this->value;

		if(in_array($this, [self::BETWEEN, self::IN]) && !is_array($value))
			throw new Error('Operator needs the value to be an array');
			
		switch($this){
			case Op::BETWEEN : 
				if(count($value) !== 2)
					throw new Error('Between operator requires array to have 2 values only');

				return sprintf('%s %s ? AND ?', $column, $op);
			break;
			case Op::IN : 
				return sprintf("%s %s (%s)", $column, $op, trim(str_repeat('?,', count($value)), ','));
			break;
			case Op::EXISTS : 
				if(!is_a($value, Builder::class))
					throw new Error('Value is not Builder class');
				return sprintf("%s (%s)", $op, $value);
			break;
			default : 
				return sprintf("%s %s ?", $column, $op);
			break;
		}
	}
}