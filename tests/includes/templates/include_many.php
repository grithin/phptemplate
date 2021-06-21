<?php $Template->parent('layout_child_var')?>
<?= $name ?>
<?= $Template->get('included1', ['name'=>'sue']) ?>
<?= $Template->get('included2', ['name'=>'dan']) ?>
<?= $Template->vars['name'] ?>