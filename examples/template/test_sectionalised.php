<?php $template->parent('layout/sectionalised'); ?>

<?php	$template->section('header') ?>

<span>I'm in the header</span>

<?php	$template->close_section() ?>

<span>I'm in the body, from test_sectionalised.php</span>

<?php	$template->section('footer') ?>

<span>I'm in the footer</span>

<?php	$template->close_section() ?>
