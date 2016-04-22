<?
/**
Wrapping:
	for a got template, CURRENT, allow CURRENT to indicate a PARENT template ($template->parent(PARENT)).  Call PARENT after CURRENT to allow wrapping (see get_template doc).
linear display:
	simply call get_template within templates
*/

namespace Grithin;

class Template{
	/**
	@param	options	{
		folder: <template folder>
		control_folder: <control folder>
		instance_vars: <vars avaiable as '$instance[]' in templates>
	}
	*/
	function __construct($options=[]){
		if(!$options['folder']){
			# attempt to find template folder at first file level, or at one higher
			$first_file = \Grithin\Reflection::firstFileExecuted();

			$options['folder'] = dirname($first_file).'/template/';
			if(!is_dir($options['folder'])){
				$options['folder'] = dirname($first_file).'/../template/';
			}
		}
		$options['folder'] = realpath($options['folder']).'/';

		if(!$options['folder']){
			throw new \Exception('View folder doesn\'t exist');
		}

		if(!$options['control_folder']){
			$options['control_folder'] = realpath($options['folder'].'../control');
		}

		$this->options = $options;

		$this->url_path = substr($_SERVER['REQUEST_URI'],1);//request uri always starts with '/'
	}

	# get last control name
	function control_name(){
		if($this->options['control_folder']){
			$trace = \Grithin\Reflection::externalCaller();
			$directory = dirname($trace['file']);
			$characters = strlen($this->options['control_folder']);
			if($this->options['control_folder'] == substr($directory, 0, $characters)){
				return explode('.', substr($trace['file'],$characters + 1))[0];
			}
		}
	}
	/// setter for $parent
	function parent($parent){
		$this->parent_stack[] = $parent;
	}
	public $parent_stack = [];
	public $inner;///< a buffer for the inner template content
	///used to get the content of a single template file
	/**
	@param	template	string path to template file relative to the templateFolder.  .php is appended to this path.
	@param	vars	variables to extract and make available to the template file
	@return	output from a template

	@note
		there are 2 ways to refer to an inner parentped template from the outer template
		1.	use the section creation functions
		2.	use $template->inner variable created for the parent
	*/
	function get_template($template,$vars=[]){
		$this->parent_stack[] = '';
		$vars['template'] = $this;

		ob_start();
		if(substr($template,-4) != '.php'){
			$template = $template.'.php';
		}
		\Grithin\Files::req($this->options['folder'].$template,null,$vars);
		$output = ob_get_clean();

		$parent = array_pop($this->parent_stack);

		if($parent){
			$this->inner = $output;
			$template = $parent;
			$output = $this->get_template($template,$vars);
			unset($this->inner);
			array_pop($this->parent_stack);
		}

		return $output;
	}
	/// alias for get_template
	function get($template,$vars=[]){
		return $this->get_template($template,$vars);
	}

	/// get the template corresponding to the current control, assuming corresponding path
	function get_current($vars=[]){
		return $this->get_template($this->control_name(), $vars);
	}

	/// display and end
	function end_template($template, $vars=[]){
		echo $this->get_template($template, $vars);
		exit;
	}
	/// display and end
	function end_current($vars=[]){
		echo $this->get_current($vars);
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
			if($name && $name == $this->open_section){
				$name = '';
			}
			$this->open_section = '';
		}
		if($name){
			$this->open_section = $name;
			ob_start();
		}
	}
	/// convenience function, calls section()
	public function close_section(){
		$this->section();
	}
	/// gets the string collected under a section name.
	public function get_section($name){
		return $this->sections[$name];
	}
//+	}
}