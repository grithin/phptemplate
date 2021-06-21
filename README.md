# About
A customizable, simple templating system, using vanilla PHP.

__Features__
-	Template hierarchy (a template can have a parent wrapper)
-	Sectionalised parts (can define sections for inclusion later)
-	Componentised parts (can require another template within a template)
-	Expectable inclusion paths
	-	relies on filesystem hierarchy
	-	allows for relative path inclusion (relative to current template)



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


