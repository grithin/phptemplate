<?php
/**
Wrapping:
	for a got template, CURRENT, allow CURRENT to indicate a PARENT template ($template->parent(PARENT)).  Call PARENT after CURRENT to allow wrapping (see get doc).
linear display:
	simply call get within templates
*/

/* @notes
-	using linux path instead of DIRECTORY_SEPARATOR
*/



namespace Grithin;

use \Exception;

class Template{
	public $helpers = [];

	public $paths = [];
	public $directory; # for convenience.  Most projects only have one view path
	/** params
	< options > < t:object >:
		directory: < template directory >
		paths: < list of paths to check for view files.  Directory is automatically prepended to this >
		helpers: < a dictionary of values or functions for use within templates >
	}
	*/
	function __construct($options=[]){

		//++ resolve template folder {

		if(empty($options['directory'])){
			# attempt to find template folder at first file level, or at one higher
			$first_file_dir = dirname(\Grithin\Reflection::firstFileExecuted());

			$options['directory'] = $first_file_dir.'template/';
			if(!is_dir($options['directory'])){
				$options['directory'] = $first_file_dir.'/../template/';
				if(!is_dir($options['directory'])){
					$options['directory'] = $first_file_dir.'/';
				}
			}
		}
		$this->paths = !empty($options['paths']) ? $options['paths'] : [];
		if($options['directory']){
			array_unshift($this->paths, $options['directory']);
		}

		$this->directory = $this->paths[0].'/'; # for convenience.  Most projects only have one view path

		foreach($this->paths as $path){
			if(!is_dir($path)){
				throw new \Exception('View path doesn\'t exist "'.$path.'"');
			}
		}


		//++ }

		$this->helpers = ['template'=>$this, 'Template'=>$this, 't'=>$this];
		if(!empty($options['helpers'])){
			$this->helpers = array_merge($this->helpers, $options['helpers']);
		}

		$this->options = $options;
	}


	function __get($name){
		if(in_array($name, ['vars', 'template'])){
			return $this->current[$name];
		}
	}



	/** about
	it is necessary to use a stack because components within a template may have their own parents
 	A single $parent variable would be overwritten by partials that had parents within the parented template.
	With a stack, that stack fits the linear progression of adding and removing parents as control flows from a partial and back to the including template
	*/
	protected $parent_stack = [];
	/** setter for $parent */
	public function parent($parent, $vars=[]){
		$this->parent_stack[] = [$parent, $vars];
	}

	public $inner;///< a buffer for the inner template content
	/**
	Flow of "current" variable from included templates.  Note, `prev` is contexted to function call
	-> current = t1
		-> current = t2, (prev = t1)
			-> current = p2, (prev = t2)
			<- current = t2
		<- current = t1
	*/
	public $current; #< the current template file
	/** used to get the content of a single template file */
	/**
	@param	template	string path to template file relative to the templateFolder.  .php is appended to this path.
	@param	vars	variables to extract and make available to the template file
	@return	output from a template

	@note
		there are 2 ways to refer to an inner parented template from the outer template
		1.	use the section creation functions
		2.	use $template->inner variable created for the parent
	*/
	/** params
	< template > < absolute or relative path from view paths > < relative path to the current template `get` is being called from >
	*/
	function get($template,$vars=[]){
		$this->parent_stack[] = [];
		# ensure there is an `all` variable, so template can know all variables that were passed to it
		# use ex: $Template->backend_data['page_data'] = $all;

		# set arrayed access for variables
		$used_vars = array_merge(['vars'=>$vars, 'helpers'=>$this->helpers], $vars);

		# combine helpers with passed variables
		$used_vars = array_merge($this->helpers, $used_vars);
		$used_vars['Template'] = $this;

		ob_start();
		if(substr($template,-4) != '.php'){
			$template = $template.'.php';
		}


		$file_location = false;
		#+ handle relative paths by making them relative to the calling template, if there is a calling template {
		if($this->current &&
			(substr($template, 0, 2) == './' || substr($template, 0, 3) == '../'))
		{
			$file_location = \Grithin\Files::resolve_relative($this->current['file'].'/../'.$template);
		}
		#+ }

		if(!$file_location){
			foreach($this->paths as $path){
				if(file_exists($path.'/'.$template)){
					$file_location = $path.'/'.$template;
					break;
				}
			}
		}
		if(!$file_location){
			throw new \Exception("missing template file \"$template\"");
		}
		#+ maintain a current template variables, accounting for nesting {
		$previous_current = $this->current;
		$this->current = ['template'=>$template, 'vars'=>$vars, 'file'=>$file_location];
		#+ }
		\Grithin\Files::req($file_location,$used_vars);
		$output = ob_get_clean();

		$parent = array_pop($this->parent_stack);

		if($parent){ # will either be '', indicating no parent, or the parent template for this particular template
			$this->inner = $output;
			$template = $parent[0];
			$output = $this->get($template,array_merge($vars, $parent[1]));
			unset($this->inner);
			array_pop($this->parent_stack); # Clear the padding at the start
		}
		#+ maintain a current_template variable, accounting for nesting {
		$this->current = $previous_current; # b/c this is a local variable, it will be unique on each get(), allowing for infinite depth
		#+ }

		return $output;
	}


//+	section handling {
	protected $open_section = '';
	public $sections = [];
	/** starts or ends a section container */
	/**
	if called with name:
		if no section open, open section
		if section open, place output into keyed array, close section, and then open new section
	if called without name
		if section open, put output into keyed array, close section

	@note If section already exists, will overwrite
	*/
	public function section($name=''){
		if($this->open_section){
			$this->sections[$this->open_section] = ob_get_clean();
			if($name && $name == $this->open_section){ #< if open section is same as name provided, consider this a close command
				$name = '';
			}
			$this->open_section = '';
		}
		if($name){
			$this->open_section = $name;
			ob_start();
		}
	}
	/** end section, ensuring a section is open */
	public function section_end(){
		if(!$this->open_section){
			throw new \Exception('No open section');
		}
		$this->section();
	}
//+	}


