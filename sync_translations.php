<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit;
}

// เช็คสถานะออนไลน์อีกรอบ
if (!is_online()) {
    echo "<script>alert('กรุณาเชื่อมต่ออินเทอร์เน็ตเพื่อทำรายการซิงค์'); window.location.href='dashboard.php';</script>";
    exit;
}

// จำกัดการประมวลผลสูงสุด 30 รายการต่อคลิกเพื่อความปลอดภัยและไม่โหลดเกินขอบเขต
$sql = "SELECT id, description, habitat, habit, associated_species, taxonomical_notes, ethnobotanical_notes, medical_notes 
        FROM herbariums 
        WHERE (description IS NOT NULL AND description != '' AND (description_th IS NULL OR description_th = ''))
           OR (habitat IS NOT NULL AND habitat != '' AND (habitat_th IS NULL OR habitat_th = ''))
        LIMIT 30";

$stmt = $pdo->query($sql);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    header("Location: dashboard.php"); exit;
}

$synced_count = 0;
$update_stmt = $pdo->prepare("UPDATE herbariums SET 
    description_th = ?, 
    habitat_th = ?, 
    habit_th = ?, 
    associated_species_th = ?, 
    taxonomical_notes_th = ?, 
    ethnobotanical_notes_th = ?, 
    medical_notes_th = ? 
    WHERE id = ?");

foreach ($records as $row) {
    $id = $row['id'];
    
    $fields_to_translate = [
        'description' => 'description_th',
        'habitat' => 'habitat_th',
        'habit' => 'habit_th',
        'associated_species' => 'associated_species_th',
        'taxonomical_notes' => 'taxonomical_notes_th',
        'ethnobotanical_notes' => 'ethnobotanical_notes_th',
        'medical_notes' => 'medical_notes_th'
    ];
    
    $translations = [];
    $failed = false;
    
    foreach ($fields_to_translate as $eng_field => $th_field) {
        $eng_text = $row[$eng_field];
        if (!empty($eng_text)) {
            $translated_text = translate_text_to_thai($eng_text);
            if (!empty($translated_text)) {
                $translations[$th_field] = $translated_text;
            } else {
                $translations[$th_field] = null;
            }
            usleep(200000); // 0.2 seconds delay between individual API calls
        } else {
            $translations[$th_field] = null;
        }
    }
    
    $update_stmt->execute([
        $translations['description_th'],
        $translations['habitat_th'],
        $translations['habit_th'],
        $translations['associated_species_th'],
        $translations['taxonomical_notes_th'],
        $translations['ethnobotanical_notes_th'],
        $translations['medical_notes_th'],
        $id
    ]);
    
    $synced_count++;
    usleep(500000); // 0.5 seconds delay between records
}

header("Location: dashboard.php?sync_success=" . $synced_count);
exit;
?>
