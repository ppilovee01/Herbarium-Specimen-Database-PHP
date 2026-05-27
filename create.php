<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะผู้ดูแลระบบ'); window.location.href='index.php';</script>"; exit;
}

// สร้าง Barcode อัตโนมัติให้เป็นค่าเริ่มต้น
$auto_barcode = 'HRB-' . date('Ymd') . '-' . rand(1000, 9999);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าและตัดช่องว่าง
    $barcode = trim($_POST['barcode']);
    $specimen_id = trim($_POST['specimen_id']);
    $category = trim($_POST['plant_category'] ?? 'dicot');
    $scientific = trim($_POST['scientific_name']);
    $family = trim($_POST['family_name']);
    $genus = trim($_POST['genus']);
    $common_th = trim($_POST['common_name_th']);
    $common_en = trim($_POST['common_name_en']);
    $collector = trim($_POST['collector_name']);
    $coll_date = trim($_POST['collection_date']);
    
    // แปลงวันที่ ถ้าไม่ได้กรอกให้เป็น NULL
    $coll_date = !empty($coll_date) ? $coll_date : null;
    
    $country = trim($_POST['country']);
    $province = trim($_POST['province']);
    $island = trim($_POST['island']);
    $elevation = trim($_POST['elevation']);
    
    // แปลงความสูง ถ้าไม่ได้กรอกให้เป็น NULL
    $elevation = is_numeric($elevation) ? (int)$elevation : null;
    
    $locality = trim($_POST['locality']);
    $desc = trim($_POST['description']);
    $habit = trim($_POST['habit']);
    $habitat = trim($_POST['habitat']);
    
    // ใช้ฟิลด์ใหม่ตามฐานข้อมูล NTBG
    $associated = trim($_POST['associated_species']);
    $taxo = trim($_POST['taxonomical_notes']);
    $ethno = trim($_POST['ethnobotanical_notes']);
    $medical = trim($_POST['medical_notes']);
    
    $image_path = '';
    $thumbnail_path = '';

    // ระบบจัดการรูปภาพ (แปลงเป็น .webp อัตโนมัติ)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $tmp_name = $_FILES['image']['tmp_name'];
        if (getimagesize($tmp_name) !== false) {
            if (extension_loaded('gd')) {
                $image_string = file_get_contents($tmp_name);
                $image = @imagecreatefromstring($image_string);
                if ($image !== false) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    $filename = uniqid('plant_') . '.webp';
                    $destination = __DIR__ . '/uploads/' . $filename;
                    imagewebp($image, $destination, 80); // บีบอัด 80%
                    imagedestroy($image);
                    
                    // เก็บ path เดียวกันลงทั้ง 2 ช่อง เพราะอัปโหลดเองในระบบไม่ได้ดึงลิงก์จากเน็ต
                    $image_path = 'uploads/' . $filename;
                    $thumbnail_path = 'uploads/' . $filename; 
                }
            } else {
                // เซิร์ฟเวอร์ไม่ได้เปิดใช้งาน GD extension: ให้อัปโหลดและเก็บไฟล์สกุลภาพเดิมโดยตรง
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'jpg';
                }
                $filename = uniqid('plant_') . '.' . $ext;
                $destination = __DIR__ . '/uploads/' . $filename;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $image_path = 'uploads/' . $filename;
                    $thumbnail_path = 'uploads/' . $filename;
                }
            }
        }
    }

    // แปลภาษาอัตโนมัติหากเชื่อมต่ออินเทอร์เน็ต
    $desc_th = '';
    $habit_th = '';
    $habitat_th = '';
    $associated_th = '';
    $taxo_th = '';
    $ethno_th = '';
    $medical_th = '';

    if (is_online()) {
        $desc_th = get_thai_translation_if_needed($desc);
        $habit_th = get_thai_translation_if_needed($habit);
        $habitat_th = get_thai_translation_if_needed($habitat);
        $associated_th = get_thai_translation_if_needed($associated);
        $taxo_th = get_thai_translation_if_needed($taxo);
        $ethno_th = get_thai_translation_if_needed($ethno);
        $medical_th = get_thai_translation_if_needed($medical);
    }

    // คำสั่ง SQL ที่ถูกต้องและตรงกับตารางล่าสุด (รวมฟิลด์ภาษาไทย)
    $stmt = $pdo->prepare("INSERT INTO herbariums (
        user_id, barcode, specimen_id, plant_category, common_name_th, common_name_en, scientific_name, 
        family_name, genus, collector_name, collection_date, country, province, island, 
        elevation, locality, description, habit, habitat, associated_species, 
        taxonomical_notes, ethnobotanical_notes, medical_notes, image_path, thumbnail_path,
        description_th, habit_th, habitat_th, associated_species_th, taxonomical_notes_th, ethnobotanical_notes_th, medical_notes_th
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");
    
    // บันทึกข้อมูล
    $stmt->execute([
        $_SESSION['user_id'], $barcode, $specimen_id, $category, $common_th, $common_en, $scientific, 
        $family, $genus, $collector, $coll_date, $country, $province, $island, 
        $elevation, $locality, $desc, $habit, $habitat, $associated, 
        $taxo, $ethno, $medical, $image_path, $thumbnail_path,
        $desc_th, $habit_th, $habitat_th, $associated_th, $taxo_th, $ethno_th, $medical_th
    ]);
    
    echo "<script>alert('บันทึกข้อมูลพรรณไม้สำเร็จ!'); window.location.href='dashboard.php';</script>";
    exit;
}
require_once 'header.php';
?>