	/** Parse a special syntax template */

	/** about
	It is sometimes necessary to pass an string to intepret with functionality and variables, while having the need that such interpretation does not cause insecurity (like with mail templates generated by administration).  To do this, a special language is provided, and you can control what functions and variables are used in the parsing.

	*/

	/** example template
	Hello ${name},
	It's the current year (${date|Y})!

	You were born on ${date|${dob}}
	*/
	/** example
	$obj = new StdClass;
	$obj->upper = function($v){
		return strtoupper($v);
	};

	Template::parse('Hello ${upper|${name}}', ['name'=>'bob'], $obj);
	*/

	/** notes
	-	by default, global functions are turned off.  See params `options`
	-	functions only accept one, string input.  But, you can split the string up
	-	you can nest variables and other functions within functions
		-	'${'functionName'|'param'}'
	*/

	/** About variables
	One of
	-	param `variables`
	-	Object implementing ArrayAccess
	-	Object property
	*/

	/** About functions
	One of
	-	Object method
	-	Object property closure
	*/


	/** params
	message: < the string to parse >
	variables: < dictionary of variables >
	class_instance : < a class to which function names are referenced and unfound variables are sought, using __get >
	options:
		use_global_functions: < t:bool ><d:false>< whether to fallback to a global function if function is not found within class >
		error_on:
			variable: < t:bool > < if a variable is missing, whether to throw an exception >
			function: < t:bool > < if a function is missing, whether to throw an exception >
	*/
	/** examples
	/** basic variable replacement */
	$x = inline('hello ${bob}, ', ['bob'=>'mokneys']);

	/** globally contexted function with variable replacement */
	$x = inline('hello ${ucwords|${bob}}, ', ['bob'=>'mokneys']);

	/** class instance provided contexted function with variable replacement */
	class MakeZero{ function doit($x){ return preg_replace('@.@','0', $x); } }
	$x = inline('hello ${doit|${bob}}, ', ['bob'=>'mokneys'], new MakeZero);

	*/
	/**	param overload
	$variables can be the $class_instance
	*/
	static function parse($message, $variables=[], $class_instance=null, $options=[]){
		$defaults = [
			'use_global_functions'=>false,
			'error_on'=>[
				'variable' => false,
				'function' => true
			]
		];
		$options = Dictionary::merge_deep($defaults, $options);

		//escape only partially implemented
		$charCount = strlen($message);
		$depth = 0;

		#+ $variables is a class instance, move the variables around {
		if(is_object($variables)){
			$class_instance = $variables;
			$variables = [];
		}
		#+ }

		$class_has_variables = false;
		if($class_instance){
			if(in_array('ArrayAccess', class_implements($class_instance))){
				$class_has_variables = true;
			}
		}



		for($i=0,$f=-1;$i<$charCount;$i++){
			$write = true;
			if($message[$i] == '$'){
				$indicater = 1;
			}elseif($indicater == 1 && $message[$i] == '{'){ # '${' start
				$write = false;
				# clear the '$' from chars (since it is not a string literal, but a var/func)
				$chars[$depth] = substr($chars[$depth],0,-1);

				$indicater = 0;
				$depth++;
			}elseif($message[$i] == '\\'){
				$escaped = true;
			}elseif($depth > 0 && $message[$i] == '}' && !$escaped){ # brackets have closed, check meaning
				$write = false;
				$parts = explode('|',$chars[$depth],2);
				if(count($parts) > 1){ # use of '|' means this is a function
					if($class_instance){
						try{
							$key = $parts[0];
							if(($class_instance->$key) instanceof \Closure){
								# try calling it using either defined methods or __call
								$value = call_user_func(($class_instance->$key), $parts[1]);
							}else{
								# try calling it using either defined methods or __call
								$value = call_user_func([$class_instance,$parts[0]], $parts[1]);
							}

						}catch(\Exception $e){
							if(substr($e->getMessage(),0,14) !='call_user_func'){
								throw $e;
							}
							throw new \Exception('Missing function: '.$parts[0]);
						}

					}else{ # default to global context for functions
						if($options['use_global_functions']){
							$value = call_user_func($parts[0],$parts[1]);
						}else{
							throw new \Exception('Missing function: '.$parts[0]);
						}
					}

				}else{ # this is a variable
					$key = $parts[0];
					if(array_key_exists($key, $variables)){ # check variable dictionary
						$value = $variables[$key];
					}elseif($class_has_variables){
						$value = $class_instance[$key];
					}elseif(
						$class_instance
						&& property_exists($class_instance, $key)
						&& !(($class_instance->$key) instanceof \Closure))
					{
						# use class propperty if it is not a method
						$value = $class_instance->$key;
					}else{ # no variable found, return blank
						if($options['error_on']['variable']){
							throw new \Exception('Missing variable: '.$key);
						}
						$value = '';
					}
				}
				//clear out current depth, and return to previous depth
				$chars[$depth] = '';
				$depth--;
				$chars[$depth] .= $value;
			}else{
				$escaped = false;
			}
			if($write){
				$chars[$depth] .= $message[$i];
			}

		}
		return $chars[0];
	}
}