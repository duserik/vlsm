<?php
session_start();
ob_start();
#require_once('../../startup.php');  
include_once(APPLICATION_PATH.'/includes/MysqliDb.php');
include_once(APPLICATION_PATH.'/models/General.php');
$general=new General($db);
$tableName="vl_request_form";
$tableName1="activity_log";
$vlTestReasonTable="r_vl_test_reasons";
$fDetails="facility_details";
try {
     //system config
     $systemConfigQuery ="SELECT * from system_config";
     $systemConfigResult=$db->query($systemConfigQuery);
     $sarr = array();
     // now we create an associative array so that we can easily create view variables
     for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
          $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
     }
     $status = 6;
     if(isset($_POST['sampleValidity']) && $_POST['sampleValidity']!=''){
          $status = 4;
     }
     if($sarr['user_type']=='remoteuser'){
          $status = 9;
     }
     $instanceId = '';
     if(isset($_SESSION['instanceId'])){
          $instanceId = $_SESSION['instanceId'];
     }
     //lab
     if(isset($_POST['newLab']) && trim($_POST['newLab'])!="" && trim($_POST['labId']) == 'other'){
          $labQuery ="SELECT facility_id FROM facility_details where facility_name='".$_POST['newLab']."' OR facility_name='".strtolower($_POST['newLab'])."' OR facility_name='".ucfirst(strtolower($_POST['newLab']))."'";
          $labResult = $db->rawQuery($labQuery);
          if(!isset($labResult[0]['facility_id'])){
               $data=array(
                    'facility_name'=>$_POST['newLab'],
                    'vlsm_instance_id'=>$instanceId,
                    'facility_type'=>2,
                    'country'=>4,
                    'status'=>'active'
               );
               $id=$db->insert('facility_details',$data);
               $_POST['labId'] = $id;
          }else{
               $_POST['labId'] = $labResult[0]['facility_id'];
          }
     }
     //update facility code
     if(trim($_POST['fCode'])!=''){
          $fData = array('facility_code'=>$_POST['fCode']);
          $db=$db->where('facility_id',$_POST['fName']);
          $id=$db->update($fDetails,$fData);
     }
     //dob
     if(isset($_POST['dob']) && trim($_POST['dob'])!=""){
          $_POST['dob']=$general->dateFormat($_POST['dob']);
     }else{
          $_POST['dob'] = NULL;
     }
     //set female section values
     $isPatientPregnant = NULL;
     $treatmentStage = NULL;
     if(isset($_POST['gender']) && trim($_POST['gender'])== "female"){
          $isPatientPregnant = (isset($_POST['patientPregnant']) && $_POST['patientPregnant']!='') ? $_POST['patientPregnant'] :  NULL;
          $treatmentStage = (isset($_POST['lineOfTreatment']) && $_POST['lineOfTreatment']!='') ? $_POST['lineOfTreatment'] :  NULL;
     }
     //sample collected date
     if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!=""){
          $sampleDate = explode(" ",$_POST['sampleCollectionDate']);
          $_POST['sampleCollectionDate']=$general->dateFormat($sampleDate[0])." ".$sampleDate[1];
     }else{
          $_POST['sampleCollectionDate'] = NULL;
     }
     //vl suspected treatment failure at
     if(isset($_POST['newSuspectedTreatmentFailureAt']) && trim($_POST['newSuspectedTreatmentFailureAt'])!="" && trim($_POST['suspectedTreatmentFailureAt']) == "other"){
          $_POST['suspectedTreatmentFailureAt'] = $_POST['newSuspectedTreatmentFailureAt'];
     }
     //sample received date
     if(isset($_POST['sampleReceivedDate']) && trim($_POST['sampleReceivedDate'])!=""){
          $sampleReceivedDateLab = explode(" ",$_POST['sampleReceivedDate']);
          $_POST['sampleReceivedDate']=$general->dateFormat($sampleReceivedDateLab[0])." ".$sampleReceivedDateLab[1];
     }else{
          $_POST['sampleReceivedDate'] = NULL;
     }
     //sample testing date at lab
     if(isset($_POST['sampleTestingDateAtLab']) && trim($_POST['sampleTestingDateAtLab'])!=""){
          $sampleTestingDateAtLab = explode(" ",$_POST['sampleTestingDateAtLab']);
          $_POST['sampleTestingDateAtLab']=$general->dateFormat($sampleTestingDateAtLab[0])." ".$sampleTestingDateAtLab[1];
     }else{
          $_POST['sampleTestingDateAtLab'] = NULL;
     }
     //set repeat sample and rejection reason
     $repeatSampleCollection = NULL;
     $rejectionReason = NULL;
     if(isset($_POST['sampleValidity']) && $_POST['sampleValidity']=='invalid'){
          if(isset($_POST['repeatSampleCollection']) && $_POST['repeatSampleCollection']!=""){
               $repeatSampleCollection = $_POST['repeatSampleCollection'];
          }
          if(isset($_POST['newRejectionReason']) && trim($_POST['newRejectionReason'])!="" && trim($_POST['rejectionReason']) =="other"){
               $rejectionReasonQuery ="SELECT rejection_reason_id FROM r_sample_rejection_reasons where rejection_reason_name='".$_POST['newRejectionReason']."' OR rejection_reason_name='".strtolower($_POST['newRejectionReason'])."' OR rejection_reason_name='".ucfirst(strtolower($_POST['newRejectionReason']))."'";
               $rejectionResult = $db->rawQuery($rejectionReasonQuery);
               if(!isset($rejectionResult[0]['rejection_reason_id'])){
                    $data=array(
                         'rejection_reason_name'=>$_POST['newRejectionReason'],
                         'rejection_type'=>'general',
                         'rejection_reason_status'=>'active',
                         'updated_datetime'=>$general->getDateTime(),
                    );
                    $id=$db->insert('r_sample_rejection_reasons',$data);
                    $rejectionReason = $id;
               }else{
                    $rejectionReason = $rejectionResult[0]['rejection_reason_id'];
               }
          }else{
               $rejectionReason = $_POST['rejectionReason'];
          }
     }
     //reviewed by date time
     if(isset($_POST['reviewedByDatetime']) && trim($_POST['reviewedByDatetime'])!=""){
          $reviewedByDatetime = explode(" ",$_POST['reviewedByDatetime']);
          $_POST['reviewedByDatetime']=$general->dateFormat($reviewedByDatetime[0])." ".$reviewedByDatetime[1];
     }else{
          $_POST['reviewedByDatetime'] = NULL;
     }
     //date of ART initiation
     if(isset($_POST['dateOfArtInitiation']) && trim($_POST['dateOfArtInitiation'])!=""){
          $_POST['dateOfArtInitiation']=$general->dateFormat($_POST['dateOfArtInitiation']);
     }else{
          $_POST['dateOfArtInitiation'] = NULL;
     }
     //ART regimen
     if(isset($_POST['newArtRegimen']) && trim($_POST['newArtRegimen'])!= "" && trim($_POST['artRegimen'])== "other"){
          $_POST['artRegimen'] = $_POST['newArtRegimen'];
          $checkArtQuery ="SELECT art_id FROM r_art_code_details where art_code='".$_POST['newArtRegimen']."' OR art_code='".strtolower($_POST['newArtRegimen'])."' OR art_code='".ucfirst(strtolower($_POST['newArtRegimen']))."'";
          $checkArtResult = $db->rawQuery($checkArtQuery);
          if(!isset($checkArtResult[0]['art_id'])){
               $data=array(
                    'art_code'=>$_POST['newArtRegimen'],
                    'nation_identifier'=>'zam',
                    'parent_art'=>'4',
                    'updated_datetime'=>$general->getDateTime(),
               );
               $db->insert('r_art_code_details',$data);
          }
     }
     //vl test reason
     if(isset($_POST['newVlTestReason']) && trim($_POST['newVlTestReason'])!="" && trim($_POST['vlTestReason'])== "other"){

          $checkTestReasonQuery ="SELECT test_reason_id FROM r_vl_test_reasons where test_reason_name='".$_POST['newVlTestReason']."' OR test_reason_name='".strtolower($_POST['newVlTestReason'])."' OR test_reason_name='".ucfirst(strtolower($_POST['newVlTestReason']))."'";
          $checkTestReasonResult = $db->rawQuery($checkTestReasonQuery);
          if(!isset($checkTestReasonResult[0]['test_reason_id'])){
               $data=array(
                    'test_reason_name'=>$_POST['newVlTestReason'],
                    'test_reason_status'=>'active'
               );
               $result=$db->insert('r_vl_test_reasons',$data);
               $_POST['vlTestReason'] = $result;
          }
     }
     //last vl test date
     if(isset($_POST['lastViralLoadTestDate']) && trim($_POST['lastViralLoadTestDate'])!=""){
          $_POST['lastViralLoadTestDate']=$general->dateFormat($_POST['lastViralLoadTestDate']);
     }else{
          $_POST['lastViralLoadTestDate'] = NULL;
     }
     if($sarr['user_type']=='remoteuser'){
          $sampleCode = 'remote_sample_code';
          $sampleCodeKey = 'remote_sample_code_key';
     }else{
          $sampleCode = 'sample_code';
          $sampleCodeKey = 'sample_code_key';
     }
     
     $vldata=array(
          'vlsm_instance_id'=>$instanceId,
          'vlsm_country_id'=>'4',
          //'sample_code_title'=>(isset($_POST['sampleCodeTitle']) && $_POST['sampleCodeTitle']!='') ? $_POST['sampleCodeTitle'] :  'auto',
          'sample_code_format'=>(isset($_POST['sampleCodeFormat']) && $_POST['sampleCodeFormat']!='') ? $_POST['sampleCodeFormat'] :  NULL,
          'facility_id'=>(isset($_POST['fName']) && $_POST['fName']!='') ? $_POST['fName'] :  NULL,
          'request_clinician_name'=>(isset($_POST['reqClinician']) && $_POST['reqClinician']!='') ? $_POST['reqClinician'] :  NULL,
          'request_clinician_phone_number'=>(isset($_POST['reqClinicianPhoneNumber']) && $_POST['reqClinicianPhoneNumber']!='') ? $_POST['reqClinicianPhoneNumber'] :  NULL,
          'lab_id'=>(isset($_POST['labId']) && $_POST['labId']!='') ? $_POST['labId'] :  NULL,
          'patient_art_no'=>(isset($_POST['patientArtNo']) && $_POST['patientArtNo']!='') ? $_POST['patientArtNo'] :  NULL,
          'patient_mobile_number'=>(isset($_POST['patientPhoneNumber']) && $_POST['patientPhoneNumber']!='') ? $_POST['patientPhoneNumber'] :  NULL,
          'patient_dob'=>(isset($_POST['dob']) && $_POST['dob']!='') ? $_POST['dob'] :  NULL,
          'patient_age_in_years'=>(isset($_POST['ageInYears']) && $_POST['ageInYears']!='' && $_POST['ageInYears'] >0) ? $_POST['ageInYears'] :  NULL,
          'patient_age_in_months'=>(isset($_POST['ageInMonths']) && $_POST['ageInMonths']!='' && $_POST['ageInMonths'] >0) ? $_POST['ageInMonths'] :  NULL,
          'patient_gender'=>(isset($_POST['gender']) && $_POST['gender']!='') ? $_POST['gender'] :  NULL,
          'is_patient_pregnant'=>$isPatientPregnant,
          'line_of_treatment'=>$treatmentStage,
          'treatment_initiated_date'=>$_POST['dateOfArtInitiation'],
          'current_regimen'=>(isset($_POST['artRegimen']) && trim($_POST['artRegimen'])!='') ? $_POST['artRegimen'] :  NULL,
          'reason_for_vl_testing'=>(isset($_POST['vlTestReason']) && trim($_POST['vlTestReason'])!='') ? $_POST['vlTestReason'] :  NULL,
          'last_viral_load_date'=>$_POST['lastViralLoadTestDate'],
          'last_viral_load_result'=>(isset($_POST['lastViralLoadResult']) && trim($_POST['lastViralLoadResult'])!='') ? $_POST['lastViralLoadResult'] :  NULL,
          'number_of_enhanced_sessions'=>(isset($_POST['enhancedSession']) && $_POST['enhancedSession']!='') ? $_POST['enhancedSession'] :  NULL,
          'sample_type'=>(isset($_POST['specimenType']) && $_POST['specimenType']!='') ? $_POST['specimenType'] :  NULL,
          'sample_reordered'=>(isset($_POST['sampleReordered']) && $_POST['sampleReordered']!='') ? $_POST['sampleReordered'] :  'no',
          'sample_collection_date'=>$_POST['sampleCollectionDate'],
          'sample_visit_type'=>(isset($_POST['visitType']) && $_POST['visitType']!='') ? $_POST['visitType'] :  NULL,
          'vl_sample_suspected_treatment_failure_at'=>(isset($_POST['suspectedTreatmentFailureAt']))?$_POST['suspectedTreatmentFailureAt']:NULL,
          'sample_collected_by'=>(isset($_POST['collectedBy']) && $_POST['collectedBy']!='') ? $_POST['collectedBy'] :  NULL,
          'facility_comments'=>(isset($_POST['facilityComments']) && $_POST['facilityComments']!='') ? $_POST['facilityComments'] :  NULL,
          'sample_received_at_vl_lab_datetime'=>$_POST['sampleReceivedDate'],
          'sample_tested_datetime'=>$_POST['sampleTestingDateAtLab'],
          'sample_test_quality'=>(isset($_POST['sampleValidity']) && $_POST['sampleValidity']!='') ? $_POST['sampleValidity'] :  NULL,
          'repeat_sample_collection'=>$repeatSampleCollection,
          'reason_for_sample_rejection'=>$rejectionReason,
          'result_value_absolute'=>(isset($_POST['vlResult']) && $_POST['vlResult']!='' && $_POST['result'] == 'actual_copies') ? $_POST['vlResult'] :  NULL,
          'result_value_absolute_decimal'=>(isset($_POST['vlResult']) && $_POST['vlResult']!='' && $_POST['result'] == 'actual_copies') ? number_format((float)$_POST['vlResult'], 2, '.', '') :  NULL,
          'result'=>(isset($_POST['result']) && trim($_POST['result'])!= '' && trim($_POST['result']) == 'actual_copies') ? $_POST['vlResult'] : $_POST['result'],
          'result_reviewed_by'=>(isset($_POST['reviewedBy']) && $_POST['reviewedBy']!='') ? $_POST['reviewedBy'] :  NULL,
          'result_reviewed_datetime'=>$_POST['reviewedByDatetime'],
          'lab_contact_person'=>(isset($_POST['labContactPerson']) && $_POST['labContactPerson']!='') ? $_POST['labContactPerson'] :  NULL,
          'approver_comments'=>(isset($_POST['labComments']) && $_POST['labComments']!='') ? $_POST['labComments'] :  NULL,
          'result_status'=>$status,
          'request_created_by'=>$_SESSION['userId'],
          'request_created_datetime'=>$general->getDateTime(),
          'last_modified_by'=>$_SESSION['userId'],
          'last_modified_datetime'=>$general->getDateTime(),
          'manual_result_entry'=>'yes'
     );


     $vldata['patient_first_name'] = $general->crypto('encrypt',$_POST['patientFname'],$vldata['patient_art_no']);
     $vldata['patient_last_name'] = $general->crypto('encrypt',$_POST['surName'],$vldata['patient_art_no']);

     if(isset($_POST['vlSampleId']) && $_POST['vlSampleId']!=''){
        $db=$db->where('vl_sample_id',$_POST['vlSampleId']);
        $id=$db->update($tableName,$vldata);
    }else{
        //check existing sample code
        $existSampleQuery ="SELECT ".$sampleCode.",".$sampleCodeKey." FROM vl_request_form where ".$sampleCode." ='".trim($_POST['sampleCode'])."'";
        $existResult = $db->rawQuery($existSampleQuery);
        if(isset($existResult[0][$sampleCodeKey]) && $existResult[0][$sampleCodeKey]!=''){
            $sCode = $existResult[0][$sampleCodeKey] + 1;
            $strparam = strlen($sCode);
            $zeros = substr("000", $strparam);
            $maxId = $zeros.$sCode;
            $_POST['sampleCode'] = $_POST['sampleCodeFormat'].$maxId;
            $_POST['sampleCodeKey'] = $maxId;
        }
        
     if($sarr['user_type']=='remoteuser'){
          $vldata['remote_sample_code'] = (isset($_POST['sampleCode']) && $_POST['sampleCode']!='') ? "R".$_POST['sampleCode'] :  NULL;
          $vldata['remote_sample_code_key'] = (isset($_POST['sampleCodeKey']) && $_POST['sampleCodeKey']!='') ? $_POST['sampleCodeKey'] :  NULL;
          $vldata['remote_sample'] = 'yes';
     }else{
          $vldata['sample_code'] = (isset($_POST['sampleCode']) && $_POST['sampleCode']!='') ? $_POST['sampleCode'] :  NULL;
          $vldata['serial_no'] = (isset($_POST['sampleCode']) && $_POST['sampleCode']!='') ? $_POST['sampleCode'] :  NULL;
          $vldata['sample_code_key'] = (isset($_POST['sampleCodeKey']) && $_POST['sampleCodeKey']!='') ? $_POST['sampleCodeKey'] :  NULL;
     }
     $id=$db->insert($tableName,$vldata);
    }
     if($id>0){
          $_SESSION['alertMsg']="VL request added successfully";
          //Add event log
          $eventType = 'add-vl-request-zam';
          $action = ucwords($_SESSION['userName']).' added a new request data with the sample code '.$_POST['sampleCode'];
          $resource = 'vl-request-zam';

          $general->activityLog($eventType,$action,$resource);
          
        //   $data=array(
        //        'event_type'=>$eventType,
        //        'action'=>$action,
        //        'resource'=>$resource,
        //        'date_time'=>$general->getDateTime()
        //   );
        //   $db->insert($tableName1,$data);

          $barcode = "";
          if(isset($_POST['printBarCode']) && $_POST['printBarCode'] =='on'){
               $s = $_POST['sampleCode'];
               $facQuery="SELECT * FROM facility_details where facility_id=".$_POST['fName'];
               $facResult = $db->rawQuery($facQuery);
               $f = ucwords($facResult[0]['facility_name'])." | ".$_POST['sampleCollectionDate'];
               $barcode = "?barcode=true&s=$s&f=$f";
          }

          if(isset($_POST['saveNext']) && $_POST['saveNext']=='next'){
               $_SESSION['treamentIdZam'] = $id;
               //$_SESSION['facilityIdZam'] = $_POST['clinicName'];
               header("location:addVlRequest.php".$barcode);
          }else{
               $_SESSION['treamentIdZam'] = '';
               $_SESSION['facilityIdZam'] = '';
               unset($_SESSION['treamentIdZam']);
               unset($_SESSION['facilityIdZam']);
               header("location:vlRequest.php".$barcode);
          }
     }else{
          $_SESSION['alertMsg']="Please try again later";
     }

} catch (Exception $exc) {
     error_log($exc->getMessage());
     error_log($exc->getTraceAsString());
}