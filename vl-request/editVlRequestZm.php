<?php
ob_start();
$autoApprovalFieldStatus = 'show';
if($_SESSION['roleCode'] == "DE"){
  $configQuery="SELECT value FROM global_config WHERE name = 'auto_approval'";
  $configResult=$db->query($configQuery);
  if(isset($configResult) && count($configResult)> 0 && $configResult[0]['value'] == 'no'){
    $autoApprovalFieldStatus = 'hide';
  }
}
if($arr['sample_code']=='auto' || $arr['sample_code']=='alphanumeric'){
  $numeric = '';
}else{
  $numeric = 'checkNum';
}
$aQuery="SELECT * from r_art_code_details where nation_identifier='zmb'";
$aResult=$db->query($aQuery);
//facility details
$facilityQuery="SELECT * from facility_details where facility_id='".$vlQueryInfo[0]['facility_id']."'";
$facilityResult=$db->query($facilityQuery);
if(!isset($facilityResult[0]['facility_state']) || $facilityResult[0]['facility_state']== ''){
  $facilityResult[0]['facility_state'] = 0;
}
$stateName = $facilityResult[0]['facility_state'];
$stateQuery="SELECT * from province_details where province_name='".$stateName."'";
$stateResult=$db->query($stateQuery);
if(!isset($stateResult[0]['province_code']) || $stateResult[0]['province_code']== ''){
  $stateResult[0]['province_code'] = 0;
}
//district details
$districtQuery="SELECT DISTINCT facility_district from facility_details where facility_state='".$stateName."'";
$districtResult=$db->query($districtQuery);
//check remote user
$pdQuery="SELECT * from province_details";
if(USERTYPE=='remoteuser'){
  $sampleCode = 'remote_sample_code';
  //check user exist in user_facility_map table
    $chkUserFcMapQry = "Select user_id from vl_user_facility_map where user_id='".$_SESSION['userId']."'";
    $chkUserFcMapResult = $db->query($chkUserFcMapQry);
    if($chkUserFcMapResult){
    $pdQuery="SELECT * from province_details as pd JOIN facility_details as fd ON fd.facility_state=pd.province_name JOIN vl_user_facility_map as vlfm ON vlfm.facility_id=fd.facility_id where user_id='".$_SESSION['userId']."'";
    }
}else{
  $sampleCode = 'sample_code';
}
$pdResult=$db->query($pdQuery);
$province = '';
$province.="<option value=''> -- Select -- </option>";
foreach($pdResult as $provinceName){
  $province .= "<option value='".$provinceName['province_name']."##".$provinceName['province_code']."'>".ucwords($provinceName['province_name'])."</option>";
}
$facility = '';
$facility.="<option value=''> -- Select -- </option>";
foreach($fResult as $fDetails){
  $facility .= "<option value='".$fDetails['facility_id']."'>".ucwords($fDetails['facility_name'])."</option>";
}
?>
<style> 
      .serialNo{background-color: #fff;}
</style>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1><i class="fa fa-edit"></i> VIRAL LOAD LABORATORY REQUEST FORM</h1>
      <ol class="breadcrumb">
        <li><a href="/"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Edit Vl Request</li>
      </ol>
    </section>
    <!-- Main content -->
    <section class="content">
      <!-- SELECT2 EXAMPLE -->
      <div class="box box-default">
        <div class="box-header with-border">
          <div class="pull-right" style="font-size:15px;"><span class="mandatory">*</span> indicates required field &nbsp;</div>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
          <!-- form start -->
            <form class="form-inline" method='post'  name='vlRequestForm' id='vlRequestForm' autocomplete="off" action="editVlRequestHelperZm.php">
              <div class="box-body">
                <div class="box box-default">
                  <div class="box-body">
                    <div class="row">
                      <div class="col-xs-3 col-md-3">
                        <div class="form-group">
                          <label for="serialNo">Form Serial No <span class="mandatory">*</span></label>
                          <input type="text" class="form-control serialNo <?php echo $numeric;?> isRequired removeValue" id="" name="serialNo" placeholder="Enter Form Serial No." title="Please enter serial No" style="width:100%;" value="<?php echo ($sCode!='') ? $sCode : $vlQueryInfo[0][$sampleCode]; ?>"  onchange="checkSampleNameValidation('vl_request_form','<?php echo $sampleCode;?>','serialNo','<?php echo "vl_sample_id##".$vlQueryInfo[0]["vl_sample_id"];?>','This sample number already exists.Try another number',null)"/>
                          <input type="hidden" name="sampleCodeCol" value="<?php echo $vlQueryInfo[0]['sample_code'];?>"/>
                        </div>
                      </div>
                      <div class="col-xs-3 col-md-3 col-sm-offset-2 col-md-offset-2" style="padding:10px;">
                        <div class="form-group">
                        <label for="urgency">Urgency&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <label class="radio-inline">
                             <input type="radio" class="" id="urgencyNormal" name="urgency" value="normal" title="Please check urgency" <?php echo ($vlQueryInfo[0]['test_urgency']=='normal')?"checked='checked'":""?>> Normal
                        </label>
                        <label class="radio-inline">
                             <input type="radio" class=" " id="urgencyUrgent" name="urgency" value="urgent" title="Please check urgency" <?php echo ($vlQueryInfo[0]['test_urgency']=='urgent')?"checked='checked'":""?>> Urgent
                        </label>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-xs-3 col-md-3">
                        <div class="form-group">
                        <label for="province">Province<span class="mandatory">*</span></label>
                          <select class="form-control isRequired" name="province" id="province" title="Please choose province" style="width:100%;" onchange="getfacilityDetails(this);">
                            <option value=""> -- Select -- </option>
                            <?php foreach($pdResult as $provinceName){
                              ?>
                            <option value="<?php echo $provinceName['province_name']."##".$provinceName['province_code'];?>" <?php echo ($facilityResult[0]['facility_state']."##".$stateResult[0]['province_code']==$provinceName['province_name']."##".$provinceName['province_code'])?"selected='selected'":""?>><?php echo ucwords($provinceName['province_name']);?></option>;
                            <?php } ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-xs-3 col-md-3">
                        <div class="form-group">
                        <label for="District">District <span class="mandatory">*</span></label>
                          <select class="form-control isRequired" name="district" id="district" title="Please choose district" style="width:100%;" onchange="getfacilityDistrictwise(this)">
                            <option value=""> -- Select -- </option>
                            <?php foreach($districtResult as $districtName){ ?>
                              <option value="<?php echo $districtName['facility_district'];?>" <?php echo ($facilityResult[0]['facility_district']==$districtName['facility_district'])?"selected='selected'":""?>><?php echo ucwords($districtName['facility_district']);?></option>
                              <?php } ?>
                          </select>
                        </div>
                      </div>
                    </div>
                <div class="row">
                  <div class="col-xs-3 col-md-3">
                    <div class="form-group">
                    <label for="clinicName">Clinic Name <span class="mandatory">*</span> </label>
                      <select class="form-control isRequired" id="clinicName" name="clinicName" title="Please select clinic name" style="width:100%;" onchange="getfacilityProvinceDetails(this)">
                        <option value=''> -- Select -- </option>
                        <?php foreach($fResult as $fDetails){ ?>
                        <option value="<?php echo $fDetails['facility_id'];?>" <?php echo ($vlQueryInfo[0]['facility_id']==$fDetails['facility_id'])?"selected='selected'":""?>><?php echo ucwords($fDetails['facility_name']);?></option>
                        <?php } ?>
		      </select>
                    </div>
                  </div>
                  <div class="col-xs-3 col-md-3">
                    <div class="form-group">
                    <label for="clinicianName">Clinician Name </label>
                    <input type="text" class="form-control  " name="clinicianName" id="clinicianName" placeholder="Enter Clinician Name" style="width:100%;"  value="<?php echo $vlQueryInfo[0]['request_clinician_name'];?>">
                    </div>
                  </div>
                  <div class="col-xs-3 col-md-3">
                    <div class="form-group">
                    <label for="sampleCollectionDate">Sample Collection Date<span class="mandatory">*</span></label>
                    <input type="text" class="form-control isRequired dateTime" style="width:100%;" name="sampleCollectionDate" id="sampleCollectionDate" title="Please choose sample collection date" placeholder="Sample Collection Date" value="<?php echo $vlQueryInfo[0]['sample_collection_date'];?>" onchange="checkSampleReceviedDate();checkSampleTestingDate();">
                    </div>
                  </div>
                  <div class="col-xs-3 col-md-3">
                    <div class="form-group">
                    <label for="">Sample Received Date</label>
                    <input type="text" class="form-control dateTime" style="width:100%;" name="sampleReceivedDate" id="sampleReceivedDate" placeholder="Sample Received Date" value="<?php echo $vlQueryInfo[0]['sample_received_at_vl_lab_datetime']; ?>" onchange="checkSampleReceviedDate();">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-3 col-md-3 col-lg-3">
                    <div class="form-group">
                    <label for="collectedBy">Collected by (Initials)</label>
                    <input type="text" class="form-control" name="collectedBy" id="collectedBy" style="width:100%;" title="Enter Collected by (Initials)" placeholder="Enter Collected by (Initials)" value="<?php echo $vlQueryInfo[0]['sample_collected_by'];?>">
                    </div>
                  </div>
                </div>
                <br/>
                    <table class="table" style="width:100%">
                      <tr>
                        <td style="width:16%">
                        <label for="patientFname">Patient First Name <span class="mandatory">*</span></label>
                        </td>
                        <td style="width:20%">
                          <input type="text" class="form-control isRequired " name="patientFname" id="patientFname" placeholder="First Name" title="Enter First Name"  style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_first_name'];?>" >
                        </td>
                        <td style="width:10%">
                        <label for="surName">Last Name <span class="mandatory">*</span></label>
                        </td>
                        <td style="width:18%">
                          <input type="text" class="form-control isRequired" name="surName" id="surName" placeholder="Last Name" title="Enter Last Name"  style="width:100%;"  value="<?php echo $vlQueryInfo[0]['patient_last_name'];?>" >
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2">
                          <label for="gender">Gender &nbsp;&nbsp;</label>
                           <label class="radio-inline">
                            <input type="radio" class="" id="genderMale" name="gender" value="male" title="Please check gender"  <?php echo ($vlQueryInfo[0]['patient_gender']=='male')?"checked='checked'":""?>> Male
                            </label>
                          <label class="radio-inline">
                            <input type="radio" class=" " id="genderFemale" name="gender" value="female" title="Please check gender" <?php echo ($vlQueryInfo[0]['patient_gender']=='female')?"checked='checked'":""?>> Female
                          </label>
                          <label class="radio-inline">
                            <input type="radio" class=" " id="genderNotRecorded" name="gender" value="not_recorded" title="Please check gender" <?php echo ($vlQueryInfo[0]['patient_gender']=='not_recorded')?"checked='checked'":""?>> Not Recorded
                          </label>
                        </td>
                        <td><label>Date Of Birth</label></td>
                        <td>
                          <input type="text" class="form-control date" placeholder="DOB" name="dob" id="dob" title="Please choose DOB" style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_dob'];?>" onchange="getAge();checkARTInitiationDate();">
                        </td>
                        <td><label for="ageInYears">Age in years</label></td>
                        <td>
                          <input type="text" class="form-control" name="ageInYears" id="ageInYears" placeholder="If DOB Unkown" title="Enter age in years" style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_age_in_years'];?>">
                        </td>
                      </tr>
                      <tr>
                        <td><label for="ageInMonths">Age in months</label></td>
                        <td>
                          <input type="text" class="form-control" name="ageInMonths" id="ageInMonths" placeholder="If age < 1 year" title="Enter age in months" style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_age_in_months'];?>" />
                        </td>
                        <td class="femaleElements" <?php echo($vlQueryInfo[0]['patient_gender'] == 'male')?'style="display:none;"':''; ?>><label for="patientPregnant">Is Patient Pregnant ?</label></td>
                        <td class="femaleElements" <?php echo($vlQueryInfo[0]['patient_gender'] == 'male')?'style="display:none;"':''; ?>>
                          <label class="radio-inline">
                           <input type="radio" class="" id="pregYes" name="patientPregnant" value="yes" title="Please check Is Patient Pregnant" <?php echo ($vlQueryInfo[0]['is_patient_pregnant']=='yes')?"checked='checked'":""?>  onclick="checkPatientIsPregnant(this.value);"> Yes
                          </label>
                          <label class="radio-inline">
                           <input type="radio" class="" id="pregNo" name="patientPregnant" value="no" title="Please check Is Patient Pregnant" <?php echo ($vlQueryInfo[0]['is_patient_pregnant']=='no')?"checked='checked'":""?>  onclick="checkPatientIsPregnant(this.value);"> No
                          </label>
                        </td>
                        <td colspan="2"  class="femaleElements" <?php echo($vlQueryInfo[0]['patient_gender'] == 'male')?'style="display:none;"':''; ?>><label for="breastfeeding">Is Patient Breastfeeding?</label>
                          <label class="radio-inline">
                             <input type="radio" class="" id="breastfeedingYes" name="breastfeeding" value="yes" title="Is Patient Breastfeeding" <?php echo ($vlQueryInfo[0]['is_patient_breastfeeding']=='yes')?"checked='checked'":""?> > Yes
                       </label>
                       <label class="radio-inline">
                               <input type="radio" class="" id="breastfeedingNo" name="breastfeeding" value="no" title="Is Patient Breastfeeding" <?php echo ($vlQueryInfo[0]['is_patient_breastfeeding']=='no')?"checked='checked'":""?>> No
                       </label>
                        </td>
                      </tr>
                      <tr>
                        <td><label for="patientArtNo">Patient OI/ART Number</label></td>
                        <td>
                          <input type="text" class="form-control" name="patientArtNo" id="patientArtNo" placeholder="Patient OI/ART Number" title="Enter Patient OI/ART Number" style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_art_no'];?>" >
                        </td>
                        <td><label for="dateOfArt">Date Of ART Initiation</label></td>
                        <td>
                          <input type="text" class="form-control date" name="dateOfArtInitiation" id="dateOfArtInitiation" placeholder="Date Of ART Initiation" title="Date Of ART Initiation" style="width:100%;" value="<?php echo $vlQueryInfo[0]['date_of_initiation_of_current_regimen'];?>" onchange="checkARTInitiationDate();checkLastVLTestDate();">
                        </td>
                        <td><label for="artRegimen">ART Regimen</label></td>
                        <td>
                            <select class="form-control" id="artRegimen" name="artRegimen" placeholder="Enter ART Regimen" title="Please choose ART Regimen" onchange="checkARTRegimenValue();" style="width: 100%">
                         <option value=""> -- Select -- </option>
                         <?php foreach($aResult as $parentRow){ ?>
                          <option value="<?php echo $parentRow['art_code']; ?>"<?php echo ($vlQueryInfo[0]['current_regimen']==$parentRow['art_code'])?"selected='selected'":""?>><?php echo $parentRow['art_code']; ?></option>
                         <?php } if(USERTYPE!='vluser'){  ?>
                          <option value="other">Other</option>
                          <?php } ?>
                        </select>
                        </td>
                      </tr>
                      <tr>
                        <td class="newArtRegimen" style="display: none;"><label for="newArtRegimen">New ART Regimen</label><span class="mandatory">*</span></td>
                        <td class="newArtRegimen" style="display: none;">
                          <input type="text" class="form-control newArtRegimen" name="newArtRegimen" id="newArtRegimen" placeholder="New Art Regimen" title="New Art Regimen" style="width:100%;" >
                        </td>
                        <td><label>Patient consent to SMS Notification</label></td>
                        <td>
                          <label class="radio-inline">
                             <input type="radio" class="" id="receivesmsYes" name="receiveSms" value="yes" title="Patient consent to receive SMS" <?php echo ($vlQueryInfo[0]['consent_to_receive_sms']=='yes')?"checked='checked'":""?> onclick="checkPatientReceivesms(this.value);"> Yes
                          </label>
                          <label class="radio-inline">
                                  <input type="radio" class="" id="receivesmsNo" name="receiveSms" value="no" title="Patient consent to receive SMS" <?php echo ($vlQueryInfo[0]['consent_to_receive_sms']=='no')?"checked='checked'":""?> onclick="checkPatientReceivesms(this.value);"> No
                          </label>
                        </td>
                        <td><label for="patientPhoneNumber" class="">Mobile Number</label></td>
                        <td><input type="text" class="form-control" id="patientPhoneNumber" name="patientPhoneNumber" placeholder="Enter Mobile Number." title="Please enter patient Phone No" style="width:100%;" value="<?php echo $vlQueryInfo[0]['patient_mobile_number'];?>" /></td>
                      </tr>
                      <tr class="newArtRegimen" style="display: none;"> </tr>
                      <tr>
                        <td><label for="lastViralLoadTestDate">Date Of Last Viral Load Test</label></td>
                        <td><input type="text" class="form-control date" id="lastViralLoadTestDate" name="lastViralLoadTestDate" placeholder="Enter Date Of Last Viral Load Test" title="Enter Date Of Last Viral Load Test" style="width:100%;" value="<?php echo $vlQueryInfo[0]['last_viral_load_date'];?>" onchange="checkLastVLTestDate();"/></td>
                        <td><label for="lastViralLoadResult">Result Of Last Viral Load</label></td>
                        <td><input type="text" class="form-control" id="lastViralLoadResult" name="lastViralLoadResult" placeholder="Enter Result Of Last Viral Load" title="Enter Result Of Last Viral Load" style="width:100%;" value="<?php echo $vlQueryInfo[0]['last_viral_load_result'];?>" /></td>
                        <td><label for="viralLoadLog">Viral Load Log</label></td>
                        <td><input type="text" class="form-control" id="viralLoadLog" name="viralLoadLog" placeholder="Enter Viral Load Log" title="Enter Viral Load Log" style="width:100%;"  value="<?php echo $vlQueryInfo[0]['last_vl_result_in_log'];?>"/></td>
                      </tr>
                      <tr>
                        <td><label for="vlTestReason">Reason For VL test</label></td>
                        <td>
                          <select name="vlTestReason" id="vlTestReason" class="form-control" title="Please choose Reason For VL test" style="width:200px;">
                            <option value=""> -- Select -- </option>
                            <?php foreach($vlTestReasonResult as $reason){ ?>
                              <option value="<?php echo $reason['test_reason_name'];?>"  <?php echo ($vlQueryInfo[0]['reason_for_vl_testing']==$reason['test_reason_name'])?"selected='selected'":""?>><?php echo ucwords($reason['test_reason_name']);?></option>
                              <?php } ?>
                           </select>
                        </td>
                        <td></td>
                        <td>
                        </td>
                      </tr>
                    </table>
                  </div>
                </div>
                <div class="box box-primary" style="<?php if(USERTYPE=='remoteuser'){ ?> pointer-events:none;<?php } ?>">
                  <div class="box-body">
                    <div class="box-header with-border">
                    <h3 class="box-title">FOR LABORATORY USE ONLY</h3>
                    <div class="pull-right"><a href="javascript:void(0);" onclick="showModal('facilitiesModal.php?type=lab',900,520);" class="btn btn-default btn-sm" style="margin-right: 2px;" title="Search"><i class="fa fa-search"></i> Search</a></div>
                    </div>
                    <table class="table">
                      <tr>
                        <td><label for="serialNo">Form Serial No. <span class="mandatory">*</span></label></td>
                        <td><input type="text" class="form-control serialNo1 <?php echo $numeric;?> isRequired removeValue" id="" name="serialNo" placeholder="Enter Form Serial No." title="Please enter serial No" style="width:100%;" value="<?php echo ($sCode!='') ? $sCode : $vlQueryInfo[0][$sampleCode]; ?>"  onchange="checkSampleNameValidation('vl_request_form','<?php echo $sampleCode;?>','serialNo1','<?php echo "vl_sample_id##".$vlQueryInfo[0]["vl_sample_id"];?>','This sample number already exists.Try another number',null)" /></td>
                        <td><label for="sampleCode">Request Barcode <span class="mandatory">*</span></label></td>
                        <td>
                          <input type="text" class="form-control reqBarcode <?php echo $numeric;?> isRequired removeValue" name="reqBarcode" id="reqBarcode" placeholder="Request Barcode" title="Enter Request Barcode"  style="width:100%;" value="<?php echo ($sCode!='') ? $sCode : $vlQueryInfo[0][$sampleCode]; ?>"  onchange="checkSampleNameValidation('vl_request_form','<?php echo $sampleCode;?>','reqBarcode','<?php echo "vl_sample_id##".$vlQueryInfo[0]["vl_sample_id"];?>','This sample number already exists.Try another number',null)"/>
                          <!--<input type="hidden" class="form-control sampleCode" name="sampleCode" id="sampleCode" placeholder="Request Barcode" title="Enter Request Barcode"  style="width:100%;" value="< ?php echo $vlQueryInfo[0]['sample_code'];?>">-->
                        </td>
                        <td><label for="labId">Lab Name</label></td>
                        <td>
                          <select name="labId" id="labId" class="form-control" title="Please choose lab name" style="width: 100%">
                            <option value=""> -- Select -- </option>
                            <?php foreach($lResult as $labName){ ?>
                              <option value="<?php echo $labName['facility_id'];?>"<?php echo ($vlQueryInfo[0]['lab_id']==$labName['facility_id'])?"selected='selected'":""?>><?php echo ucwords($labName['facility_name']);?></option>
                              <?php } ?>
                          </select>
                        </td>
                      </tr>
                      <tr>
                        <td><label for="labNo">LAB No</label></td>
                        <td><input type="text" class="form-control checkNum" id="labNo" name="labNo" placeholder="Enter LAB No." title="Please enter patient Phone No" style="width:100%;" value="<?php echo $vlQueryInfo[0]['lab_code'];?>" /></td>
                        <td><label for="testingPlatform">VL Testing Platform</label></td>
                        <td>
                          <select name="testingPlatform" id="testingPlatform" class="form-control" title="Please choose VL Testing Platform" style="width:100%">
                            <option value="">-- Select --</option>
                            <?php foreach($importResult as $mName) { ?>
                              <option value="<?php echo $mName['machine_name'].'##'.$mName['lower_limit'].'##'.$mName['higher_limit'];?>"<?php echo ($vlQueryInfo[0]['vl_test_platform'].'##'.$mName['lower_limit'].'##'.$mName['higher_limit']==$mName['machine_name'].'##'.$mName['lower_limit'].'##'.$mName['higher_limit'])?"selected='selected'":""?>><?php echo $mName['machine_name'];?></option>
                              <?php } ?>
                          </select>
                        </td>
                        <td><?php if(isset($arr['sample_type']) && trim($arr['sample_type']) == "enabled"){ ?><label for="specimenType">Specimen type</label><?php } ?></td>
                        <td>
                          <?php if(isset($arr['sample_type']) && trim($arr['sample_type']) == "enabled"){ ?>
                            <select name="specimenType" id="specimenType" class="form-control" title="Please choose Specimen type" style="width:100%">
                                <option value=""> -- Select -- </option>
                                <?php foreach($sResult as $name){ ?>
                                 <option value="<?php echo $name['sample_id'];?>" <?php echo ($vlQueryInfo[0]['sample_type']==$name['sample_id'])?"selected='selected'":""?>><?php echo ucwords($name['sample_name']);?></option>
                                 <?php } ?>
                            </select>
                          <?php } ?>
                        </td>
                      </tr>
                      <tr>
                        <td><label for="sampleTestingDateAtLab">Sample Testing Date</label></td>
                        <td><input type="text" class="form-control dateTime" id="sampleTestingDateAtLab" name="sampleTestingDateAtLab" placeholder="Enter Sample Testing Date." title="Please enter Sample Testing Date" style="width:100%;" value="<?php echo $vlQueryInfo[0]['sample_tested_datetime'];?>" onchange="checkSampleTestingDate();"/></td>
                        <td><label for="vlResult">Viral Load Result<br/> (copiesl/ml)</label></td>
                        <td>
                          <input type="text" class="form-control" id="vlResult" name="vlResult" placeholder="Enter Viral Load Result" title="Please enter viral load result" style="width:100%;" value="<?php echo $vlQueryInfo[0]['result_value_absolute'];?>" onchange="calculateLogValue(this)"/>
                          <input type="hidden" name="textValue" value="<?php echo $vlQueryInfo[0]['result_value_text'];?>" />
                        </td>
                        <td><label for="vlLog">Viral Load Log</label></td>
                        <td><input type="text" class="form-control" id="vlLog" name="vlLog" placeholder="Enter Viral Load Log" title="Please enter viral load log" style="width:100%;" value="<?php echo $vlQueryInfo[0]['result_value_log'];?>" onchange="calculateLogValue(this)"/></td>
                      </tr>
                      <tr class="noResult">
                        <td><label class="noResult">If no result</label></td>
                        <td colspan="2">
                          <label class="radio-inline noResult">
                             <input type="radio" class="" id="noResultRejected" name="noResult" value="sample_rejected" title="Choose result" <?php echo ($vlQueryInfo[0]['is_sample_rejected']=='sample_rejected')?"checked='checked'":""?> onclick='checkRejectionReason()'> Sample Rejected
                          </label>
                          <label class="radio-inline noResult" style="margin-left: 0px;">
                              <input type="radio" class="" id="noResultError" name="noResult" value="technical_error" title="Choose result"<?php echo ($vlQueryInfo[0]['is_sample_rejected']=='technical_error')?"checked='checked'":""?> onclick='checkRejectionReason()'> Lab testing Technical Error
                          </label>
                        </td>
                        <td><label class="noResult">Rejection Reason</label></td>
                        <td colspan="2"><select name="rejectionReason" id="rejectionReason" class="form-control" title="Please choose reason" style="width: 200px;">
                        <option value="">-- Select --</option>
                          <?php foreach($rejectionResult as $reject){ ?>
                            <option value="<?php echo $reject['rejection_reason_id'];?>"<?php echo ($vlQueryInfo[0]['reason_for_sample_rejection']==$reject['rejection_reason_id'])?"selected='selected'":""?>><?php echo ucwords($reject['rejection_reason_name']);?></option>
                            <?php } ?>
                        </select></td>
                      </tr>
                      <tr class="">
                        <td><label>Reviewed By</label></td>
                        <!--<td><input type="text" class="form-control" id="reviewedBy" name="reviewedBy" placeholder="Enter Reviewed By" title="Please enter reviewed by" style="width:100%;" value="< ?php echo $vlQueryInfo[0]['result_reviewed_by'];?>" /></td>-->
                        <td>
                          <select name="reviewedBy" id="reviewedBy" class="form-control" title="Please choose reviewed by" style="width: 100%">
                            <option value="">-- Select --</option>
                            <?php foreach($userResult as $uName){ ?>
                              <option value="<?php echo $uName['user_id'];?>" <?php echo ($uName['user_id']==$vlQueryInfo[0]['result_reviewed_by'])?"selected=selected":""; ?>><?php echo ucwords($uName['user_name']);?></option>
                              <?php } ?>
                          </select>
                         </td>
                        <?php if($autoApprovalFieldStatus == 'show'){ ?>
                          <td><label>Approved By</label></td>
                          <!--<td><input type="text" class="form-control" id="approvedBy" name="approvedBy" placeholder="Enter Approved By" title="Please enter approved by" style="width:100%;"  value="< ?php echo $vlQueryInfo[0]['result_approved_by'];?>" /></td>-->
                          <td>
                          <select name="approvedBy" id="approvedBy" class="form-control" title="Please choose approved by" style="width: 100%">
                            <option value="">-- Select --</option>
                            <?php foreach($userResult as $uName){ ?>
                              <option value="<?php echo $uName['user_id'];?>" <?php echo ($uName['user_id']==$vlQueryInfo[0]['result_approved_by'])?"selected=selected":""; ?>><?php echo ucwords($uName['user_name']);?></option>
                              <?php } ?>
                          </select>
                         </td>
                        <?php } else { ?>
                          <td colspan="2"></td>
                        <?php } ?>
                      </tr>
                      <tr>
                        <td><label for="labComments">Laboratory <br/>Scientist Comments</label></td>
                        <td colspan="5"><textarea class="form-control" name="labComments" id="labComments" title="Enter lab comments" style="width:100%"> <?php echo $vlQueryInfo[0]['approver_comments'];?></textarea></td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>
              <!-- /.box-body -->
              <div class="box-footer">
                <a class="btn btn-primary" href="javascript:void(0);" onclick="validateNow();return false;">Save</a>
                <input type="hidden" name="treamentId" id="treamentId" value="<?php echo $vlQueryInfo[0]['vl_sample_id'];?>"/>
                <input type="hidden" name="oldStatus" value="<?php echo $vlQueryInfo[0]['result_status']; ?>"/>
                <a href="vlRequest.php" class="btn btn-default"> Cancel</a>
              </div>
              <!-- /.box-footer -->
            </form>
          <!-- /.row -->
        </div>
      </div>
      <!-- /.box -->
    </section>
    <!-- /.content -->
  </div>
  <script type="text/javascript">
provinceName = true;
facilityName = true;
machineName = true;
  function validateNow(){
    flag = deforayValidator.init({
        formId: 'vlRequestForm'
    });
    $('.isRequired').each(function () {
            ($(this).val() == '') ? $(this).css('background-color', '#FFFF99') : $(this).css('background-color', '#FFFFFF') 
    });
    if(flag){
      getMachineName();
      if(machineName){
        //check approve and review by name
        rBy = $("#reviewedBy").val();
        aBy = $("#approvedBy").val();
        globalValue = '<?php echo $arr["user_review_approve"];?>';
        if(aBy==rBy && (rBy!='' && aBy!='') && globalValue=='yes'){
          conf = confirm("Same person is reviewing and approving result!");
          if(conf){}else{
            return false;
          }
        }else if(aBy==rBy && (rBy!='' && aBy!='') && globalValue=='no'){
          alert("Same person is reviewing and approving result!");
          return false;
        }
      $.blockUI();
      document.getElementById('vlRequestForm').submit();
      }
    }
  }
  function getfacilityDetails(obj)
  {
      var cName = $("#clinicName").val();
      var pName = $("#province").val();
      if(pName!='' && provinceName && facilityName){
        facilityName = false;
      }
    if(pName!=''){
      if(provinceName){
      $.post("../includes/getFacilityForClinic.php", { pName : pName},
      function(data){
	  if(data != ""){
            details = data.split("###");
            $("#clinicName").html(details[0]);
            $("#district").html(details[1]);
            $("#clinicianName").val(details[2]);
	  }
      });
      }
    }else if(pName=='' && cName==''){
      provinceName = true;
      facilityName = true;
      $("#province").html("<?php echo $province;?>");
      $("#clinicName").html("<?php echo $facility;?>");
    }
  }
  function getfacilityDistrictwise(obj)
  {
    var dName = $("#district").val();
    var cName = $("#clinicName").val();
    if(dName!=''){
      $.post("../includes/getFacilityForClinic.php", {dName:dName,cliName:cName},
      function(data){
	  if(data != ""){
            $("#clinicName").html(data);
	  }
      });
    }
  }
  function getfacilityProvinceDetails(obj)
  {
     //check facility name
      var cName = $("#clinicName").val();
      var pName = $("#province").val();
      if(cName!='' && provinceName && facilityName){
        provinceName = false;
      }
    if(cName!='' && facilityName){
      $.post("../includes/getFacilityForClinic.php", { cName : cName},
      function(data){
	  if(data != ""){
            details = data.split("###");
            $("#province").html(details[0]);
            $("#district").html(details[1]);
            $("#clinicianName").val(details[2]);
	  }
      });
    }else if(pName=='' && cName==''){
      provinceName = true;
      facilityName = true;
      $("#province").html("<?php echo $province;?>");
      $("#clinicName").html("<?php echo $facility;?>");
    }
  }
  $(document).ready(function() {
    if($("#vlResult").val() == "" && $("#vlLog").val() == "" ){
      $(".noResult").show();
    }else{
      $(".noResult").hide();
    }    
$("#vlResult").bind("keyup change", function(e) {
    if($("#vlResult").val() == "" && $("#vlLog").val() == "" ){
      $(".noResult").show();
    }else{
      $( "#noResultRejected" ).prop( "checked", false );
      $( "#noResultError" ).prop( "checked", false );
      $("#rejectionReason").removeClass("isRequired");
      $("#rejectionReason").val("");
      $(".noResult").hide();
    }
});
$("#vlLog").bind("keyup change", function(e) {
    if($("#vlResult").val() == "" && $("#vlLog").val() == "" ){
      $(".noResult").show();
    }else{
      $( "#noResultRejected" ).prop( "checked", false );
      $( "#noResultError" ).prop( "checked", false );
      $("#rejectionReason").removeClass("isRequired");
      $("#rejectionReason").val("");
      $(".noResult").hide();
    }
});
     <?php if(isset($vlQueryInfo[0]['is_patient_pregnant']) && trim($vlQueryInfo[0]['is_patient_pregnant'])!= ''){ ?>
       checkPatientIsPregnant('<?php echo $vlQueryInfo[0]['is_patient_pregnant'];?>');
     <?php } ?>
  });
  function checkRejectionReason()
  {
  $("#rejectionReason").addClass("isRequired");
  }
  $("input:radio[name=gender]").click(function() {
      if($(this).val() == 'male'){
         $(".femaleElements").hide();
      }else if($(this).val() == 'female'){
        $(".femaleElements").show();
      }else if($(this).val() == 'not_recorded'){
        $(".femaleElements").show();
      }
    });
  if($("input:radio[name=receiveSms]:checked") && $("input:radio[name=receiveSms]:checked").val() =='yes'){
    $("#patientPhoneNumber").removeAttr("disabled");
  }else{
    $("#patientPhoneNumber").attr("disabled","disabled");
  }
  $("input:radio[name=receiveSms]").click(function() {
      if($(this).val() == 'no'){
         $("#patientPhoneNumber").attr("disabled","disabled");
      }else if($(this).val() == 'yes'){
        $("#patientPhoneNumber").removeAttr("disabled");
      }
  });
  function checkPatientReceivesms(val)
  {
   if(val=='yes'){
    $('#patientPhoneNumber').addClass('isRequired');
   }else{
     $('#patientPhoneNumber').removeClass('isRequired');
   }
  }
  $(".serialNo").keyup(function(){
    $(".serialNo1").val($(".serialNo").val());
    $(".reqBarcode").val($(".serialNo").val());
  });
  $(".serialNo1").keyup(function(){
    $(".serialNo").val($(".serialNo1").val());
    $(".reqBarcode").val($(".serialNo1").val());
  });
  $(".reqBarcode").keyup(function(){
    $(".serialNo").val($(".reqBarcode").val());
    $(".serialNo1").val($(".reqBarcode").val());
  });
    function setFacilityLabDetails(fDetails){
      $("#labId").val("");
      facilityArray = fDetails.split("##");
      $("#labId").val(facilityArray[0]);
    }
    function checkPatientIsPregnant(value){
      if(value=='yes'){
        $("select option[value*='pregnant_mother']").prop('disabled',false);
      }else{
        if($("#vlTestReason").val()=='pregnant_mother'){
          $("#vlTestReason").val('');
        }
        $("select option[value*='pregnant_mother']").prop('disabled',true);
      }
    }
    function checkLastVLTestDate(){
      var artInitiationDate = $("#dateOfArtInitiation").val();
      var dateOfLastVLTest = $("#lastViralLoadTestDate").val();
      if($.trim(artInitiationDate)!= '' && $.trim(dateOfLastVLTest)!= '') {
        //Check diff
        if(moment(artInitiationDate).isAfter(dateOfLastVLTest)) {
          alert("Last Viral Load Test Date could not be earlier than ART initiation date!");
          $("#lastViralLoadTestDate").val("");
        }
      }
    }
    //check machine name and limit
    function getMachineName()
    {
      machineName = true;
      var mName = $("#testingPlatform").val();
      var absValue = $("#vlResult").val();
      if(mName!='' && absValue!='')
      {
        //split the value
        var result = mName.split("##");
        if(result[0]=='Roche' && absValue!='<20' && absValue!='>10000000'){
          var lowLimit = result[1];
          var highLimit = result[2];
            if(lowLimit!='' && lowLimit!=0 && parseInt(absValue) < 20){
              alert("Value outside machine detection limit");
              $("#vlResult").css('background-color', '#FFFF99');
              machineName = false;
            }else if(highLimit!='' && highLimit!=0 && parseInt(absValue) > 10000000){
              alert("Value outside machine detection limit");
              $("#vlResult").css('background-color', '#FFFF99');
              machineName  = false;
            }else{
              lessSign = absValue.split("<");
              greaterSign = absValue.split(">");
              if(lessSign.length>1)
              {
                if(parseInt(lessSign[1])<parseInt(lowLimit)){
                alert("Invalid value.Value Lesser than machine detection limit.");  
                }else if(parseInt(lessSign[1])>parseInt(highLimit))
                {
                  alert("Invalid value.Value Greater than machine detection limit.");  
                }else{
                  alert("Invalid value.");  
                }
                $("#vlResult").css('background-color', '#FFFF99');
                machineName = false;
              }else if(greaterSign.length>1)
              {
                if(parseInt(greaterSign[1])<parseInt(lowLimit)){
                alert("Invalid value.Value Lesser than machine detection limit.");  
                }else if(parseInt(greaterSign[1])>parseInt(highLimit))
                {
                  alert("Invalid value.Value Greater than machine detection limit.");  
                }else{
                  alert("Invalid value.");  
                }
                $("#vlResult").css('background-color', '#FFFF99');
                machineName = false;
              }
            }
        }
      }
    }
    function calculateLogValue(obj)
    {
      if(obj.id=="vlResult") {
        absValue = $("#vlResult").val();
        if(absValue!='' && absValue!=0){
          $("#vlLog").val(Math.round(Math.log10(absValue) * 100) / 100);
        }
      }
      if(obj.id=="vlLog") {
        logValue = $("#vlLog").val();
        if(logValue!='' && logValue!=0){
          var absVal = Math.round(Math.pow(10,logValue) * 100) / 100;
          if(absVal!='Infinity'){
          $("#vlResult").val(Math.round(Math.pow(10,logValue) * 100) / 100);
          }else{
            $("#vlResult").val('');
          }
        }
      }
    }
</script>