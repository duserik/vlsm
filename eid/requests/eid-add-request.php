<?php
ob_start();
$title = "EID | Add New Request";
#require_once('../../startup.php');
include_once(APPLICATION_PATH . '/header.php');

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


$facilitiesDb = new \Vlsm\Models\Facilities($db);

$healthFacilities = $facilitiesDb->getHealthFacilities('eid');
$testingLabs = $facilitiesDb->getTestingLabs('eid');


$rejectionTypeQuery = "SELECT DISTINCT rejection_type FROM r_eid_sample_rejection_reasons WHERE rejection_reason_status ='active'";
$rejectionTypeResult = $db->rawQuery($rejectionTypeQuery);

//sample rejection reason
$rejectionQuery = "SELECT * FROM r_eid_sample_rejection_reasons where rejection_reason_status = 'active'";
$rejectionResult = $db->rawQuery($rejectionQuery);


foreach ($rejectionTypeResult as $type) {
    $rejectionReason .= '<optgroup label="' . ucwords($type['rejection_type']) . '">';
    foreach ($rejectionResult as $reject) {
        if ($type['rejection_type'] == $reject['rejection_type']) {
            $rejectionReason .= '<option value="' . $reject['rejection_reason_id'] . '">' . ucwords($reject['rejection_reason_name']) . '</option>';
        }
    }
    $rejectionReason .= '</optgroup>';
}

$iResultQuery = "select * from  import_config_machines";
$iResult = $db->rawQuery($iResultQuery);
$machine = array();
foreach ($iResult as $val) {
    $machine[$val['config_machine_id']] = $val['config_machine_name'];
}


$testPlatformResult = $general->getTestingPlatforms('eid');
foreach ($testPlatformResult as $row) {
    $testPlatformList[$row['machine_name']] = $row['machine_name'];
}

$sampleResult = $general->fetchDataFromTable('r_eid_sample_type', "status = 'active'");

$fileArray = array(
    1 => 'forms/add-southsudan.php',
    2 => 'forms/add-zimbabwe.php',
    3 => 'forms/add-drc.php',
    4 => 'forms/add-zambia.php',
    5 => 'forms/add-png.php',
    6 => 'forms/add-who.php',
    7 => 'forms/add-rwanda.php',
    8 => 'forms/add-angola.php',
);

require_once($fileArray[$arr['vl_form']]);

?>

<script>

    function changeFun(){
        if ($('#isSampleRejected').val() == "yes") {
            $('.rejected').show();
            $('#sampleRejectionReason').addClass('isRequired');
            $('#sampleTestedDateTime,#result').val('');
            $('#sampleTestedDateTime,#result').removeClass('isRequired');
        } else {
            $('.rejected').hide();
            $('#sampleRejectionReason').removeClass('isRequired');
            $('#sampleTestedDateTime').addClass('isRequired');
        }

        if ($('#result').val() == "") {
            $('#sampleTestedDateTime').removeClass('isRequired');
        } else {
            $('#sampleTestedDateTime').addClass('isRequired');
        }
    }

    $(document).ready(function() {

        $("#isSampleRejected,#result").on("change", function() {
			changeFun();
		});

        $('.date').datepicker({
            changeMonth: true,
            changeYear: true,
            onSelect: function() {
                $(this).change();
            },
            dateFormat: 'dd-M-yy',
            timeFormat: "hh:mm TT",
            maxDate: "Today",
            yearRange: <?php echo (date('Y') - 100); ?> + ":" + "<?php echo (date('Y')) ?>"
        }).click(function() {
            $('.ui-datepicker-calendar').show();
        });

        $("#childDob").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd-M-yy',
            minDate: "-48m",
            maxDate: "Today",
            onSelect: function(dateText, inst) {
                $("#sampleCollectionDate").datepicker("option", "minDate", $("#childDob").datepicker("getDate"));
                $(this).change();
            }
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
        $('.date').mask('99-aaa-9999');
        $('.dateTime').mask('99-aaa-9999 99:99');
    });


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
                        <?php if ($sarr['user_type'] == 'remoteuser' || $sarr['user_type'] == 'standalone') { ?>
                            alert(alrt);
                            $("#" + id).val('');
                            <?php if ($arr['vl_form'] == '3') { ?>
                                $("#sampleCodeValue").html('').hide();
                            <?php }
                        } else { ?>
                            data = data.split("##");
                            document.location.href = "/eid/requests/eid-edit-request.php?id=" + data[0] + "&c=" + data[1];
                        <?php } ?>
                    }
                });
            $.unblockUI();
        }
    }

    function insertSampleCode(formId, eidSampleId, sampleCode, sampleCodeKey, sampleCodeFormat, countryId, sampleCollectionDate, provinceCode = null, provinceId = null) {
        $.blockUI();
        $.post("/eid/requests/insert-sample.php", {
                sampleCode: $("#" + sampleCode).val(),
                sampleCodeKey: $("#" + sampleCodeKey).val(),
                sampleCodeFormat: $("#" + sampleCodeFormat).val(),
                countryId: countryId,
                sampleCollectionDate: $("#" + sampleCollectionDate).val(),
                provinceCode: provinceCode,
                provinceId: provinceId
            },
            function(data) {
                if (data > 0) {
                    $.unblockUI();
                    document.getElementById("eidSampleId").value = data;
                    document.getElementById(formId).submit();
                } else {
                    $.unblockUI();
                    //$("#sampleCollectionDate").val('');
                    sampleCodeGeneration();
                    alert("We could not save this form. Please try saving again.");
                }
            });
    }

    function calculateAgeInMonths() {
        var dateOfBirth = moment($("#childDob").val(), "DD-MMM-YYYY");
        $("#childAge").val(moment().diff(dateOfBirth, 'months'));
    }
</script>



<?php

include_once(APPLICATION_PATH . '/footer.php');
