<?php $template->parent('layout/basic_with_vars'); ?>

<?php	$template->section('header') ?>

<span>I'm in the header</span>

<?php	$template->close_section() ?>

<?= $template->get_template('component/header', ['id'=>'header_id']) ?>

<span>I'm in the body of <?= $file ?></span>
