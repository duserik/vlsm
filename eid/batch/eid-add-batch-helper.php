<?php
ob_start();
#require_once('../../startup.php');  


$general=new \Vlsm\Models\General($db);


$tableName1="batch_details";
$tableName2="eid_form";
try {

    
        if(isset($_POST['batchCode']) && trim($_POST['batchCode'])!=""){
                $data=array(
                            'machine'=>$_POST['platform'],
                            'batch_code'=>$_POST['batchCode'],
                            'batch_code_key'=>$_POST['batchCodeKey'],
                            'test_type'=>'eid',
                            'request_created_datetime'=>$general->getDateTime()
                            );
                            
                $db->insert($tableName1,$data);
                $lastId = $db->getInsertId();
                
                if($lastId > 0){  
                    for($j=0;$j<count($_POST['sampleCode']);$j++){
                        $vlSampleId = $_POST['sampleCode'][$j];
                        $value = array('sample_batch_id'=>$lastId);
                        $db=$db->where('eid_id',$vlSampleId);
                        $db->update($tableName2,$value); 
                    }
                    header("location:eid-add-batch-position.php?id=".base64_encode($lastId));
                }
        }else{
                header("location:eid-batches.php");
        }
} catch (Exception $exc) {
    echo ($exc->getMessage());
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}