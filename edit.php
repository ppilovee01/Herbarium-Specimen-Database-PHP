<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะผู้ดูแลระบบ'); window.location.href='index.php';</script>"; exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM herbariums WHERE id = ?");
$stmt->execute([$id]);
$plant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$plant) { header("Location: dashboard.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode']);
    $specimen_id = trim($_POST['specimen_id']);
    $category = trim($_POST['plant_category'] ?? 'dicot');
    $common_th = trim($_POST['common_name_th']);
    $common_en = trim($_POST['common_name_en']);
    $scientific = trim($_POST['scientific_name']);
    $family = trim($_POST['family_name']);
    $genus = trim($_POST['genus']);
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
    
    $associated = trim($_POST['associated_species']);
    $taxo = trim($_POST['taxonomical_notes']);
    $ethno = trim($_POST['ethnobotanical_notes']);
    $medical = trim($_POST['medical_notes']);

    // รับค่าคำแปลภาษาไทยเพิ่มเติมจากหน้าฟอร์ม
    $desc_th = trim($_POST['description_th'] ?? '');
    $habit_th = trim($_POST['habit_th'] ?? '');
    $habitat_th = trim($_POST['habitat_th'] ?? '');
    $associated_th = trim($_POST['associated_species_th'] ?? '');
    $taxo_th = trim($_POST['taxonomical_notes_th'] ?? '');
    $ethno_th = trim($_POST['ethnobotanical_notes_th'] ?? '');
    $medical_th = trim($_POST['medical_notes_th'] ?? '');

    // ระบบแปลภาษาอัตโนมัติหากเชื่อมต่ออินเทอร์เน็ต และคำแปลเก่าว่างหรือมีการปรับปรุงภาษาอังกฤษ
    if (is_online()) {
        if (empty($desc_th) || ($desc !== $plant['description'] && $desc_th === $plant['description_th'])) {
            $desc_th = get_thai_translation_if_needed($desc);
        }
        if (empty($habit_th) || ($habit !== $plant['habit'] && $habit_th === $plant['habit_th'])) {
            $habit_th = get_thai_translation_if_needed($habit);
        }
        if (empty($habitat_th) || ($habitat !== $plant['habitat'] && $habitat_th === $plant['habitat_th'])) {
            $habitat_th = get_thai_translation_if_needed($habitat);
        }
        if (empty($associated_th) || ($associated !== $plant['associated_species'] && $associated_th === $plant['associated_species_th'])) {
            $associated_th = get_thai_translation_if_needed($associated);
        }
        if (empty($taxo_th) || ($taxo !== $plant['taxonomical_notes'] && $taxo_th === $plant['taxonomical_notes_th'])) {
            $taxo_th = get_thai_translation_if_needed($taxo);
        }
        if (empty($ethno_th) || ($ethno !== $plant['ethnobotanical_notes'] && $ethno_th === $plant['ethnobotanical_notes_th'])) {
            $ethno_th = get_thai_translation_if_needed($ethno);
        }
        if (empty($medical_th) || ($medical !== $plant['medical_notes'] && $medical_th === $plant['medical_notes_th'])) {
            $medical_th = get_thai_translation_if_needed($medical);
        }
    }
    
    $image_path = $plant['image_path'];
    $thumbnail_path = $plant['thumbnail_path'];

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
                    imagewebp($image, $destination, 80);
                    imagedestroy($image);
                    
                    // ลบรูปภาพเดิมหากไม่ใช่ลิงก์ URL จากภายนอก
                    if (!empty($plant['image_path']) && !filter_var($plant['image_path'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . '/' . $plant['image_path'])) {
                        unlink(__DIR__ . '/' . $plant['image_path']);
                    }
                    if (!empty($plant['thumbnail_path']) && !filter_var($plant['thumbnail_path'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . '/' . $plant['thumbnail_path'])) {
                        unlink(__DIR__ . '/' . $plant['thumbnail_path']);
                    }
                    
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
                    // ลบรูปภาพเดิมหากไม่ใช่ลิงก์ URL จากภายนอก
                    if (!empty($plant['image_path']) && !filter_var($plant['image_path'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . '/' . $plant['image_path'])) {
                        unlink(__DIR__ . '/' . $plant['image_path']);
                    }
                    if (!empty($plant['thumbnail_path']) && !filter_var($plant['thumbnail_path'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . '/' . $plant['thumbnail_path'])) {
                        unlink(__DIR__ . '/' . $plant['thumbnail_path']);
                    }
                    
                    $image_path = 'uploads/' . $filename;
                    $thumbnail_path = 'uploads/' . $filename;
                }
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE herbariums SET 
        barcode = ?, 
        specimen_id = ?, 
        plant_category = ?, 
        common_name_th = ?, 
        common_name_en = ?, 
        scientific_name = ?, 
        family_name = ?, 
        genus = ?, 
        collector_name = ?, 
        collection_date = ?, 
        country = ?, 
        province = ?, 
        island = ?, 
        elevation = ?, 
        locality = ?, 
        description = ?, 
        habit = ?, 
        habitat = ?, 
        associated_species = ?, 
        taxonomical_notes = ?, 
        ethnobotanical_notes = ?, 
        medical_notes = ?, 
        image_path = ?, 
        thumbnail_path = ?, 
        description_th = ?, 
        habit_th = ?, 
        habitat_th = ?, 
        associated_species_th = ?, 
        taxonomical_notes_th = ?, 
        ethnobotanical_notes_th = ?, 
        medical_notes_th = ? 
        WHERE id = ?");
        
    $stmt->execute([
        $barcode, 
        $specimen_id, 
        $category, 
        $common_th, 
        $common_en, 
        $scientific, 
        $family, 
        $genus, 
        $collector, 
        $coll_date, 
        $country, 
        $province, 
        $island, 
        $elevation, 
        $locality, 
        $desc, 
        $habit, 
        $habitat, 
        $associated, 
        $taxo, 
        $ethno, 
        $medical, 
        $image_path, 
        $thumbnail_path, 
        $desc_th, 
        $habit_th, 
        $habitat_th, 
        $associated_th, 
        $taxo_th, 
        $ethno_th, 
        $medical_th, 
        $id
    ]);
    
    header("Location: dashboard.php"); exit;
}
require_once 'header.php';
?>

<div class="max-w-5xl mx-auto my-8 px-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-blue-800 px-8 py-6 text-white"><h2 class="text-2xl font-bold">แก้ไขข้อมูล (Edit Herbarium Record)</h2></div>

        <form method="POST" enctype="multipart/form-data" class="p-8 space-y-8 bg-gray-50">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">🌿 การระบุชนิด (Plant Identification)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Barcode *</label>
                        <input type="text" name="barcode" value="<?= htmlspecialchars($plant['barcode']) ?>" required class="w-full px-3 py-2 border rounded-lg bg-gray-100 font-mono text-blue-700 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Specimen ID</label>
                        <input type="text" name="specimen_id" value="<?= htmlspecialchars($plant['specimen_id'] ?? '') ?>" placeholder="Ex: 067928" class="w-full px-3 py-2 border rounded-lg text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">หมวดหมู่พรรณไม้ (Category)</label>
                        <select name="plant_category" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                            <option value="dicot" <?= $plant['plant_category'] === 'dicot' ? 'selected' : '' ?>>dicot (พืชใบเลี้ยงคู่)</option>
                            <option value="monocot" <?= $plant['plant_category'] === 'monocot' ? 'selected' : '' ?>>monocot (พืชใบเลี้ยงเดี่ยว)</option>
                            <option value="pteridophyte" <?= $plant['plant_category'] === 'pteridophyte' ? 'selected' : '' ?>>pteridophyte (เฟิร์น)</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อวิทยาศาสตร์ *</label>
                        <input type="text" name="scientific_name" value="<?= htmlspecialchars($plant['scientific_name']) ?>" required class="w-full px-3 py-2 border rounded-lg italic">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อวงศ์ (Family)</label>
                        <input type="text" name="family_name" value="<?= htmlspecialchars($plant['family_name']) ?>" class="w-full px-3 py-2 border rounded-lg uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">สกุล (Genus)</label>
                        <input type="text" name="genus" value="<?= htmlspecialchars($plant['genus']) ?>" class="w-full px-3 py-2 border rounded-lg italic">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อท้องถิ่น / ชื่อไทย</label>
                        <input type="text" name="common_name_th" value="<?= htmlspecialchars($plant['common_name_th'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">ชื่อสามัญ (English)</label>
                        <input type="text" name="common_name_en" value="<?= htmlspecialchars($plant['common_name_en'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">📍 ข้อมูลสถานที่และการเก็บ (Geography & Collection)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ผู้เก็บรวบรวม (Collector Name)</label>
                        <input type="text" name="collector_name" value="<?= htmlspecialchars($plant['collector_name'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">วันที่เก็บ (Collection Date)</label>
                        <input type="date" name="collection_date" value="<?= htmlspecialchars($plant['collection_date'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ระดับความสูง (Elevation - m)</label>
                        <input type="number" name="elevation" value="<?= htmlspecialchars($plant['elevation'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ประเทศ</label>
                        <input type="text" name="country" value="<?= htmlspecialchars($plant['country'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">จังหวัด / รัฐ (Province / State)</label>
                        <input type="text" name="province" value="<?= htmlspecialchars($plant['province'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">เกาะ / พื้นที่ (Island)</label>
                        <input type="text" name="island" value="<?= htmlspecialchars($plant['island'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-600 mb-1">สถานที่พบละเอียด (Locality)</label>
                        <textarea name="locality" rows="2" class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($plant['locality'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">📝 ลักษณะทางพฤกษศาสตร์ และบันทึกเพิ่มเติม (Notes & Translations)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ลักษณะวิสัย (Habit - English)</label>
                        <input type="text" name="habit" value="<?= htmlspecialchars($plant['habit'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="เช่น Tree, Shrub, Vine...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 mb-1">ลักษณะวิสัย (Habit - คำแปลภาษาไทย)</label>
                        <input type="text" name="habit_th" value="<?= htmlspecialchars($plant['habit_th'] ?? '') ?>" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">ถิ่นที่อยู่อาศัย (Habitat - English)</label>
                        <input type="text" name="habitat" value="<?= htmlspecialchars($plant['habitat'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 mb-1">ถิ่นที่อยู่อาศัย (Habitat - คำแปลภาษาไทย)</label>
                        <input type="text" name="habitat_th" value="<?= htmlspecialchars($plant['habitat_th'] ?? '') ?>" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">คำอธิบายลักษณะ (Plant Description - English)</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($plant['description'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-green-700 mb-1">คำอธิบายลักษณะ (Plant Description - คำแปลภาษาไทย)</label>
                        <textarea name="description_th" rows="3" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง"><?= htmlspecialchars($plant['description_th'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">พันธุ์ไม้ที่พบร่วม (Associated Species - English)</label>
                        <textarea name="associated_species" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($plant['associated_species'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-green-700 mb-1">พันธุ์ไม้ที่พบร่วม (Associated Species - คำแปลภาษาไทย)</label>
                        <textarea name="associated_species_th" rows="2" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง"><?= htmlspecialchars($plant['associated_species_th'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกทางอนุกรมวิธาน (Taxonomical Notes - English)</label>
                        <textarea name="taxonomical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($plant['taxonomical_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-green-700 mb-1">บันทึกทางอนุกรมวิธาน (Taxonomical Notes - คำแปลภาษาไทย)</label>
                        <textarea name="taxonomical_notes_th" rows="2" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง"><?= htmlspecialchars($plant['taxonomical_notes_th'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกพฤกษศาสตร์พื้นบ้าน (Ethnobotanical Notes - English)</label>
                        <textarea name="ethnobotanical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($plant['ethnobotanical_notes'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 mb-1">บันทึกพฤกษศาสตร์พื้นบ้าน (Ethnobotanical Notes - คำแปลภาษาไทย)</label>
                        <textarea name="ethnobotanical_notes_th" rows="2" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง"><?= htmlspecialchars($plant['ethnobotanical_notes_th'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">บันทึกสรรพคุณทางยา (Medical Use Notes - English)</label>
                        <textarea name="medical_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($plant['medical_notes'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 mb-1">บันทึกสรรพคุณทางยา (Medical Use Notes - คำแปลภาษาไทย)</label>
                        <textarea name="medical_notes_th" rows="2" class="w-full px-3 py-2 border border-green-200 rounded-lg bg-green-50/20 text-sm" placeholder="แปลไทยอัตโนมัติเมื่อออนไลน์หากปล่อยว่าง"><?= htmlspecialchars($plant['medical_notes_th'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">🖼️ รูปภาพ (Herbarium Image)</h3>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gray-50 hover:bg-gray-100 transition">
                    <?php 
                    $display_img = '';
                    if (!empty($plant['thumbnail_path']) && file_exists(__DIR__ . '/' . $plant['thumbnail_path'])) {
                        $display_img = $plant['thumbnail_path'];
                    } elseif (!empty($plant['image_path'])) {
                        if (filter_var($plant['image_path'], FILTER_VALIDATE_URL)) {
                            $display_img = $plant['image_path'];
                        } elseif (file_exists(__DIR__ . '/' . $plant['image_path'])) {
                            $display_img = $plant['image_path'];
                        }
                    }
                    if ($display_img): 
                    ?>
                        <img src="<?= htmlspecialchars($display_img) ?>" onerror="this.onerror=null; this.src='images/no-image.jpg';" class="h-48 mx-auto mb-4 rounded shadow-md border border-gray-200 object-cover">
                    <?php endif; ?>
                    <label class="block text-sm font-bold mb-2">อัปเดตรูปภาพ (เว้นว่างถ้าใช้รูปเดิม)</label>
                    <input type="file" name="image" accept="image/*" class="mx-auto block text-xs sm:text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 cursor-pointer">
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-3">ภาพจะถูกบีบอัดเป็นสกุล .webp อัตโนมัติ (ช่วยประหยัดพื้นที่เซิร์ฟเวอร์)</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                <a href="dashboard.php" class="w-full sm:w-auto text-center bg-gray-300 text-gray-700 font-bold py-3 px-8 rounded-lg hover:bg-gray-400 transition shadow">ยกเลิก</a>
                <button type="submit" class="w-full sm:w-auto bg-blue-700 text-white font-bold py-3 px-10 rounded-lg hover:bg-blue-800 transition shadow-lg text-base sm:text-lg">💾 บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>