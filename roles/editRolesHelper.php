<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
#require_once('../startup.php');  


$tableName1 = "roles";
$tableName2 = "roles_privileges_map";
try {
        $lastId = base64_decode($_POST['roleId']);
        if (isset($_POST['roleName']) && trim($_POST['roleName']) != "") {
                $data = array(
                        'role_name' => $_POST['roleName'],
                        'role_code' => $_POST['roleCode'],
                        'status' => $_POST['status'],
                        'landing_page' => $_POST['landingPage']
                );
                $db = $db->where('role_id', $lastId);
                $db->update($tableName1, $data);
        }
        $roleQuery = "SELECT * from roles_privileges_map where role_id=$lastId";
        $roleInfo = $db->query($roleQuery);
        if ($roleInfo) {
                $db = $db->where('role_id', $lastId);
                $db->delete($tableName2);
        }
        if ($lastId != 0 && $lastId != '') {
                foreach ($_POST['resource'] as $key => $priviId) {
                        if ($priviId == 'allow') {
                                $value = array('role_id' => $lastId, 'privilege_id' => $key);
                                $db->insert($tableName2, $value);
                        }
                }
                $_SESSION['alertMsg'] = "Roles updated successfully";
        }
        header("location:roles.php");
} catch (Exception $exc) {
        error_log($exc->getMessage());
        error_log($exc->getTraceAsString());
}
