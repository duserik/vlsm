<?php
ob_start();

$title = "Edit Batch Position";

#require_once('../../startup.php');
include_once(APPLICATION_PATH . '/header.php');
$id = base64_decode($_GET['id']);
if (!isset($id) || trim($id) == '') {
	header("location:batchcode.php");
}
$content = '';
$displayOrder = array();
$batchQuery = "SELECT * from batch_details as b_d INNER JOIN import_config as i_c ON i_c.config_id=b_d.machine where batch_id=$id";
$batchInfo = $db->query($batchQuery);
// Config control
$configControlQuery = "SELECT * from import_config_controls where config_id=".$batchInfo[0]['config_id'];
$configControlInfo = $db->query($configControlQuery);
$configControl = array();
foreach($configControlInfo as $info){
	if($info['test_type'] == 'vl'){
		$configControl[$info['test_type']]['noHouseCtrl'] = $info['number_of_in_house_controls'];
		$configControl[$info['test_type']]['noManufacturerCtrl'] = $info['number_of_manufacturer_controls'];
		$configControl[$info['test_type']]['noCalibrators'] = $info['number_of_calibrators'];
	}
}
if (!isset($batchInfo) || count($batchInfo) == 0) {
	header("location:batchcode.php");
}
if (isset($batchInfo[0]['label_order']) && trim($batchInfo[0]['label_order']) != '') {
	$jsonToArray = json_decode($batchInfo[0]['label_order'], true);
	for ($j = 0; $j < count($jsonToArray); $j++) {
		$displayOrder[] = $jsonToArray[$j];
		$xplodJsonToArray = explode("_", $jsonToArray[$j]);
		if (count($xplodJsonToArray) > 1 && $xplodJsonToArray[0] == "s") {
			$sampleQuery = "SELECT sample_code from vl_request_form where vl_sample_id=$xplodJsonToArray[1]";
			$sampleResult = $db->query($sampleQuery);
			$label = $sampleResult[0]['sample_code'];
		} else {
			$label = str_replace("_", " ", $jsonToArray[$j]);
			$label = str_replace("in house", "In-House", $label);
			$label = ucwords(str_replace("no of ", " ", $label));
		}
		$content .= '<li class="ui-state-default" id="' . $jsonToArray[$j] . '">' . $label . '</li>';
	}
} else {
	if(isset($configControl['vl']['noHouseCtrl']) && trim($configControl['vl']['noHouseCtrl'])!='' && $configControl['vl']['noHouseCtrl']>0){
		foreach(range(1,$configControl['vl']['noHouseCtrl']) as $h){
			$displayOrder[] = "no_of_in_house_controls_".$h;
			$content.='<li class="ui-state-default" id="no_of_in_house_controls_'.$h.'">In-House Controls '.$h.'</li>';
		}
	}if(isset($configControl['vl']['noManufacturerCtrl']) && trim($configControl['vl']['noManufacturerCtrl'])!='' && $configControl['vl']['noManufacturerCtrl']>0){
		foreach(range(1,$configControl['vl']['noManufacturerCtrl']) as $m){
			$displayOrder[] = "no_of_manufacturer_controls_".$m;
		   	$content.='<li class="ui-state-default" id="no_of_manufacturer_controls_'.$m.'">Manufacturer Controls '.$m.'</li>';	
		}
	}if(isset($configControl['vl']['noCalibrators']) && trim($configControl['vl']['noCalibrators'])!='' && $configControl['vl']['noCalibrators']>0){
		foreach(range(1,$configControl['vl']['noCalibrators']) as $c){
			$displayOrder[] = "no_of_calibrators_".$c;	 	
		   	$content.='<li class="ui-state-default" id="no_of_calibrators_'.$c.'">Calibrators '.$c.'</li>';
		}
	}
	$samplesQuery = "SELECT vl_sample_id,sample_code from vl_request_form where sample_batch_id=$id ORDER BY sample_code ASC";
	$samplesInfo = $db->query($samplesQuery);
	foreach ($samplesInfo as $sample) {
		$displayOrder[] = "s_" . $sample['vl_sample_id'];
		$content .= '<li class="ui-state-default" id="s_' . $sample['vl_sample_id'] . '">' . $sample['sample_code'] . '</li>';
	}
}
?>
<style>
	#sortableRow {
		list-style-type: none;
		margin: 0px 0px 30px 0px;
		padding: 0;
		width: 100%;
		text-align: center;
	}

	#sortableRow li {
		color: #333 !important;
		font-size: 16px;
		border-radius: 10px;
		margin-bottom: 4px;
	}
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><i class="fa fa-edit"></i> Edit Batch Controls Position</h1>
		<ol class="breadcrumb">
			<li><a href="/"><i class="fa fa-dashboard"></i> Home</a></li>
			<li class="active">Batch</li>
		</ol>
	</section>

	<!-- Main content -->
	<section class="content">
		
		<div class="box box-default">
			<div class="box-header with-border">
				<h4><strong>Batch Code : <?php echo (isset($batchInfo[0]['batch_code'])) ? $batchInfo[0]['batch_code'] : ''; ?></strong></h4>
			</div>
			<!-- /.box-header -->
			<div class="box-body">
				<!-- form start -->
				<form class="form-horizontal" method='post' name='editBatchControlsPosition' id='editBatchControlsPosition' autocomplete="off" action="editBatchControlsPositionHelper.php">
					<div class="box-body">
						<div class="row" id="displayOrderDetails">
							<div class="col-md-8">
								<ul id="sortableRow">
									<?php
									echo $content;
									?>
								</ul>
							</div>
						</div>
					</div>
					<!-- /.box-body -->
					<div class="box-footer">
						<input type="hidden" name="sortOrders" id="sortOrders" value="<?php echo implode(",", $displayOrder); ?>" />
						<input type="hidden" name="batchId" id="batchId" value="<?php echo $id; ?>" />
						<a class="btn btn-primary" href="javascript:void(0);" onclick="validateNow();return false;">Submit</a>
						<a href="batchcode.php" class="btn btn-default"> Cancel</a>
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
<script>
	sortedTitle = [];
	$(document).ready(function() {
		function cleanArray(actual) {
			var newArray = new Array();
			for (var i = 0; i < actual.length; i++) {
				if (actual[i]) {
					newArray.push(actual[i]);
				}
			}
			return newArray;
		}

		$("#sortableRow").sortable({
			opacity: 0.6,
			cursor: 'move',
			update: function() {
				sortedTitle = cleanArray($(this).sortable("toArray"));
				$("#sortOrders").val("");
				$("#sortOrders").val(sortedTitle);
			}
		}).disableSelection();
	});

	function validateNow() {
		flag = deforayValidator.init({
			formId: 'editBatchControlsPosition'
		});

		if (flag) {
			$.blockUI();
			document.getElementById('editBatchControlsPosition').submit();
		}
	}
</script>
<?php
include(APPLICATION_PATH . '/footer.php');
?>