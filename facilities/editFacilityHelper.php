<?php
ob_start();
session_start();
include('../includes/MysqliDb.php');
include('../General.php');
$general = new Deforay_Commons_General();
$tableName="facility_details";
$facilityId=base64_decode($_POST['facilityId']);
$tableName1="province_details";
$tableName2="vl_user_facility_map";
try {
    if(isset($_POST['facilityName']) && trim($_POST['facilityName'])!=""){
        if(trim($_POST['state'])!=""){
            $strSearch = (isset($_POST['provinceNew']) && trim($_POST['provinceNew'])!= '' && $_POST['state'] == 'other')?$_POST['provinceNew']:$_POST['state'];
            $facilityQuery="SELECT province_name from province_details where province_name='".$strSearch."'";
            $facilityInfo=$db->query($facilityQuery);
	    if(isset($facilityInfo[0]['province_name'])){
		$_POST['state'] = $facilityInfo[0]['province_name'];
	    }else{
		$data=array(
		  'province_name'=>$_POST['provinceNew'],
		  'updated_datetime'=>$general->getDateTime(),
		);
		$db->insert($tableName1,$data);
		$_POST['state'] = $_POST['provinceNew'];
	    }
        }
	$email = '';
	if(isset($_POST['reportEmail']) && trim($_POST['reportEmail'])!=''){
	    $expEmail = explode(",",$_POST['reportEmail']);
	    for($i=0;$i<count($expEmail);$i++){
		$reportEmail = filter_var($expEmail[$i], FILTER_VALIDATE_EMAIL);
		if($reportEmail!=''){
		    if($email!=''){
		      $email.= ",".$reportEmail;
		    }else{
		      $email.= $reportEmail;    
		    }
		}
	    }
	}
        $data=array(
	    'facility_name'=>$_POST['facilityName'],
	    'facility_code'=>$_POST['facilityCode'],
	    'other_id'=>$_POST['otherId'],
	    'facility_mobile_numbers'=>$_POST['phoneNo'],
	    'address'=>$_POST['address'],
	    'country'=>$_POST['country'],
	    'facility_state'=>$_POST['state'],
	    'facility_district'=>$_POST['district'],
	    'facility_hub_name'=>$_POST['hubName'],
	    'latitude'=>$_POST['latitude'],
	    'longitude'=>$_POST['longitude'],
	    'facility_emails'=>$_POST['email'],
	    'report_email'=>$email,
	    'contact_person'=>$_POST['contactPerson'],
	    'facility_type'=>$_POST['facilityType'],
		'updated_datetime'=>$general->getDateTime(),
	    'status'=>$_POST['status']
        );
        $db=$db->where('facility_id',$facilityId);
        $id = $db->update($tableName,$data);
		$db=$db->where('facility_id',$facilityId);
		$delId = $db->delete($tableName2);
		if($facilityId>0 && trim($_POST['selectedUser'])!='')
		{
			$selectedUser = explode(",",$_POST['selectedUser']);
			for($j = 0; $j < count($selectedUser); $j++){
				$data=array(
					'user_id'=>$selectedUser[$j],
					'facility_id'=>$facilityId,
				);
				$db->insert($tableName2,$data);
			}
		}
        $_SESSION['alertMsg']="Facility details updated successfully";
    }
    header("location:facilities.php");
  
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}