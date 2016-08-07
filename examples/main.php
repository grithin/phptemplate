<?
$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

/*
-	the `control_folder` option is, by default, set to be in the same parent folder as the template folder, with the name `control`.
-	the `control_folder` is used for finding corresponding template files when using `from`, `end_from`, and `get_current`
*/
$tmpl = new \Grithin\Template(['folder'=>__DIR__.'/template/']);


$include = function($name) use ($tmpl){
	return include __DIR__.'/control/'.$name.'.php';
};


$output = $include('test');
/*<
<html>
	<head></head>
	<body>
		<span>test.php</span>	</body>
</html>
*/

$output = $include('test_sectionalised');
/*<
<html>
	<head></head>
	<body>
		<header>

<span>I'm in the header</span>

		</header>


<span>I'm in the body, from test_sectionalised.php</span>

		<footer>

<span>I'm in the footer</span>

		</footer>
	</body>
</html>
*/

$tmpl->keywords = 'bob, sue, mill, joe';
$output = $include('test_components_and_vars');
/*<
<html>
	<head>
		<title>child variables available to wrappers</title>
		<meta name="keywords" content="bob, sue, mill, joe"/>
	</head>
	<body>


<header id="header_id">

<span>I'm in the header</span>

</header>
<span>I'm in the body of test_components_and_vars.php</span>
	</body>
</html>
*/

$tmpl->helpers['hsc'] = 'htmlspecialchars';
$output = $include('test_helpers');
/*<
<html>
	<head></head>
	<body>

<span>I'm using helper function to escape stuff &lt;script&gt; malicious(); &lt;/script&gt;</span>
	</body>
</html>
*/

