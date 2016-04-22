# Grithin's PHP Template

## Design Goal
-	Provide template hierarchy functionality (template-template wrapping)
-	Provide linear and intra-linear template concatenation
-	Provide template variable context
-	Provide simple control/template path framework assumption

## History
This is a greatly reduced version of the View class from my Brushfire framework.  The biggest design difference being: the template determines the parent - instead of the caller.


## Use

_Appending Sections_
```html
<?	$template->section('footer'); ?>
<?= $template->get_section('footer'); ?>

&copy;Rights Holder 2006-<?= date('Y') ?>

<?	$template->section(); ?>

```