<?php
include('../includes/MysqliDb.php');
include('../General.php');
$general=new Deforay_Commons_General();
$fName = $_POST['fName'];
$sample = $_POST['sName'];
$gender = $_POST['gender'];
$pregnant = $_POST['pregnant'];
$urgent = $_POST['urgent'];
$start_date = '';
$end_date = '';
//global config
$configQuery="SELECT value FROM global_config WHERE name ='vl_form'";
$configResult=$db->query($configQuery);
$country = $configResult[0]['value'];
if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!= ''){
   $s_c_date = explode("to", $_POST['sampleCollectionDate']);
   //print_r($s_c_date);die;
   if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
     $start_date = $general->dateFormat(trim($s_c_date[0]));
   }
   if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
     $end_date = $general->dateFormat(trim($s_c_date[1]));
   }
}
$rejected = '4';
if($fName=='' && $sample=='' && $_POST['sampleCollectionDate']=='' && $gender=='' && $pregnant=='' && $urgent==''){
   $query="SELECT vl.sample_code,vl.vl_sample_id,vl.facility_id,f.facility_name,f.facility_code FROM vl_request_form as vl INNER JOIN facility_details as f ON vl.facility_id=f.facility_id where vl.result_status NOT IN (".$rejected.") AND (sample_batch_id IS NULL OR sample_batch_id='') AND vlsm_country_id = $country";
}else{
   $query="SELECT vl.sample_code,vl.vl_sample_id,vl.facility_id,vl.result_status,f.facility_name,f.facility_code FROM vl_request_form as vl INNER JOIN facility_details as f ON vl.facility_id=f.facility_id WHERE (sample_batch_id IS NULL OR sample_batch_id='') AND vl.result_status NOT IN (".$rejected.") AND vlsm_country_id = $country";
   if(trim($fName)!=''){
      $query = $query." AND vl.facility_id IN (".implode(',',$fName).")";
   }if(trim($sample)!=''){
      $query = $query." AND vl.sample_type='".$sample."'";
   }if(trim($gender)!=''){
      $query = $query." AND vl.patient_gender='".$gender."'";
   }if(trim($pregnant)!=''){
      $query = $query." AND vl.is_patient_pregnant='".$pregnant."'";
   }if(trim($urgent)!=''){
      $query = $query." AND vl.test_urgency='".$urgent."'";
   }
   if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!= ''){
       if(trim($start_date) == trim($end_date)) {
         $query = $query.' AND DATE(sample_collection_date) = "'.$start_date.'"';
       }else{
         $query = $query.' AND DATE(sample_collection_date) >= "'.$start_date.'" AND DATE(sample_collection_date) <= "'.$end_date.'"';
       }
   }
   $query = $query." ORDER BY f.facility_name ASC";
}
$result = $db->rawQuery($query);
?>
<div class="col-md-8">
<div class="form-group">
    <div class="col-md-12">
      <div class="col-md-12">
         <div style="width:60%;margin:0 auto;clear:both;">
          <a href='#' id='select-all-samplecode' style="float:left" class="btn btn-info btn-xs">Select All&nbsp;&nbsp;<i class="icon-chevron-right"></i></a>  <a href='#' id='deselect-all-samplecode' style="float:right" class="btn btn-danger btn-xs"><i class="icon-chevron-left"></i>&nbsp;Deselect All</a>
          </div><br/><br/>
        <select id='sampleCode' name="sampleCode[]" multiple='multiple' class="search">
        <?php
        foreach($result as $sample){
          ?>
          <option value="<?php echo $sample['vl_sample_id'];?>"><?php  echo ucwords($sample['sample_code'])." - ".ucwords($sample['facility_name']);?></option>
          <?php
         }
        ?>
      </select>
      </div>
    </div>
</div>
</div>
<script>
    $(document).ready(function() {
      $('.search').multiSelect({
        selectableHeader: "<input type='text' class='search-input form-control' autocomplete='off' placeholder='Enter Sample Code'>",
        selectionHeader: "<input type='text' class='search-input form-control' autocomplete='off' placeholder='Enter Sample Code'>",
        afterInit: function(ms){
          var that = this,
              $selectableSearch = that.$selectableUl.prev(),
              $selectionSearch = that.$selectionUl.prev(),
              selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
              selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';
      
          that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
          .on('keydown', function(e){
            if (e.which === 40){
              that.$selectableUl.focus();
              return false;
            }
          });
      
          that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
          .on('keydown', function(e){
            if (e.which == 40){
              that.$selectionUl.focus();
              return false;
            }
          });
        },
        afterSelect: function(){
            //button disabled/enabled
	     if(this.qs2.cache().matchedResultsCount == noOfSamples){
		alert("You have selected Maximum no. of sample "+this.qs2.cache().matchedResultsCount);
		$("#batchSubmit").attr("disabled",false);
                $("#batchSubmit").css("pointer-events","auto");
	     }else if(this.qs2.cache().matchedResultsCount <= noOfSamples){
	       $("#batchSubmit").attr("disabled",false);
               $("#batchSubmit").css("pointer-events","auto");
	     }else if(this.qs2.cache().matchedResultsCount > noOfSamples){
               alert("You have already selected Maximum no. of sample "+noOfSamples);
	       $("#batchSubmit").attr("disabled",true);
               $("#batchSubmit").css("pointer-events","none");
	     }
	      this.qs1.cache();
	      this.qs2.cache();
       },
       afterDeselect: function(){
         //button disabled/enabled
          if(this.qs2.cache().matchedResultsCount == 0){
            $("#batchSubmit").attr("disabled",true);
	    $("#batchSubmit").css("pointer-events","none");
          }else if(this.qs2.cache().matchedResultsCount == noOfSamples){
	     alert("You have selected Maximum no. of sample "+this.qs2.cache().matchedResultsCount);
	     $("#batchSubmit").attr("disabled",false);
             $("#batchSubmit").css("pointer-events","auto");
	  }else if(this.qs2.cache().matchedResultsCount <= noOfSamples){
	    $("#batchSubmit").attr("disabled",false);
            $("#batchSubmit").css("pointer-events","auto");
	  }else if(this.qs2.cache().matchedResultsCount > noOfSamples){
	    $("#batchSubmit").attr("disabled",true);
            $("#batchSubmit").css("pointer-events","none");
	  }
	  this.qs1.cache();
	  this.qs2.cache();
       }
      });
      $('#select-all-samplecode').click(function(){
        $('#sampleCode').multiSelect('select_all');
        return false;
      });
      $('#deselect-all-samplecode').click(function(){
        $('#sampleCode').multiSelect('deselect_all');
        $("#batchSubmit").attr("disabled",true);
        $("#batchSubmit").css("pointer-events","none");
        return false;
      });
   });
</script>