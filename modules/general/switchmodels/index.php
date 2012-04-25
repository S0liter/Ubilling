<?php
if(cfr('SWITCHM')) {


if (isset($_POST['newsm'])) {
    ub_SwitchModelAdd($_POST['newsm'],$_POST['newsmp']);
    rcms_redirect("?module=switchmodels");
}

if (isset($_GET['deletesm'])) {
	if (!empty($_GET['deletesm'])) {
  ub_SwitchModelDelete($_GET['deletesm']);
  rcms_redirect("?module=switchmodels");
  }
}

if (!isset($_GET['edit'])) {
show_window('',  wf_Link('?module=switches', 'Available switches', true, 'ubButton'));
show_window(__('Available switch models'),  web_SwitchModelsShow());
 } else {
     //show editing form
     $editid=vf($_GET['edit'],3);
     
     //if someone post changes
     if (wf_CheckPost(array('editmodelname'))) {
         simple_update_field('switchmodels', 'modelname', $_POST['editmodelname'], "WHERE `id`='".$editid."' ");
         simple_update_field('switchmodels', 'ports', $_POST['editports'], "WHERE `id`='".$editid."' ");
         log_register("SWITCHMODEL CHANGE ".$editid);
         rcms_redirect("?module=switchmodels");
     }
     
     $modeldata=zb_SwitchModelGetData($editid);
     $editinputs=wf_TextInput('editmodelname', 'Model', $modeldata['modelname'], true, '20');
     $editinputs.=wf_TextInput('editports', 'Ports', $modeldata['ports'], true, '5');
     $editinputs.=wf_Submit('Save');
     $editform=wf_Form('', 'POST', $editinputs, 'glamour');
     show_window(__('Switch model edit'),$editform);
     show_window('', wf_Link('?module=switchmodels', 'Back', true, 'ubButton'));
  }
}
else {
	show_error(__('Access denied'));
}
?>