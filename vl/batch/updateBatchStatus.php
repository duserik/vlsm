<?php
ob_start();
#require_once('../../startup.php');


$general = new \Vlsm\Models\General($db);
$tableName = "batch_details";
try {
    $id = $_POST['id'];
    $status = array(
        'batch_status' => $_POST['value']
    );
    $db = $db->where('batch_id', $id);
    $db->update($tableName, $status);
    $result = $id;
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
echo $result;
