  <link rel="stylesheet" media="all" type="text/css" href="assets/css/jquery-ui.1.11.0.css" />
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="assets/css/font-awesome.min.4.5.0.css">
   <!-- DataTables -->
  <link rel="stylesheet" href="./assets/plugins/datatables/dataTables.bootstrap.css">
  <style>
    .content-wrapper{
      padding:2%;
    }
    .center{text-align:center;}
  </style>
  <script type="text/javascript" src="assets/js/jquery.min.2.0.2.js"></script>
  <script type="text/javascript" src="assets/js/jquery-ui.1.11.0.js"></script>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h3 style="margin:0;">Search Facilities</h3>
    </section><hr>
     <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
              <table id="facilityModalDataTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th style="width:10%;">Select</th>
                  <th>Facility Name</th>
                  <th>Facility Code</th>
                  <th>Hub Name</th>
                  <th>Country</th>
                </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="5" class="dataTables_empty">Loading data from server</td>
                </tr>
                </tbody>
                
              </table>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content -->
  </div>
  <!-- Bootstrap 3.3.6 -->
<script src="assets/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="./assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="./assets/plugins/datatables/dataTables.bootstrap.min.js"></script>
  <script>
  var oTable = null;
  $(document).ready(function() {
        oTable = $('#facilityModalDataTable').dataTable({	
            "oLanguage": {
                "sLengthMenu": "_MENU_ records per page"
            },
            "bJQueryUI": false,
            "bAutoWidth": false,
            "bInfo": true,
            "bScrollCollapse": true,
            
            "bRetrieve": true,                        
            "aoColumns": [
                {"sClass":"center","bSortable":false},
                {"sClass":"center"},
                {"sClass":"center"},
                {"sClass":"center"},
                {"sClass":"center"}
            ],
            "aaSorting": [[ 1, "asc" ]],
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "getFacilitiesModalDetails.php",
            "fnServerData": function ( sSource, aoData, fnCallback ) {
              $.ajax({
                  "dataType": 'json',
                  "type": "POST",
                  "url": sSource,
                  "data": aoData,
                  "success": fnCallback
              });
            }
        });
    } );
  
    function getFacility(fDetails){
      parent.closeModal();
      window.parent.setFacilityDetails(fDetails);
    }
</script>