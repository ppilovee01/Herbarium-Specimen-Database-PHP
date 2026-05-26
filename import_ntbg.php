<?php
// ปลดล็อกเวลาและหน่วยความจำของ PHP ให้ทำงานได้ต่อเนื่อง
set_time_limit(0); 
ini_set('memory_limit', '1024M');
require_once 'db.php';

$filename = "herbarium_search_results_2026-05-21_180214.csv";
if (!file_exists($filename)) { die("<h1 style='text-align:center; color:red;'>ไม่พบไฟล์ CSV</h1>"); }

// สร้างคอลัมน์เก็บ thumbnail_path ถ้ายังไม่มี
try { $pdo->exec("ALTER TABLE herbariums ADD COLUMN thumbnail_path VARCHAR(255) AFTER image_path"); } catch(Exception $e) {}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

if (($handle = fopen($filename, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 10000, ",");
    $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
    $headerMap = array_flip($headers);
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO herbariums (
        user_id, barcode, herbarium_name, specimen_id, plant_category, common_name_en, scientific_name, 
        genus, collector_name, collection_date, country, province, island, elevation, locality, 
        description, habit, habitat, associated_species, taxonomical_notes, ethnobotanical_notes, medical_notes, image_path, thumbnail_path
    ) VALUES (
        :user_id, :barcode, :herbarium, :specimen, :category, :common_en, :scientific,
        :genus, :collector, :coll_date, :country, :province, :island, :elevation, :locality,
        :description, :habit, :habitat, :associated, :taxo, :ethno, :medical, :full_image_url, :local_thumb_path
    )");
    
    $count = 0;
    
    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $thumb_url = $data[$headerMap['Thumbnail Image URL']] ?? '';
        $full_url = $data[$headerMap['Full Image URL']] ?? '';
        
        if (empty($thumb_url)) continue; 
        
        $barcode = $data[$headerMap['Barcode']] ?? uniqid('plant_');
        $plant_category = $data[$headerMap['Plant Category']] ?? 'Unknown';
        
        // ========================================================
        // 🚀 ระบบดึงรูปและแปลงเป็น .webp อัตโนมัติ 
        // ========================================================
        $local_thumb_filename = 'uploads/thumb_' . $barcode . '.webp'; // เปลี่ยนสกุลเป็น .webp
        $absolute_path = __DIR__ . '/' . $local_thumb_filename;
        
        // เช็คว่าเคยแปลงไฟล์นี้ไว้หรือยัง
        if (!file_exists($absolute_path)) {
            // ดึงข้อมูลรูปภาพจากเว็บมาไว้ในหน่วยความจำ
            $image_string = @file_get_contents($thumb_url);
            if ($image_string !== FALSE) {
                $image = @imagecreatefromstring($image_string);
                if ($image !== FALSE) {
                    // จัดการพื้นหลังและแปลงเป็น WebP บีบอัดคุณภาพที่ 80% (ภาพยังชัดแต่ไฟล์เบามาก)
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    imagewebp($image, $absolute_path, 80);
                    imagedestroy($image);
                }
            }
        }
        // ========================================================
        
        $scientific = $data[$headerMap['NTBG Plant Name']] ?? 'Unknown Species';
        $genus = explode(' ', $scientific)[0] ?? ''; 
        $date_str = $data[$headerMap['Collection Date']] ?? '';
        $date = (!empty($date_str) && strtotime($date_str)) ? date('Y-m-d', strtotime($date_str)) : null;
        $elev = $data[$headerMap['Elevation']] ?? null; if (!is_numeric($elev)) $elev = null;
        
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? 1,
            ':barcode' => $barcode,
            ':herbarium' => $data[$headerMap['Herbarium Name']] ?? null,
            ':specimen' => $data[$headerMap['Specimen ID']] ?? null,
            ':category' => $plant_category,
            ':common_en' => $plant_category, 
            ':scientific' => $scientific,
            ':genus' => $genus,
            ':collector' => $data[$headerMap['Collector Name']] ?? null,
            ':coll_date' => $date,
            ':country' => $data[$headerMap['Country']] ?? null,
            ':province' => $data[$headerMap['State']] ?? null,
            ':island' => $data[$headerMap['Island']] ?? null,
            ':elevation' => $elev,
            ':locality' => $data[$headerMap['Locality']] ?? null,
            ':description' => $data[$headerMap['Plant Description']] ?? null,
            ':habit' => $data[$headerMap['Habit']] ?? null,
            ':habitat' => $data[$headerMap['Habitat']] ?? null,
            ':associated' => $data[$headerMap['Associated Species']] ?? null,
            ':taxo' => $data[$headerMap['Taxonomical Notes']] ?? null,
            ':ethno' => $data[$headerMap['Ethnobotanical Notes']] ?? null,
            ':medical' => $data[$headerMap['Medicial Use Notes']] ?? null,
            ':full_image_url' => $full_url,          
            ':local_thumb_path' => $local_thumb_filename // บันทึกชื่อไฟล์ .webp ลงฐานข้อมูล
        ]);
        
        if ($stmt->rowCount() > 0) { $count++; }
    }
    fclose($handle);
    echo "<h1 style='color:green; text-align:center;'>✅ นำเข้าและแปลงรูปเป็น .webp สำเร็จ! ($count รายการ)</h1>";
    echo "<p style='text-align:center;'>ประหยัดพื้นที่เซิร์ฟเวอร์ และเว็บจะโหลดเร็วขึ้นอย่างเห็นได้ชัด</p>";
    echo "<div style='text-align:center; margin-top:20px;'><a href='index.php' style='padding:10px 20px; background:#166534; color:white; text-decoration:none; border-radius:8px;'>กลับไปหน้าสืบค้น</a></div>";
}
?>