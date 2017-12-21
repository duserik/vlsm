<?php
session_start();
include('../includes/MysqliDb.php');
include('../General.php');
$tableName="package_details";
$primaryKey="package_id";
//system config
$systemConfigQuery ="SELECT * from system_config";
$systemConfigResult=$db->query($systemConfigQuery);
$sarr = array();
// now we create an associative array so that we can easily create view variables
for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
  $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
}
if($sarr['user_type']=='remoteuser'){
    $sCode = 'remote_sample_code';
  }else if($sarr['user_type']=='vluser'){
    $sCode = 'sample_code';
  }
$configQuery="SELECT value FROM global_config WHERE name ='vl_form'";
$configResult=$db->query($configQuery);
$general=new Deforay_Commons_General();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
        */
        $aColumns = array('p.package_code',"DATE_FORMAT(p.request_created_datetime,'%d-%b-%Y %H:%i:%s')");
        $orderColumns = array('p.package_code','','p.request_created_datetime');
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
        $sQuery="select p.request_created_datetime ,p.package_code,p.package_status,p.package_id,count(vl.".$sCode.") as sample_code from vl_request_form vl right join package_details p on vl.sample_package_id = p.package_id";
        if (isset($sWhere) && $sWhere != "") {
            $sWhere=' where '.$sWhere;
            $sWhere= $sWhere. 'AND vl.vlsm_country_id ="'.$configResult[0]['value'].'"';
        }else{
            $sWhere=' where vl.vlsm_country_id ="'.$configResult[0]['value'].'"';
        }
	    $sQuery = $sQuery.' '.$sWhere;
        $sQuery = $sQuery.' group by p.package_id';
        if (isset($sOrder) && $sOrder != "") {
            $sOrder = preg_replace('/(\v|\s)+/', ' ', $sOrder);
            $sQuery = $sQuery.' order by '.$sOrder;
        }
        if (isset($sLimit) && isset($sOffset)) {
            $sQuery = $sQuery.' LIMIT '.$sOffset.','. $sLimit;
        }
        //error_log($sQuery);die;
        $rResult = $db->rawQuery($sQuery);
        /* Data set length after filtering */
        $aResultFilterTotal =$db->rawQuery("select p.request_created_datetime ,p.package_code,p.package_status,count(vl.".$sCode.") as sample_code from vl_request_form vl right join package_details p on vl.sample_package_id = p.package_id $sWhere group by p.package_id order by $sOrder");
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $aResultTotal =  $db->rawQuery("select p.request_created_datetime ,p.package_code,p.package_status,count(vl.".$sCode.") as sample_code from vl_request_form vl right join package_details p on vl.sample_package_id = p.package_id where vl.vlsm_country_id ='".$configResult[0]['value']."' group by p.package_id");
       // $aResultTotal = $countResult->fetch_row();
       //print_r($aResultTotal);
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
        $package = false;
        if(isset($_SESSION['privileges']) && (in_array("editPackage.php", $_SESSION['privileges']))){
            $package = true;
        }
	
        foreach ($rResult as $aRow) {
        $humanDate="";
        $printBarcode='<a href="javascript:void(0);" class="btn btn-info btn-xs" style="margin-right: 2px;" title="Print bar code" onclick="generateBarcode(\''.base64_encode($aRow['package_id']).'\');"><i class="fa fa-barcode"> Print Barcode</i></a>';
        if(trim($aRow['request_created_datetime'])!="" && $aRow['request_created_datetime']!='0000-00-00 00:00:00'){
        $date = $aRow['request_created_datetime'];
        $humanDate =  date("d-M-Y H:i:s",strtotime($date));
        }
        $disable = '';$pointerEvent = '';
        if($aRow['package_status']=='dispatch'){
            $pointerEvent = "pointer-events:none;";
            $disable = "disabled";
        }
        $row = array();
        $row[] = $aRow['package_code'];
        $row[] = $aRow['sample_code'];
        $row[] = $humanDate;
        if($package){
            $row[] = '<a href="editPackage.php?id=' . base64_encode($aRow['package_id']) . '" class="btn btn-primary btn-xs" '.$disable.' style="margin-right: 2px;'.$pointerEvent.'" title="Edit"><i class="fa fa-pencil"> Edit</i></a>&nbsp;&nbsp;'.$printBarcode;
        }
        $output['aaData'][] = $row;
        }
    echo json_encode($output);
?>