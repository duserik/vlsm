<?php
#require_once('../../startup.php');


$general = new \Vlsm\Models\General($db);

$tableName1 = "batch_details";
$tableName2 = "vl_request_form";


if (isset($_POST['type']) && $_POST['type'] == 'vl') {
    $tableName2 = "vl_request_form";
    $table2PrimaryColumn = "vl_sample_id";
    $editFileName = 'editBatch.php';
} else if (isset($_POST['type']) && $_POST['type'] == 'eid') {
    $tableName2 = "eid_form";
    $table2PrimaryColumn = "eid_id";
} else if (isset($_POST['type']) && $_POST['type'] == 'covid19') {
    $tableName2 = "form_covid19";
    $table2PrimaryColumn = "covid19_id";
} else if (isset($_POST['type']) && $_POST['type'] == 'hepatitis') {
    $tableName2 = "form_hepatitis";
    $table2PrimaryColumn = "hepatitis_id";
}


$batchId = base64_decode($_POST['id']);

$vlQuery = "SELECT $table2PrimaryColumn from $tableName2 as vl where sample_batch_id=$batchId";
$vlInfo = $db->query($vlQuery);
if (count($vlInfo) > 0) {

    $value = array('sample_batch_id' => NULL);
    $db = $db->where('sample_batch_id', $batchId);

    $db->update($tableName2, $value);
}

$db = $db->where('batch_id', $batchId);
$delId = $db->delete($tableName1);
if ($delId > 0) {
    echo '1';
} else {
    echo '0';
}
