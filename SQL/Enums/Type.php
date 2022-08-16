<?php
namespace SQL\Enums;

enum Type{
	case SELECT;
	case INSERT;
	case UPDATE;
	case DELETE;
}