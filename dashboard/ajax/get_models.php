<?php
require_once '../../config/database.php';

if (isset($_POST['brand_id'])) {
    $brand_id = mysqli_real_escape_string($conn, $_POST['brand_id']);

    // Get active models for the selected brand
    $query = "SELECT model_id, model_name
              FROM tbl_model
              WHERE model_brand_id = ?
              AND model_status = 'active'
              ORDER BY model_name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $models = array();
    while ($row = $result->fetch_assoc()) {
        $models[] = array(
            'model_id' => $row['model_id'],
            'model_name' => $row['model_name']
        );
    }

    echo json_encode($models);
}
?>
