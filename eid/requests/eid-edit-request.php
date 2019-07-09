<?php
ob_start();
$title = "EID | Edit Request";
require_once('../../startup.php');
include_once(APPLICATION_PATH . '/header.php');
include_once(APPLICATION_PATH . '/models/General.php');
?>
<style>
    .ui_tpicker_second_label,
    .ui_tpicker_second_slider,
    .ui_tpicker_millisec_label,
    .ui_tpicker_millisec_slider,
    .ui_tpicker_microsec_label,
    .ui_tpicker_microsec_slider,
    .ui_tpicker_timezone_label,
    .ui_tpicker_timezone {
        display: none !important;
    }

    .ui_tpicker_time_input {
        width: 100%;
    }
</style>



<?php


$labFieldDisabled = '';

if ($sarr['user_type'] == 'remoteuser') {
    $labFieldDisabled = 'disabled="disabled"';
    $vlfmQuery = "SELECT GROUP_CONCAT(DISTINCT vlfm.facility_id SEPARATOR ',') as facilityId FROM vl_user_facility_map as vlfm where vlfm.user_id='" . $_SESSION['userId'] . "'";
    $vlfmResult = $db->rawQuery($vlfmQuery);
}

$general = new General($db);



$rejectionTypeQuery = "SELECT DISTINCT rejection_type FROM r_eid_sample_rejection_reasons WHERE rejection_reason_status ='active'";
$rejectionTypeResult = $db->rawQuery($rejectionTypeQuery);

//sample rejection reason
$rejectionQuery = "SELECT * FROM r_eid_sample_rejection_reasons where rejection_reason_status = 'active'";
$rejectionResult = $db->rawQuery($rejectionQuery);

$condition = "status = 'active'";
if (isset($vlfmResult[0]['facilityId'])) {
    $condition = $condition . " AND facility_id IN(" . $vlfmResult[0]['facilityId'] . ")";
}
$fResult = $general->fetchDataFromTable('facility_details', $condition);


//get lab facility details
$condition = "facility_type='2' AND status='active'";
$lResult = $general->fetchDataFromTable('facility_details', $condition);


$id = base64_decode($_GET['id']);
//$id = ($_GET['id']);
$eidQuery = "SELECT * from eid_form where eid_id=$id";
$eidInfo = $db->rawQueryOne($eidQuery);


$arr = $general->getGlobalConfig();


if ($arr['eid_sample_code'] == 'auto' || $arr['eid_sample_code'] == 'auto2' || $arr['eid_sample_code'] == 'alphanumeric') {
    $sampleClass = '';
    $maxLength = '';
    if ($arr['eid_max_length'] != '' && $arr['eid_sample_code'] == 'alphanumeric') {
         $maxLength = $arr['eid_max_length'];
         $maxLength = "maxlength=" . $maxLength;
    }
} else {
    $sampleClass = 'checkNum';
    $maxLength = '';
    if ($arr['eid_max_length'] != '') {
         $maxLength = $arr['eid_max_length'];
         $maxLength = "maxlength=" . $maxLength;
    }
}


$fileArray = array(
    1 => 'forms/edit-southsudan.php',
    2 => 'forms/edit-zimbabwe.php',
    3 => 'forms/edit-drc.php',
    4 => 'forms/edit-zambia.php',
    5 => 'forms/edit-png.php',
    6 => 'forms/edit-who.php',
    7 => 'forms/edit-rwanda.php',
    8 => 'forms/edit-angola.php',
);

require_once($fileArray[$arr['vl_form']]);

?>

<script>
    function checkSampleNameValidation(tableName, fieldName, id, fnct, alrt) {
        if ($.trim($("#" + id).val()) != '') {
            $.blockUI();
            $.post("/eid/requests/check-sample-duplicate.php", {
                    tableName: tableName,
                    fieldName: fieldName,
                    value: $("#" + id).val(),
                    fnct: fnct,
                    format: "html"
                },
                function(data) {
                    if (data != 0) {
                        <?php if (isset($sarr['user_type']) && ($sarr['user_type'] == 'remoteuser' || $sarr['user_type'] == 'standalone')) { ?>
                            alert(alrt);
                            $("#" + id).val('');
                        <?php } else { ?>
                            data = data.split("##");
                            document.location.href = " /eid/requests/eid-edit-request.php?id=" + data[0] + "&c=" + data[1];
                        <?php } ?>
                    }
                });
            $.unblockUI();
        }
    }

    $(document).ready(function() {
        $('.date').datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd-M-yy',
            timeFormat: "hh:mm TT",
            maxDate: "Today",
            yearRange: <?php echo (date('Y') - 100); ?> + ":" + "<?php echo (date('Y')) ?>"
        }).click(function() {
            $('.ui-datepicker-calendar').show();
        });
        $('.dateTime').datetimepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd-M-yy',
            timeFormat: "HH:mm",
            maxDate: "Today",
            onChangeMonthYear: function(year, month, widget) {
                setTimeout(function() {
                    $('.ui-datepicker-calendar').show();
                });
            },
            yearRange: <?php echo (date('Y') - 100); ?> + ":" + "<?php echo (date('Y')) ?>"
        }).click(function() {
            $('.ui-datepicker-calendar').show();
        });
        //$('.date').mask('99-aaa-9999');
        //$('.dateTime').mask('99-aaa-9999 99:99');
    });
</script>



<?php

include_once(APPLICATION_PATH . '/footer.php');