<div class="max-w-5xl mx-auto my-4 sm:my-8 px-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        
        <div class="bg-green-900 px-4 sm:px-8 py-4 sm:py-6 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold">บันทึกข้อมูลพรรณไม้ (Data Entry)</h2>
                <p class="text-green-200 text-xs sm:text-sm mt-1">อ้างอิงมาตรฐานโครงสร้าง NTBG Database</p>
            </div>
            <div class="text-left sm:text-right w-full sm:w-auto bg-green-950 sm:bg-transparent p-2 sm:p-0 rounded-lg sm:rounded-none">
                <span class="block text-xs text-green-300">รหัสบาร์โค้ดสร้างอัตโนมัติ</span>
                <span class="font-mono font-bold text-base sm:text-lg"><?= $auto_barcode ?></span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-4 sm:p-8 space-y-6 sm:space-y-8 bg-gray-50">
            
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 border-b pb-2 mb-4">🌿 การระบุชนิด (Plant Identification)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Barcode *</label>
                        <input type="text" name="barcode" value="<?= $auto_barcode ?>" required class="w-full px-3 py-2 border rounded-lg bg-gray-100 font-mono text-blue-700 font-bold text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Specimen ID</label>
                        <input type="text" name="specimen_id" placeholder="Ex: 067928" class="w-full px-3 py-2 border rounded-lg text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">หมวดหมู่พรรณไม้ (Category)</label>
                        <select name="plant_category" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                            <option value="dicot">dicot (พืชใบเลี้ยงคู่)</option>
                            <option value="monocot">monocot (พืชใบเลี้ยงเดี่ยว)</option>
                            <option value="pteridophyte">pteridophyte (เฟิร์น)</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อวิทยาศาสตร์ (Scientific Name) *</label>
                        <input type="text" name="scientific_name" required class="w-full px-3 py-2 border rounded-lg italic text-sm" placeholder="Ex: Acacia confusa">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อวงศ์ (Family)</label>
                        <input type="text" name="family_name" class="w-full px-3 py-2 border rounded-lg uppercase text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">สกุล (Genus)</label>
                        <input type="text" name="genus" class="w-full px-3 py-2 border rounded-lg italic text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อท้องถิ่น / ชื่อไทย</label>
                        <input type="text" name="common_name_th" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อสามัญ (English)</label>
                        <input type="text" name="common_name_en" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 border-b pb-2 mb-4">📍 ข้อมูลสถานที่และการเก็บ (Geography & Collection)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ผู้เก็บรวบรวม (Collector Name)</label>
                        <input type="text" name="collector_name" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">วันที่เก็บ (Collection Date)</label>
                        <input type="date" name="collection_date" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ระดับความสูง (Elevation - m)</label>
                        <input type="number" name="elevation" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="เมตร (m)">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ประเทศ (Country)</label>
                        <input type="text" name="country" value="Thailand" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">จังหวัด/รัฐ (Province / State)</label>
                        <input type="text" name="province" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">เกาะ / พื้นที่ (Island)</label>
                        <input type="text" name="island" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-600 mb-1">สถานที่พบละเอียด (Locality)</label>
                        <textarea name="locality" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 border-b pb-2 mb-4">📝 บันทึกลักษณะและพฤกษศาสตร์ (Notes)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ลักษณะวิสัย (Habit)</label>
                        <input type="text" name="habit" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="เช่น Tree, Shrub, Vine...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ถิ่นที่อยู่อาศัย (Habitat)</label>
                        <input type="text" name="habitat" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">คำอธิบายลักษณะพืช (Plant Description)</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">พันธุ์ไม้ที่พบร่วม (Associated Species)</label>
                        <textarea name="associated_species" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกทางอนุกรมวิธาน (Taxonomical Notes)</label>
                        <textarea name="taxonomical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกพฤกษศาสตร์พื้นบ้าน (Ethnobotanical Notes)</label>
                        <textarea name="ethnobotanical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกสรรพคุณทางยา (Medical Use Notes)</label>
                        <textarea name="medical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 border-b pb-2 mb-4">🖼️ ภาพถ่าย (Herbarium Image)</h3>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-8 text-center bg-gray-50 hover:bg-gray-100 transition">
                    <input type="file" name="image" accept="image/*" class="w-full text-xs sm:text-sm text-gray-600 file:mr-2 sm:file:mr-4 file:py-2 file:px-3 sm:file:px-4 file:rounded-lg file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 cursor-pointer">
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-3">ภาพจะถูกบีบอัดเป็นสกุล .webp อัตโนมัติ (ช่วยประหยัดพื้นที่เซิร์ฟเวอร์)</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                <a href="dashboard.php" class="w-full sm:w-auto text-center bg-gray-300 text-gray-700 font-bold py-3 px-8 rounded-lg hover:bg-gray-400 transition shadow">ยกเลิก</a>
                <button type="submit" class="w-full sm:w-auto bg-green-700 text-white font-bold py-3 px-10 rounded-lg hover:bg-green-800 transition shadow-lg text-base sm:text-lg">💾 บันทึกเข้าฐานข้อมูล</button>
            </div>
            
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>