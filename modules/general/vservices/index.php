<?php
if(cfr('VSERVICES')) {

    if (isset ($_POST['newfee'])) {
        $tagid=$_POST['newtagid'];
        $price=$_POST['newfee'];
        $cashtype=$_POST['newcashtype'];
        $priority=$_POST['newpriority'];
        zb_VserviceCreate($tagid, $price, $cashtype, $priority);
        rcms_redirect("?module=vservices");
    }
    
    if (isset($_GET['delete'])) {
        $vservid=$_GET['delete'];
        zb_VsericeDelete($vservid);
        rcms_redirect("?module=vservices");
    }

    web_VservicesShow();
    
    
}
else {
	show_error(__('Access denied'));
}
?>
