<? $template->parent('layout/sectionalised'); ?>

<?	$template->section('header') ?>

<span>I'm in the header</span>

<?	$template->close_section() ?>

<span>I'm in the body, from test_sectionalised.php</span>

<?	$template->section('footer') ?>

<span>I'm in the footer</span>

<?	$template->close_section() ?>
