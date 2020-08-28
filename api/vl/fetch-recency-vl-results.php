<?php

// PURPOSE : Fetch Results using serial_no field which is used to
// store the recency id from third party apps (for eg. in DRC)

// serial_no field in db was unused so we decided to use it to store recency id

ini_set('memory_limit', -1);
header('Content-Type: application/json');

include_once(APPLICATION_PATH . "/includes/MysqliDb.php");
include_once(APPLICATION_PATH . '/models/General.php');
include_once(APPLICATION_PATH . "/vendor/autoload.php");


session_unset(); // no need of session in json response
$general = new General($db);




$sampleCode = !empty($_REQUEST['s']) ? explode(",", filter_var($_REQUEST['s'], FILTER_SANITIZE_STRING)) : null;
$recencyId = !empty($_REQUEST['r']) ? explode(",", filter_var($_REQUEST['r'], FILTER_SANITIZE_STRING)) : null;
$apiKey = !empty($_REQUEST['x-api-key']) ? filter_var($_REQUEST['x-api-key'], FILTER_SANITIZE_STRING) : 'APIKEY';
$from = !empty($_REQUEST['f']) ? filter_var($_REQUEST['f'], FILTER_SANITIZE_STRING) : null;
$to = !empty($_REQUEST['t']) ? filter_var($_REQUEST['t'], FILTER_SANITIZE_STRING) : null;;

if (!$apiKey) {
    $response = array(
        'status' => 'failed',
        'timestamp' => time(),
        'error' => 'API Key invalid',
        'data' => array()
    );
    echo json_encode($response);
    exit(0);
}

if (!$sampleCode && !$recencyId && (!$from || !$to)) {
    $response = array(
        'status' => 'failed',
        'timestamp' => time(),
        'error' => 'Mandatory request params missing in request. Expected Recency IDs or Date Range',
        'data' => array()
    );
    echo json_encode($response);
    exit(0);
}

try {

    $sQuery = "SELECT vl.sample_code, 
                    vl.remote_sample_code,
                    vl.serial_no as `recency_id`,
                    vl.sample_collection_date,
                    vl.sample_received_at_vl_lab_datetime,
                    vl.sample_registered_at_lab,
                    vl.sample_tested_datetime,
                    vl.is_sample_rejected,
                    vl.result,
                    samptype.sample_name as `specimen_type`,
                    sampstatus.status_name as `sample_status`,
                    f.facility_name as `collection_facility_name`,
                    lab.facility_name as `testing_lab_name`,
                    testreason.test_reason_name as `reason_for_testing`,
                    rejreason.rejection_reason_name as `rejection_reason`

                        FROM vl_request_form as vl 
                        LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id 
                        LEFT JOIN facility_details as lab ON vl.lab_id=lab.facility_id 
                        LEFT JOIN r_vl_sample_type as samptype ON samptype.sample_id=vl.sample_type 
                        INNER JOIN r_sample_status as sampstatus ON sampstatus.status_id=vl.result_status 
                        LEFT JOIN r_vl_test_reasons as testreason ON testreason.test_reason_id=vl.reason_for_vl_testing 
                        LEFT JOIN r_sample_rejection_reasons as rejreason ON rejreason.rejection_reason_id=vl.reason_for_sample_rejection
                        
                        WHERE (serial_no is not null)";



    if (!empty($recencyId)) {
        $recencyId = implode("','", $recencyId);
        $sQuery .= " AND serial_no IN ('$recencyId') ";
    }

    if (!empty($sampleCode)) {
        $sampleCode = implode("','", $sampleCode);
        $sQuery .= " AND sample_code IN ('$sampleCode') ";
    }

    if (!empty($from) && !empty($to)) {
        $sQuery .= " AND DATE(sample_collection_date) between '$from' AND '$to' ";
    }

    $sQuery .= " ORDER BY sample_collection_date ASC ";

    $rowData = $db->rawQuery($sQuery);

    if (!$rowData) {
        $response = array(
            'status' => 'failed',
            'timestamp' => time(),
            'error' => 'No matching data',
            'data' => $rowData

        );
        echo json_encode($response);
        exit(0);
    }

    $payload = array(
        'status' => 'success',
        'timestamp' => time(),
        'data' => $rowData
    );

    echo json_encode($payload);
    exit(0);
} catch (Exception $exc) {

    $payload = array(
        'status' => 'failed',
        'timestamp' => time(),
        'error' => $exc->getMessage(),
        'data' => array()
    );

    echo json_encode($payload);

    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
    exit(0);
}