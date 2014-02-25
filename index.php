<?php

// set maximum execution time
ini_set('max_execution_time', 7200);

// require back-end
require_once('./php_convert_db.php');

// initialize message
$message = '';

// process request
$v = (isset($_REQUEST['v']) && is_string($_REQUEST['v']) ? trim($_REQUEST['v']) : '__run__');
switch ($v)
{
	case '__run__':
	{ // run conversion
		$obj = new PHP_Convert_DB();
		$obj->auto_migrate();
	}
	break;
	case 'all_done':
	{ // done with conversion
		$message = 'Congratulations, conversion is now complete!';
	}
	break;
	default:
	{ // wrong message	
		$message = 'Invalid request.';
	}
}

// print message
print $message;

?>