<?php
session_start();
include('../includes/MysqliDb.php');
include('../General.php');
$general=new Deforay_Commons_General();
$tableName="vl_request_form";
$primaryKey="vl_sample_id";
$configQuery ="SELECT * from global_config where name='vl_form'";
$configResult=$db->query($configQuery);
$country = $configResult[0]['value'];
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
        */
        
        $aColumns = array('facility_state','facility_district','facility_name','facility_code',"DATE_FORMAT(vl.sample_collection_date,'%d-%b-%Y')",'vl.approver_comments');
        $orderColumns = array('facility_state','facility_district','facility_name','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','sample_collection_date','vl.approver_comments');
        
        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = $primaryKey;
        
        $sTable = $tableName;
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
            $sOffset = $_POST['iDisplayStart'];
            $sLimit = $_POST['iDisplayLength'];
        }
        
        /*
         * Ordering
        */
        
        $sOrder = "";
		
		
		
        if (isset($_POST['iSortCol_0'])) {
            $sOrder = "";
            for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
                if ($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {
                    $sOrder .= $orderColumns[intval($_POST['iSortCol_' . $i])] . "
				 	" . ( $_POST['sSortDir_' . $i] ) . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
        }
        
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
        */
        
       $sWhere = "";
        if (isset($_POST['sSearch']) && $_POST['sSearch'] != "") {
            $searchArray = explode(" ", $_POST['sSearch']);
            $sWhereSub = "";
            foreach ($searchArray as $search) {
                if ($sWhereSub == "") {
                    $sWhereSub .= "(";
                } else {
                    $sWhereSub .= " AND (";
                }
                $colSize = count($aColumns);
                
                for ($i = 0; $i < $colSize; $i++) {
                    if ($i < $colSize - 1) {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }
        
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
                } else {
                    $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
                }
            }
        }
        
        /*
         * SQL queries
         * Get data to display
        */
	$sQuery="SELECT vl.vl_sample_id,vl.facility_id,f.facility_code,f.facility_state,f.facility_district,f.facility_name FROM vl_request_form as vl INNER JOIN facility_details as f ON f.facility_id=vl.facility_id";
	$start_date = '';
	$end_date = '';
	if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!= ''){
	   $s_t_date = explode("to", $_POST['sampleCollectionDate']);
	   if (isset($s_t_date[0]) && trim($s_t_date[0]) != "") {
	     $start_date = $general->dateFormat(trim($s_t_date[0]));
	   }
	   if (isset($s_t_date[1]) && trim($s_t_date[1]) != "") {
	     $end_date = $general->dateFormat(trim($s_t_date[1]));
	   }
	}
	
	$tWhere = 'where ';
	if(isset($sWhere) && trim($sWhere)!= ''){
	  $sWhere=' where '.$sWhere;
	  $sWhere = $sWhere.' AND vl.vlsm_country_id = '.$country;
	  $tWhere = $tWhere.'vl.vlsm_country_id = '.$country;
	}else{
	  $sWhere=' where '.$sWhere;
	  $sWhere = $sWhere.'vl.vlsm_country_id = '.$country;
	  $tWhere = $tWhere.'vl.vlsm_country_id = '.$country;
	}
	
	if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!= ''){
	    if (trim($start_date) == trim($end_date)) {
	      $sWhere = $sWhere.' AND DATE(vl.sample_collection_date) = "'.$start_date.'"';
	      $tWhere = $tWhere.' AND DATE(vl.sample_collection_date) = "'.$start_date.'"';
	    }else{
	      $sWhere = $sWhere.' AND DATE(vl.sample_collection_date) >= "'.$start_date.'" AND DATE(vl.sample_collection_date) <= "'.$end_date.'"';
	      $tWhere = $tWhere.' AND DATE(vl.sample_collection_date) >= "'.$start_date.'" AND DATE(vl.sample_collection_date) <= "'.$end_date.'"';
	    }
        }if(isset($_POST['lab']) && trim($_POST['lab'])!= ''){
	    $sWhere = $sWhere." AND vl.lab_id IN (".$_POST['lab'].")";
	    $tWhere = $tWhere." AND vl.lab_id IN (".$_POST['lab'].")";
	}
	
	$sQuery = $sQuery.' '.$sWhere;
	$sQuery = $sQuery.' GROUP BY vl.facility_id';
	$_SESSION['vlStatisticsQuery']=$sQuery;
        if (isset($sOrder) && $sOrder != "") {
            $sOrder = preg_replace('/(\v|\s)+/', ' ', $sOrder);
            $sQuery = $sQuery.' order by '.$sOrder;
        }
        
        if (isset($sLimit) && isset($sOffset)) {
            $sQuery = $sQuery.' LIMIT '.$sOffset.','. $sLimit;
        }
	//echo $sQuery;die;
        $sResult = $db->rawQuery($sQuery);
        /* Data set length after filtering */
        
        $aResultFilterTotal =$db->rawQuery("SELECT vl.vl_sample_id FROM vl_request_form as vl INNER JOIN facility_details as f ON f.facility_id=vl.facility_id $sWhere GROUP BY vl.facility_id order by $sOrder");
        $iFilteredTotal = count($aResultFilterTotal);
        /* Total data set length */
        $aResultTotal =  $db->rawQuery("select vl.vl_sample_id FROM vl_request_form as vl INNER JOIN facility_details as f ON f.facility_id=vl.facility_id $tWhere GROUP BY vl.facility_id");
        $iTotal = count($aResultTotal);

        /*
         * Output
        */
        $output = array(
            "sEcho" => intval($_POST['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
	
        
        foreach ($sResult as $aRow) {
	    //No. of tests per facility & calculate others
	    $totalQuery = 'SELECT vl_sample_id,patient_dob,patient_gender,is_patient_pregnant,is_patient_breastfeeding,result,is_sample_rejected,reason_for_sample_rejection,result_status FROM vl_request_form as vl where vl.facility_id = '.$aRow['facility_id'].' AND vl.vlsm_country_id = '.$country;
	    if(isset($_POST['lab']) && trim($_POST['lab'])!= ''){
	       $totalQuery = $totalQuery." AND vl.lab_id IN (".$_POST['lab'].")";
	    }
	    if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!= ''){
		if (trim($start_date) == trim($end_date)) {
		  $totalQuery = $totalQuery.' AND DATE(vl.sample_collection_date) = "'.$start_date.'"';
		}else{
		  $totalQuery = $totalQuery.' AND DATE(vl.sample_collection_date) >= "'.$start_date.'" AND DATE(vl.sample_collection_date) <= "'.$end_date.'"';
		}
	    }
	    $totalResult = $db->rawQuery($totalQuery);
	    $lte14n1000 = array();
	    $lte14ngt1000 = array();
	    $gt14mnlte1000 = array();
	    $gt14mngt1000 = array();
	    $gt14fnlte1000 = array();
	    $gt14fngt1000 = array();
	    $isPatientPergnantrbfeedingnlte1000 = array();
	    $isPatientPergnantrbfeedingngt1000 = array();
	    $unknownxnlte1000 = array();
	    $unknownxngt1000 = array();
	    $lte1000 = array();
	    $gt1000 = array();
	    $rejection = array();
	    foreach($totalResult as $tRow){
		$age = '';
		if($tRow['patient_dob']!= NULL && $tRow['patient_dob']!= '' && $tRow['patient_dob']!= '0000-00-00'){
		    $age = floor((time() - strtotime($tRow['patient_dob'])) / 31556926);
		}
		
		if(trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
		    $lte1000[] = $tRow['vl_sample_id'];
		}else if(trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
		   $gt1000[] = $tRow['vl_sample_id'];
		}
		
		if(trim($age)!= '' && $age <= 14 && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
		    $lte14n1000[] = $tRow['vl_sample_id'];
		}else if(trim($age)!= '' && $age <= 14 && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
		    $lte14ngt1000[] = $tRow['vl_sample_id'];
		}
		
		if(trim($age)!= '' && $age > 14 && $tRow['patient_gender'] == 'male' && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
		    $gt14mnlte1000[] = $tRow['vl_sample_id'];
		}else if(trim($age)!= '' && $age > 14 && $tRow['patient_gender'] == 'male' && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
		    $gt14mngt1000[] = $tRow['vl_sample_id'];
		}else if(trim($age)!= '' && $age > 14 && $tRow['patient_gender'] == 'female' && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
		    $gt14fnlte1000[] = $tRow['vl_sample_id'];
		}else if(trim($age)!= '' && $age > 14 && $tRow['patient_gender'] == 'female' && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
		    $gt14fngt1000[] = $tRow['vl_sample_id'];
		}
		
		if($tRow['patient_gender'] == 'female' && ($tRow['is_patient_pregnant'] == 'yes' || $tRow['is_patient_breastfeeding'] == 'yes') && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
		    $isPatientPergnantrbfeedingnlte1000[] = $tRow['vl_sample_id'];
		}else if($tRow['patient_gender'] == 'female' && ($tRow['is_patient_pregnant'] == 'yes' || $tRow['is_patient_breastfeeding'] == 'yes') && trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
		    $isPatientPergnantrbfeedingngt1000[] = $tRow['vl_sample_id'];
		}
		
		if($tRow['patient_gender']!= 'male' && $tRow['patient_gender']!= 'female'){
		    if(trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] <= 1000){
			$unknownxnlte1000[] = $tRow['vl_sample_id'];
		    }else if(trim($tRow['result'])!= '' && $tRow['result']!= NULL && $tRow['result']!= 'Target Not Detected' && $tRow['result'] > 1000){
			$unknownxngt1000[] = $tRow['vl_sample_id'];
		    }
		}
		
		if($tRow['result_status'] == 4){
		    $rejection[] = $tRow['vl_sample_id'];
		}
	    }
	    $row = array();
            $row[] = ucwords($aRow['facility_state']);
            $row[] = ucwords($aRow['facility_district']);
            $row[] = ucwords($aRow['facility_name']);
            $row[] = $aRow['facility_code'];
            $row[] = count($rejection);
            $row[] = count($lte14n1000);
            $row[] = count($lte14ngt1000);
            $row[] = count($gt14mnlte1000);
            $row[] = count($gt14mngt1000);
            $row[] = count($gt14fnlte1000);
            $row[] = count($gt14fngt1000);
            $row[] = count($isPatientPergnantrbfeedingnlte1000);
            $row[] = count($isPatientPergnantrbfeedingngt1000);
            $row[] = count($unknownxnlte1000);
            $row[] = count($unknownxngt1000);
            $row[] = count($lte1000);
            $row[] = count($gt1000);
            $row[] = count($totalResult);
            $row[] = '';
            $output['aaData'][] = $row;
        }
        
    echo json_encode($output);
?>