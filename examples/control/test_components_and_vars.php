<?
# Template will use the control_path to find the corresponding template file
return $tmpl->from(__FILE__, ['file'=>'test_components_and_vars.php', 'title'=>'child variables available to wrappers']);