<?php
session_start();
ob_start();
include('../includes/MysqliDb.php');
include ('../includes/PHPExcel.php');
include('../General.php');
$general=new Deforay_Commons_General();
$filedGroup = array();
$rs_field = 'Lab Name,LAB No,VL Testing Platform,Specimen type,Sample Testing Date,Viral Load Result(copiesl/ml),Log Value,If no result,Rejection Reason,Reviewed By,Approved By,Laboratory Scientist Comments,Status';
if(isset($rs_field) && trim($rs_field)!= ''){
     //Excel code start
     $excel = new PHPExcel();
     $sheet = $excel->getActiveSheet();
     $styleArray = array(
     'font' => array(
         'bold' => true,
         'size' => '13',
     ),
     'alignment' => array(
         'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
         'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
     ),
     'borders' => array(
         'outline' => array(
             'style' => \PHPExcel_Style_Border::BORDER_THIN,
         ),
     )
    );
    $borderStyle = array(
          'alignment' => array(
              'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
          ),
          'borders' => array(
              'outline' => array(
                  'style' => \PHPExcel_Style_Border::BORDER_THIN,
              ),
          )
     );
    $filedGroup = explode(",",$rs_field);
    $headings = $filedGroup;
    //Set heading row
     $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit(html_entity_decode('Sample'), PHPExcel_Cell_DataType::TYPE_STRING);
     $cellName = $sheet->getCellByColumnAndRow(0,1)->getColumn();
     $sheet->getStyle($cellName.'1')->applyFromArray($styleArray);
     $colNo = 1;
    foreach ($headings as $field => $value) {
     $sheet->getCellByColumnAndRow($colNo, 1)->setValueExplicit(html_entity_decode($value), PHPExcel_Cell_DataType::TYPE_STRING);
     $cellName = $sheet->getCellByColumnAndRow($colNo,1)->getColumn();
     $sheet->getStyle($cellName.'1')->applyFromArray($styleArray);
     $colNo++;
    }
    //Set query and values
    $sampleResult = $db->rawQuery($_SESSION['vlResultQuery']);
      $output = array();
      foreach($sampleResult as $sample){
         $row = array();
         $row[] = $sample['sample_code'];
         for($f=0;$f<count($filedGroup);$f++){
            if($filedGroup[$f] == "Lab Name"){
                 $field = 'lab_id';
            }elseif($filedGroup[$f] == "LAB No"){
                 $field = 'lab_no';
            }elseif($filedGroup[$f] == "VL Testing Platform"){
                 $field = 'vl_test_platform';
            }elseif($filedGroup[$f] == "Specimen type"){
                 $field = 'sample_name';
            }elseif($filedGroup[$f] == "Sample Testing Date"){
                 $field = 'lab_tested_date';
            }elseif($filedGroup[$f] == "Viral Load Result(copiesl/ml)"){
                 $field = 'absolute_value';
            }elseif($filedGroup[$f] == "Log Value"){
                 $field = 'log_value';
            }elseif($filedGroup[$f] == "If no result"){
                 $field = 'rejection';
            }elseif($filedGroup[$f] == "Rejection Reason"){
                 $field = 'rejection_reason_name';
            }elseif($filedGroup[$f] == "Reviewed By"){
                 $field = 'result_reviewed_by';
            }elseif($filedGroup[$f] == "Approved By"){
                 $field = 'result_approved_by';
            }elseif($filedGroup[$f] == "Laboratory Scientist Comments"){
                 $field = 'comments';
            }elseif($filedGroup[$f] == "Status"){
                 $field = 'status_name';
            }
            
            if($field ==  'result_reviewed_by'){
               $fValueQuery="SELECT u.user_name as reviewedBy FROM vl_request_form as vl LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id LEFT JOIN r_sample_type as s_type ON s_type.sample_id=vl.sample_id LEFT JOIN r_sample_rejection_reasons as s_r_r ON s_r_r.rejection_reason_id=vl.sample_rejection_reason LEFT JOIN user_details as u ON u.user_id = vl.result_reviewed_by where vl.vl_sample_id = '".$sample['vl_sample_id']."'";
            }elseif($field ==  'result_approved_by'){
               $fValueQuery="SELECT u.user_name as approvedBy FROM vl_request_form as vl LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id LEFT JOIN r_sample_type as s_type ON s_type.sample_id=vl.sample_id LEFT JOIN r_sample_rejection_reasons as s_r_r ON s_r_r.rejection_reason_id=vl.sample_rejection_reason LEFT JOIN user_details as u ON u.user_id = vl.result_approved_by where vl.vl_sample_id = '".$sample['vl_sample_id']."'";
            }elseif($field ==  'lab_id'){
               $fValueQuery="SELECT f.facility_name as labName FROM vl_request_form as vl LEFT JOIN facility_details as f ON vl.lab_id=f.facility_id where vl.vl_sample_id = '".$sample['vl_sample_id']."'";
            }else{
              $fValueQuery="SELECT $field FROM vl_request_form as vl LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id LEFT JOIN r_sample_type as s_type ON s_type.sample_id=vl.sample_id LEFT JOIN r_sample_rejection_reasons as s_r_r ON s_r_r.rejection_reason_id=vl.sample_rejection_reason LEFT JOIN testing_status as t_s ON t_s.status_id=vl.status where vl.vl_sample_id = '".$sample['vl_sample_id']."'";
            }
            $fValueResult = $db->rawQuery($fValueQuery);
            $fieldValue = '';
            if(count($fValueResult) >0){
               if($field == 'lab_tested_date'){
                    if(isset($fValueResult[0][$field]) && trim($fValueResult[0][$field])!= '' && trim($fValueResult[0][$field])!= '0000-00-00 00:00:00'){
                        $xplodDate = explode(" ",$fValueResult[0][$field]);
                        $fieldValue=$general->humanDateFormat($xplodDate[0])." ".$xplodDate[1];  
                    }
               }elseif($field ==  'vl_test_platform' || $field ==  'gender' || $field == 'rejection'){
                 $fieldValue = ucwords(str_replace("_"," ",$fValueResult[0][$field]));
               }elseif($field ==  'result_reviewed_by'){
                 $fieldValue = $fValueResult[0]['reviewedBy'];
               }elseif($field ==  'result_approved_by'){
                 $fieldValue = $fValueResult[0]['approvedBy'];
               }elseif($field ==  'lab_id'){
                 $fieldValue = $fValueResult[0]['labName'];
               }else{
                 $fieldValue = $fValueResult[0][$field];
               }
            }
           $row[] = $fieldValue;
         }
        $output[] = $row;
      }
     $start = (count($output));
     foreach ($output as $rowNo => $rowData) {
          $colNo = 0;
          foreach ($rowData as $field => $value) {
            $rRowCount = $rowNo + 2;
            $cellName = $sheet->getCellByColumnAndRow($colNo,$rRowCount)->getColumn();
            $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
            $sheet->getStyle($cellName . $start)->applyFromArray($borderStyle);
            $sheet->getDefaultRowDimension()->setRowHeight(15);
            $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value), PHPExcel_Cell_DataType::TYPE_STRING);
            $colNo++;
          }
     }
     $filename = '';
     $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
     $filename = 'vl-test-result-' . date('d-M-Y-H-i-s') . '.xls';
     $pathFront=realpath('../temporary');
     $writer->save($pathFront. DIRECTORY_SEPARATOR . $filename);
    echo $filename;
}else{
    echo $filename = '';
}