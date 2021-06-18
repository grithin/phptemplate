<?php $template->parent('layout/basic'); ?>

<span>I'm using helper function to escape stuff <?= $hsc('<script> malicious(); </script>') ?></span>
