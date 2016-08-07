<? $template->parent('layout/basic_with_vars'); ?>

<?	$template->section('header') ?>

<span>I'm in the header</span>

<?	$template->close_section() ?>

<?= $template->get_template('component/header', ['id'=>'header_id']) ?>

<span>I'm in the body of <?= $file ?></span>
