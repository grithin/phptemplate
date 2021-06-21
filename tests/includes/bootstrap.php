<?php
namespace Bootstrap;


$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use \Grithin\Debug;

\Grithin\GlobalFunctions::init();

Trait Test{
	public $class;

	public function message_from_input($input, $method){
		$input_as_string = Debug::json_pretty($input);
		return "\tMethod: $method\t\ninput: $input_as_string";
	}
	public function assert_equal_standard($expect, $input, $method, $message=''){
		$message .= $this->message_from_input($input, $method);
		$output = call_user_func_array([$this->class, $method], $input);
		$this->assertEquals($expect, $output, $message);
	}
	public function expect_exception($input, $method, $message='', $exception_type=\Exception::class, $message_pattern=''){
		$message_part = $this->message_from_input($input, $method);
		try{
			$output = call_user_func_array([$this->class, $method], $input);
		}catch(\Exception $e){
			if(!($e instanceof $exception_type)){
				# $this->fail($messaage.' Wrong exception thrown: "'.get_class($e).'".  Expecting: "'.$exception_type.'". '.$message_part);
				$this->assertEquals($exception_type, get_class($e), $message);
			}
			$this->assertEquals(1, 1, $message); # success
			return true;
		}
		$this->assertEquals($exception_type, null, $message);
	}
}