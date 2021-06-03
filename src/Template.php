<?
/**
Wrapping:
	for a got template, CURRENT, allow CURRENT to indicate a PARENT template ($template->parent(PARENT)).  Call PARENT after CURRENT to allow wrapping (see get_template doc).
linear display:
	simply call get_template within templates
*/

namespace Grithin;

use \Exception;

class Template{
	public $helpers = [];

	/**
	@param	options	{
		folder: <template folder>
		control_folder: <control folder>
		instance_vars: <vars avaiable as '$instance[]' in templates>
	}
	*/
	function __construct($options=[]){

		//++ resolve template folder {

		if(empty($options['folder'])){
			# attempt to find template folder at first file level, or at one higher
			$first_file = \Grithin\Reflection::firstFileExecuted();

			$options['folder'] = dirname($first_file).'/template/';
			if(!is_dir($options['folder'])){
				$options['folder'] = dirname($first_file).'/../template/';
			}
		}
		$options['folder'] = realpath($options['folder']).'/';

		if(empty($options['folder'])){
			throw new \Exception('View folder doesn\'t exist');
		}

		//++ }

		# resolve control folder
		if(empty($options['control_folder'])){
			$options['control_folder'] = realpath($options['folder'].'../control');
		}
		if(substr($options['control_folder'],-1) != '/'){
			$options['control_folder'] .= '/'; # `folder` should end with `/`
		}


		$this->helpers = ['template'=>$this, 'Template'=>$this];
		if($options['helpers']){
			$this->helpers = array_merge($this->helpers, $options['helpers']);
		}

		$this->options = $options;

		$this->url_path = substr($_SERVER['REQUEST_URI'],1);//request uri always starts with '/'
	}


	/// setter for $parent
	function parent($parent){
		$this->parent_stack[] = $parent;
	}
	public $parent_stack = []; # it is necessary to use a stack because components within a template may have their own parents (thus a single $parent variable would not work, and a stack must be used for linear progression)
	public $inner;///< a buffer for the inner template content
	public $current_template; #< the current template file
	///used to get the content of a single template file
	/**
	@param	template	string path to template file relative to the templateFolder.  .php is appended to this path.
	@param	vars	variables to extract and make available to the template file
	@return	output from a template

	@note
		there are 2 ways to refer to an inner parented template from the outer template
		1.	use the section creation functions
		2.	use $template->inner variable created for the parent
	*/
	function get_template($template,$vars=[]){
		$this->parent_stack[] = '';
		# ensure there is an `all` variable, so template can know all variables that were passed to it
		# use ex: $Template->backend_data['page_data'] = $all;
		if(empty($vars['all'])){
			$vars['all'] = $vars;
		}

		$used_vars = array_merge($this->helpers, $vars);

		ob_start();
		if(substr($template,-4) != '.php'){
			$template = $template.'.php';
		}

		#+ handle relative paths by making them relative to the calling template, if there is a calling template {
		if($this->current_template && substr($template, 0, 2) == './' || substr($template, 0, 3) == '../'){
			$template = \Grithin\Files::absolute_path($this->current_template.'/../'.$template);
		}
		#+ }

		#+ maintain a current_template variable, accounting for nesting {
		$previous_current = $this->current_template;
		$this->current_template = $template;
		#+ }


		\Grithin\Files::req($this->options['folder'].$template,$used_vars);
		$output = ob_get_clean();

		$parent = array_pop($this->parent_stack);

		if($parent){ # will either be '', indicating no parent, or the parent template for this particular template
			$this->inner = $output;
			$template = $parent;
			$output = $this->get_template($template,$vars);
			unset($this->inner);
			array_pop($this->parent_stack);
		}
		#+ maintain a current_template variable, accounting for nesting {
		$this->current_template = $previous_current;
		#+ }

		return $output;
	}
	/// alias for get_template
	function get($template,$vars=[]){
		return $this->get_template($template,$vars);
	}

	# get template name from a control path
	public function from_control_path($path, $template_name=null){
		#+ sanity check {
		$characters = strlen($this->options['control_folder']);
		if($this->options['control_folder'] != substr($path, 0, $characters)){
			throw new Exception('control path does not match parameter');
		}
		#+ }

		$relative_current_control_file = substr($path,$characters);
		if($template_name){
			return dirname($relative_current_control_file).'/'.$template_name;
		}else{
			return $relative_current_control_file;
		}
	}

