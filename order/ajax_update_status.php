<?php
require_once "../auth.php";
require_once "../php/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $field = $_POST['field']; 
    $value = mysqli_real_escape_string($conn, $_POST['value']);


    if (in_array($field, ['order_status', 'delivery_status'])) {
        $sql = "UPDATE orders SET $field = '$value' WHERE order_id = $order_id";
        
        if (mysqli_query($conn, $sql)) {

            if ($field === 'order_status' && $value === 'ชำระแล้ว') {
                mysqli_query($conn, "UPDATE orders SET due_date = NULL WHERE order_id = $order_id");
            }
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error']);
    exit;
}

?>

