<?php
require 'db_config.php';

// Helper for unique form UID
function generate_uid($length = 18) {
    return substr(bin2hex(random_bytes(18)), 0, $length);
}

function clean_filename($name) {
    return preg_replace('/[^A-Za-z0-9_\-.]/', '_', $name);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form_name = trim($_POST['form_name'] ?? '');
    $registration = json_decode($_POST['registration'] ?? '[]', true);
    $test = json_decode($_POST['test'] ?? '[]', true);

    if (!$form_name || (empty($registration) && empty($test))) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing form name or questions']);
        exit;
    }

    $form_uid = generate_uid();
    $created_at = date('Y-m-d H:i:s');

    // Save the form meta
    $stmt = $conn->prepare("INSERT INTO forms (form_uid, name, created_at) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $form_uid, $form_name, $created_at);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "DB error: " . $conn->error]);
        exit;
    }
    $form_id = $conn->insert_id;
    $stmt->close();

    // Prepare upload dir
    $upload_dir = "uploads/form_" . $form_uid . "/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $all_sections = [
        'registration' => $registration,
        'test' => $test
    ];

    $q_order = 1;
    $imageq_idx = 1;
    foreach ($all_sections as $section => $fields) {
        foreach ($fields as $q) {
            $type = $q['type'];
            $label = $q['label'] ?? '';
            $options = NULL;
            $image_path = NULL;

            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $options = json_encode($q['options'] ?? []);
            }

            // Handle imageq (image with question)
            if ($type === 'imageq') {
                $image_field = "imageq_" . $imageq_idx;
                if (isset($_FILES[$image_field]) && $_FILES[$image_field]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$image_field]['name'], PATHINFO_EXTENSION));
                    $file_name = clean_filename(uniqid('imgq_', true)) . "." . $ext;
                    $target = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES[$image_field]['tmp_name'], $target)) {
                        $image_path = $target;
                    }
                }
                $imageq_idx++;
            }

            // Insert the field/question
            $stmt2 = $conn->prepare("INSERT INTO form_fields (form_id, section, q_order, type, label, options, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param('isissss', $form_id, $section, $q_order, $type, $label, $options, $image_path);
            if (!$stmt2->execute()) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => "DB error: " . $conn->error]);
                exit;
            }
            $stmt2->close();
            $q_order++;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Form saved!', 'form_uid' => $form_uid]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