	# try to find the current control file from the debug stack
	public function current_control_file($back = 6){
		if($back){ #< go back as far as 5 stack items looking for the control folder
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $back);
		}else{
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS);
		}

		$characters = strlen($this->options['control_folder']);
		foreach($trace as $item){
			if($this->options['control_folder'] == substr($item['file'], 0, $characters)){
				return $item['file'];
			}
		}

		if(!$back){ # attempt a full backtrace
			return $this->current_control_file(null);
		}

		throw new Exception('could not find control file in debug stack');
	}
	public function template_relative_path_from_current($template_name=null){
		return $this->from_control_path($this->current_control_file(), $template_name);
	}

	/// get the template corresponding to the current control.  Must call from within the control.  I recommend you use `from` instead, since it doesn't have the overhead of a backtrace
	public function get_current($vars=[]){
		$template_file = $this->template_relative_path_from_current();
		return $this->get($template_file, $vars);
	}
	public function from_current($template_name=null, $vars=[]){
		$template_file = $this->template_relative_path_from_current($template_name);
		return $this->get($template_file, $vars);
	}
	# find template based on input control file path
	function from($file, $vars=[]){
		return $this->get($this->from_control_path($file), $vars);
	}

	/// display and end
	function end_template($template, $vars=[]){
		echo $this->get_template($template, $vars);
		exit;
	}
	/// alias for end_template
	function end($template, $vars=[]){
		return $this->end_template($template, $vars);
	}
	/// display and end
	function end_current($vars=[]){
		echo $this->get_current($vars);
		exit;
	}
	/// display and end
	function end_from($file, $vars=[]){
		echo $this->get($this->from_control_path($file), $vars);
		exit;
	}


//+	section handling {
	public $open_section = '';
	public $sections = [];
	/// starts or ends a section container
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
	/// start section, ensuring no other section is open
	public function section_start($name){
		if($this->open_section){
			throw new \Exception('Section is already open: "'.$this->open_section.'"');
		}
		$this->section($name);
	}
	# alias, deprecated
	public function start_section($name){
		call_user_func_array([$this, 'section_start'], func_get_args());
	}
	/// end section, ensuring a section is open
	public function section_end(){
		if(!$this->open_section){
			throw new \Exception('No open section');
		}
		$this->section();
	}
	# alias, deprecated
	public function close_section(){
		call_user_func_array([$this, 'section_end'], func_get_args());
	}
	# close open section and open new section
	public function section_next($name){
		if(!$this->open_section){
			throw new \Exception('No open section');
		}
		$this->sections[$this->open_section] = ob_get_clean();
		$this->open_section = $name;
		ob_start();
	}


	/// gets the string collected under a section name.
	public function get_section($name){
		return $this->sections[$name];
	}
//+	}


	# inline interpretted templates
	/* about
	-	handles applying functions from a class, or from global context, by using '${'functionName'|'param'}'
	-	handles variables mapped to keys in $variables using '${'keyName'}'
		-	the variables will be resolved using $class_instance->__get() if not found withing the variable dictionary
	-	handles nesting:
		ex: ${urlencode|${customer_first_name}}
	*/
	/* param
	variables: < dictionary of variables >
	class_instance : < an optional class to which function names are referenced and unfound variables, using __get >
	*/
	/* examples
	# basic variable replacement
	$x = inline('hello ${bob}, ', ['bob'=>'mokneys']);

	# globally contexted function with variable replacement
	$x = inline('hello ${ucwords|${bob}}, ', ['bob'=>'mokneys']);

	# class instance provided contexted function with variable replacement
	class MakeZero{ function doit($x){ return preg_replace('@.@','0', $x); } }
	$x = inline('hello ${doit|${bob}}, ', ['bob'=>'mokneys'], new MakeZero);

	*/
	/*	param overload
	$variables can be the $class_instance
	*/
	static function inline($message, $variables=[], $class_instance=null){
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
		if(in_array('ArrayAccess', class_implements($class_instance))){
			$class_has_variables = true;
		}


		for($i=0,$f=-1;$i<$charCount;$i++){
			$write = true;
			if($message[$i] == '$'){
				$indicater = 1;
			}elseif($indicater == 1 && $message[$i] == '{'){
				$write = false;
				//remove previous character from previous depth
				$chars[$depth] = substr($chars[$depth],0,-1);

				$indicater = 0;
				$depth++;
			}elseif($message[$i] == '\\'){
				$escaped = true;
			}elseif($depth > 0 && $message[$i] == '}' && !$escaped){ # special syntax brackets have closed, check meaning
				$write = false;
				$parts = explode('|',$chars[$depth],2);
				if(count($parts) > 1){ # this is a function
					if($class_instance){
						$value = call_user_func([$class_instance,$parts[0]], $parts[1]);
					}else{ # default to global context for functions
						$value = call_user_func($parts[0],$parts[1]);
					}

				}else{ # this is a variable
					if(array_key_exists($parts[0], $variables)){ # check variable dictionary
						$value = $variables[$parts[0]];
					}elseif($class_has_variables){
						$value = $class_instance[$parts[0]];
					}else{ # no variable found, return blank
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