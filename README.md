# About
A customizable, simple templating system, using vanilla PHP.

__Features__
-	Template hierarchy (a template can have a parent wrapper)
-	Sectionalised parts (can define sections for inclusion later)
-	Componentised parts (can require another template within a template)
-	Expectable inclusion paths
	-	relies on filesystem hierarchy
	-	allows for relative path inclusion (relative to current template)

## A Note On PHP-HTML Integration
Spaghetti code is bad, but integrating PHP code with HTML does not have to result in spaghetti code.

If you follow a [sane set of rules](https://github.com/CLR-MO/standard-coding#html-integration), and you do not mix control code with view code, you will be fine.

The reasons people present for using templating systems with custom syntax are often invalid:
-	Better syntax.  [This depends on your point of view](https://github.com/CLR-MO/standard-coding#compairson-to-twig)
-	Short helper functions.
This tool can have short helper functions.  We can make `$e` do html escaping:
```html
<span><?= $e($name) ?></span>
```

The one reason that remains, after discarding avoidance of spaghetti code, is security.  Some template engines allow you to prevent execution of arbitrary PHP code, which allows you to reduce the damage designers might do when using code.  
Although security can be done even when the template is written with native PHP code, (`get_all_tokens`), I have had no need for such security so I have not extended this tool to do that, and if you need such security, you should go with another templating engine that has this feature (twig) or convince me to spend the time extending this tool.

## Why?
Probably mainly because of a bad experience in 2006 when I had to rewrite various parts of the smarty templating engine to fix caching problems the company was having.  But, apart from that:

-	I don't see the benefit in adding more custom syntax developers have to learn on top of PHP
-	Sometimes it is necessary to have PHP code that formats data prior to it being displayed in the template.   With custom syntax templating engines, this PHP code is forced to be in the control, even though it is view code.
-	Laravel's Blade templating engine is ridiculous.  Among other things, it passes arguments in to helper functions as a single, unparsed string.  Good luck figuring out how to do a relative path inclusion of another template file within a template.
-	Twig.  If you want to pass variables to a parent template, you have to turn the variables into blocks.  With this engine, not only are template variables that were passed in to the child passed thru to the parent, but the child can add variables to pass to the parent.
-	In general, the feature set of a sub-PHP template will always be less than a PHP template.




## Guide

### Plain Use

```php
$Template = new \Grithin\Template(['directory'=>__DIR__.'/templates/']);
$Template->get('page', ['first_name'=>'bob']);
```

Template files are expected to end in `.php`

You can include another template from within a template
```html
<?= $Template->get('sidemenu') ?>
```
You can even do so relative to the path of the current template

```html
<?= $Template->get('./sidemenu') ?>
```
This might be useful if a site section had its own sidemenu.

The `$Template` variable is injected into the context of the template file.  It can be referenced three ways, `$t`, `$template`, and `$Template`, but only `$Template` is guaranteed not to be overwritten.

These keys are part of the helper array that are provided to templates.  The helper array is a list of variables that are imported into the scope of the template.  They can be values, closures, or anything that can fit inside a variable.

```php
$Template = new Template([
	'helpers'=>[
		'upper'=>function($x){ return strtoupper($x)},
		'name' => 'bob'	]])
```
```html
<span><?= $upper($name) ?></span>
```

The instance level helpers, like `t`, may be overwitten by variables of the same key provided to the `get` call.

```php
$template->get('view_user', ['t'=> 'this will override the template variable for this template file inclusion'])
```

To avoid using a variable that might not exist, the variables passed to `get` are available as keys in `$vars`.
```html
<?= isset($vars['name']) ? $name : 'Default Person' ?>
```

`$vars` represents the variables passed in through `get`.  `$vars` can be overwritten by a passed variable.
If `$vars` is overwritten, it can still be accessed with `$Template->vars`

You can also access class instance helpers that were overwritten
```
<?= $helpers['name'] ?>
<?= $Template->helpers['name'] ?>
```

To get the string identity of the current template, use `<?= $Template->template ?>`



### Sections And Hierarchy
A template can have a parent/layout
```
<?php $Template->parent('layout') ?>

<div> Hello </div>
```

A parent refers to the content of the child with `$Template->inner`

```html
<html><body> <?= $Template->inner ?> </html></body>
```


Templates can also use sections

```html
<!-- create a header section and put meta tag into it -->
<?php	$Template->section('header') ?>
<meta charset="utf-8" >
<?php	$Template->section_end() ?>
```

Parents, or other templates, can use these defined sections
```html
<head>
	<?= $Template->sections['header'] ?>
</head>
<?=
```

You can append an existing section
```html
<?php	$Template->section('header') ?>
<?= $Template->sections['header'] ?>
<meta property="og:type" content= "website" />
<?php	$Template->section_end() ?>
```

You can directly set the section variable if desired
```html
<?php	$Template->sections['title']='The Page Title' ?>
```


#### Parent Complexities
The parent template gets the vars passed to the child template. Though, the child template can also pass variables to the parent, and these will overwrite, based on keys, the child variables within the tempalte
```html
<?php $Template->parent('layout', ['theme'=>'blue']) ?>
```


