<?php
use \PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Template;


global $log;
$log = [];
function logit($data){
	global $log;
	$log[] = $data;
}

/**
* @group Dictionary
*/
class TemplateClassTests extends TestCase{
	use \Bootstrap\Test;

	function __construct(){
		$this->class = \Grithin\Template::class;
	}


	function test_parse(){
		$this->class = \Grithin\Template::class;

		$expect = 'bob';
		$input = ['bob'];
		$this->assert_equal_standard($expect, $input, 'parse', 'string');

		$expect = 'bob boberson';
		$input = ['bob ${bob}', ['bob'=>'boberson']];
		$this->assert_equal_standard($expect, $input, 'parse', 'variable');

		$expect = 'bob ';
		$input = ['bob ${bill}', ['bob'=>'boberson']];
		$this->assert_equal_standard($expect, $input, 'parse', 'missing variable');

		$expect = 'bob ';
		$input = ['bob ${bill}', ['bob'=>'boberson'], null, ['error_on'=>['variable'=>true]]];
		$this->expect_exception($input, 'parse');

		$obj = new StdClass;
		$obj->name = 'bob';
		$obj->bob = function(){
			return 'bob';
		};
		$obj->upper = function($v){
			return strtoupper($v);
		};

		$expect = 'bob ';
		$input = ['bob ${missing|}', ['bob'=>'boberson'], $obj];
		$this->expect_exception($input, 'parse');


		$expect = 'bob ';
		$input = ['bob ${missing|}', ['bob'=>'boberson'], $obj];
		$this->expect_exception($input, 'parse');


		$expect = 'bob bob';
		$input = ['bob ${bob|}', ['bob'=>'boberson'], $obj];
		$this->assert_equal_standard($expect, $input, 'parse', 'function, no args');

		$expect = 'bob BOB';
		$input = ['bob ${upper|bob}', ['bob'=>'boberson'], $obj];
		$this->assert_equal_standard($expect, $input, 'parse', 'function, arg');


		$expect = 'bob bob';
		$input = ['bob ${name}', [], $obj];
		$this->assert_equal_standard($expect, $input, 'parse', 'class property');

		$anon_obj = new class([]) extends ArrayObject{};
		$anon_obj['name'] = 'bob2';
		$anon_obj->upper = $obj->upper;

		$expect = 'bob BOB2';
		$input = ['bob ${upper|${name}}', ['bob'=>'boberson'], $anon_obj];
		$this->assert_equal_standard($expect, $input, 'parse', 'class array access key');
	}


	function test_constructor(){

		$template = new Template(['directory'=>__DIR__]);
		$this->assertEquals(__DIR__.'/', $template->directory, 'memoized faliure');





		$exception_caught = false;
		try{
			$template = new Template(['directory'=>__DIR__.'/templates/']);
		}catch(\Exception $e){
			$exception_caught = true;
		}
		$this->assertEquals(true, $exception_caught, 'exception on no matching directory');

		$helpers = [
			'name' => 'bob',
			'upper' => function($x){ return strtoupper($x);}
		];

		$template = new Template(['directory'=>__DIR__.'/includes/templates/', 'helpers'=>$helpers]);
		$this->class = $template;



		$match = array_intersect(array_keys($helpers), array_keys($template->helpers));
		$expect = array_keys($helpers);
		$this->assertEquals($expect, $match, 'helpers added');


		$expect = 'bob';
		$input = ['plain'];
		$this->assert_equal_standard($expect, $input, 'get', 'plain template');

		$expect = 'bob||';
		$input = ['variable'];
		$this->assert_equal_standard($expect, $input, 'get', 'template with variables');

		$expect = 'bill|bill|bill';
		$input = ['variable', ['name'=>'bill']];
		$this->assert_equal_standard($expect, $input, 'get', 'template with variables, pass in');


		$expect = 'bob|||bob|bob';
		$input = ['helper_vars'];
		$this->assert_equal_standard($expect, $input, 'get', 'helper vars');

		$expect = 'bill|bill|bill|bob|bob';
		$input = ['helper_vars', ['name'=>'bill']];
		$this->assert_equal_standard($expect, $input, 'get', 'helper var overwritten');

		$expect = 'BOB|BOB|BOB';
		$input = ['helper_func'];
		$this->assert_equal_standard($expect, $input, 'get', 'class array access key');

		$expect = '<h1>bob</h1>';
		$input = ['child_simple'];
		$this->assert_equal_standard($expect, $input, 'get', 'simple child');

		$expect = '<h1>bob</h1><div>section content</div>';
		$input = ['child_sectioned'];
		$this->assert_equal_standard($expect, $input, 'get', 'child sectioned');

		$expect = 'got1got2';
		$input = ['1/2/relative_get'];
		$this->assert_equal_standard($expect, $input, 'get', 'inner template relative pathing');

		$expect = "bob2<h1>bob2<b>sue<i>jan</i></b><b>dan</b>bob2</h1>";
		$input = ['include_many', ['name'=>'bob2']];
		$this->assert_equal_standard($expect, $input, 'get', 'many children, many parents');
	}
}