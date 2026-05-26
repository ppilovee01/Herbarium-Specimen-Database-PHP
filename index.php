<?php
require_once 'db.php';
require_once 'header.php';

$category = $_GET['category'] ?? '';
$barcode = trim($_GET['barcode'] ?? '');
$scientific = trim($_GET['scientific'] ?? '');
$family_filter = $_GET['family'] ?? '';
$genus_filter = $_GET['genus'] ?? '';
$country_filter = $_GET['country'] ?? ''; // เพิ่มตัวกรองประเทศ
$island_filter = $_GET['island'] ?? ''; 
$collector = trim($_GET['collector'] ?? '');

$is_search_active = (!empty($category) || !empty($barcode) || !empty($scientific) || !empty($family_filter) || !empty($genus_filter) || !empty($country_filter) || !empty($island_filter) || !empty($collector));

if (!$is_search_active): 
?>
    <div class="max-w-6xl mx-auto my-6 sm:my-12 px-4">
        <div class="text-center mb-8 sm:mb-16">
            <h1 class="text-2xl sm:text-4xl font-extrabold text-green-900 mb-3 tracking-wide">Herbarium Specimen Database</h1>
            <p class="text-gray-600 text-sm sm:text-lg font-light uppercase tracking-wider">ระบบสืบค้นฐานข้อมูลตัวอย่างพรรณไม้แห้งออนไลน์</p>
            <div class="w-24 h-1 bg-yellow-500 mx-auto mt-4 rounded-full"></div>
        </div>

        <div class="max-w-3xl mx-auto bg-white p-4 sm:p-6 rounded-2xl shadow-md border border-gray-100 mb-8 sm:mb-16">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="scientific" placeholder="ค้นหาด่วนด้วยชื่อวิทยาศาสตร์ (Scientific Name)..." class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 bg-gray-50 text-sm sm:text-base">
                <button type="submit" class="bg-green-800 hover:bg-green-900 text-white font-bold px-6 py-2.5 rounded-xl shadow transition text-sm sm:text-base whitespace-nowrap flex items-center justify-center gap-2">🔍 ค้นหาด่วน</button>
            </form>
            <div class="text-center mt-4"><a href="?category=all" class="text-xs sm:text-sm text-green-700 font-bold hover:underline">หรือคลิกที่นี่เพื่อเปิดแผงค้นหาข้อมูลเชิงลึก (Advanced Search)</a></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
            <?php 
            $categories = [
                ['id' => 'dicot', 'name' => 'พืชใบเลี้ยงคู่', 'en' => 'Dicotyledons', 'img' => 'images/dicot.jpg'],
                ['id' => 'monocot', 'name' => 'พืชใบเลี้ยงเดี่ยว', 'en' => 'Monocotyledons', 'img' => 'images/monocot.jpg'],
                ['id' => 'pteridophyte', 'name' => 'เฟิร์นและพืชญาติเฟิร์น', 'en' => 'Pteridophytes', 'img' => 'images/fern.jpg'],
                ['id' => 'all', 'name' => 'พรรณไม้ทั้งหมด', 'en' => 'All Specimens', 'img' => 'images/all.jpg']
            ];
            foreach ($categories as $cat): 
            ?>
            <a href="?category=<?= urlencode($cat['id']) ?>" class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col h-full">
                <div class="h-44 sm:h-52 overflow-hidden bg-gray-100 relative border-b border-gray-100">
                    <img src="<?= $cat['img'] ?>" onerror="this.onerror=null; this.src='images/no-image.jpg';" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                </div>
                <div class="p-4 sm:p-6 text-center flex-grow flex flex-col justify-center">
                    <h3 class="font-bold text-gray-800 text-base sm:text-lg group-hover:text-green-800 transition-colors"><?= $cat['name'] ?></h3>
                    <p class="text-[10px] sm:text-xs text-gray-400 mt-1 font-mono tracking-wide">/ <?= $cat['en'] ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php 
else: 
    // เพิ่มการดึงข้อมูลรายชื่อประเทศแบบ Dynamic 
    $fam_stmt = $pdo->query("SELECT DISTINCT family_name FROM herbariums WHERE family_name IS NOT NULL AND family_name != '' ORDER BY family_name ASC");
    $gen_stmt = $pdo->query("SELECT DISTINCT genus FROM herbariums WHERE genus IS NOT NULL AND genus != '' ORDER BY genus ASC");
    $country_stmt = $pdo->query("SELECT DISTINCT country FROM herbariums WHERE country IS NOT NULL AND country != '' ORDER BY country ASC");
    $isl_stmt = $pdo->query("SELECT DISTINCT island FROM herbariums WHERE island IS NOT NULL AND island != '' ORDER BY island ASC");
    
    $families = $fam_stmt->fetchAll(PDO::FETCH_COLUMN);
    $genera = $gen_stmt->fetchAll(PDO::FETCH_COLUMN);
    $countries = $country_stmt->fetchAll(PDO::FETCH_COLUMN);
    $islands = $isl_stmt->fetchAll(PDO::FETCH_COLUMN);

    $conditions = []; $params = [];
    if ($category && $category !== 'all') { $conditions[] = "plant_category = ?"; $params[] = $category; }
    if ($barcode) { $conditions[] = "(barcode LIKE ? OR specimen_id LIKE ?)"; $params[] = "%$barcode%"; $params[] = "%$barcode%"; }
    if ($scientific) { $conditions[] = "scientific_name LIKE ?"; $params[] = "%$scientific%"; }
    if ($family_filter) { $conditions[] = "family_name = ?"; $params[] = $family_filter; }
    if ($genus_filter) { $conditions[] = "genus = ?"; $params[] = $genus_filter; }
    if ($country_filter) { $conditions[] = "country = ?"; $params[] = $country_filter; } // เพิ่มเงื่อนไขค้นหาประเทศ
    if ($island_filter) { $conditions[] = "island = ?"; $params[] = $island_filter; }
    if ($collector) { $conditions[] = "collector_name LIKE ?"; $params[] = "%$collector%"; }

    $sql = "SELECT * FROM herbariums";
    if (!empty($conditions)) { $sql .= " WHERE " . implode(" AND ", $conditions); }
    $sql .= " ORDER BY scientific_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cat_display_name = 'ทุกหมวดหมู่ (All Specimens)';
    if ($category === 'dicot') $cat_display_name = 'พืชใบเลี้ยงคู่ (Dicotyledons)';
    if ($category === 'monocot') $cat_display_name = 'พืชใบเลี้ยงเดี่ยว (Monocotyledons)';
    if ($category === 'pteridophyte') $cat_display_name = 'เฟิร์นและพืชญาติเฟิร์น (Pteridophytes)';
?>
    <div class="max-w-7xl mx-auto my-4 sm:my-8 px-4">
        
        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gray-100 px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">Advanced Specimen Search</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">หมวดหมู่: <span class="font-bold text-green-800"><?= $cat_display_name ?></span></p>
                </div>
                <a href="index.php" class="text-xs sm:text-sm font-bold text-green-700 hover:text-green-900 border border-green-300 bg-white px-3 py-1.5 rounded-lg flex items-center justify-center w-full sm:w-auto shadow-sm">
                    ← กลับหน้าหลัก
                </a>
            </div>

            <form method="GET" class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5 items-end">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category ?: 'all') ?>">
                
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Specimen ID / Barcode</label>
                    <input type="text" name="barcode" value="<?= htmlspecialchars($barcode) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                </div>
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Scientific Name</label>
                    <input type="text" name="scientific" value="<?= htmlspecialchars($scientific) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 italic">
                </div>
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Family (วงศ์)</label>
                    <select name="family" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 bg-white uppercase">
                        <option value="">-- ทั้งหมด (ALL) --</option>
                        <?php foreach($families as $fam): ?>
                            <option value="<?= htmlspecialchars($fam) ?>" <?= $family_filter === $fam ? 'selected' : '' ?>><?= htmlspecialchars($fam) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Genus (สกุล)</label>
                    <select name="genus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 bg-white italic">
                        <option value="">-- ทั้งหมด (ALL) --</option>
                        <?php foreach($genera as $gen): ?>
                            <option value="<?= htmlspecialchars($gen) ?>" <?= $genus_filter === $gen ? 'selected' : '' ?>><?= htmlspecialchars($gen) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Country (ประเทศ)</label>
                    <select name="country" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- ทั้งหมด (ALL) --</option>
                        <?php foreach($countries as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $country_filter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Island (เกาะ/พื้นที่)</label>
                    <select name="island" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- ทั้งหมด (ALL) --</option>
                        <?php foreach($islands as $isl): ?>
                            <option value="<?= htmlspecialchars($isl) ?>" <?= $island_filter === $isl ? 'selected' : '' ?>><?= htmlspecialchars($isl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Collector (ผู้เก็บ)</label>
                    <input type="text" name="collector" value="<?= htmlspecialchars($collector) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="w-full flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm shadow flex items-center justify-center">🔎 กรองข้อมูล</button>
                    <a href="?category=<?= htmlspecialchars($category ?: 'all') ?>" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm text-center flex items-center justify-center">ล้างค่า (Reset)</a>
                </div>
            </form>
        </div>
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-gray-800 text-sm sm:text-base">พบตัวอย่างตรงเงื่อนไข: <span class="text-blue-700 font-mono text-lg"><?= count($plants) ?></span> รายการ</h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[700px]">
                <thead>
                    <tr class="bg-gray-800 text-white font-bold border-b border-gray-700 uppercase tracking-wider text-[11px] sm:text-xs">
                        <th class="py-3.5 px-4 text-center w-14">No.</th>
                        <th class="py-3.5 px-4 w-32">Specimen ID</th>
                        <th class="py-3.5 px-4">Scientific Name</th>
                        <th class="py-3.5 px-4 w-40">Country / Island</th>
                        <th class="py-3.5 px-4 w-40">Collector</th>
                        <th class="py-3.5 px-4 text-center w-20">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(empty($plants)): ?>
                        <tr><td colspan="6" class="text-center py-16 text-gray-500 font-medium bg-gray-50">📭 ไม่พบตัวอย่างพรรณไม้</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($plants as $index => $plant): ?>
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="py-3 px-4 text-center text-gray-400 font-medium"><?= $index + 1 ?></td>
                            <td class="py-3 px-4 font-mono font-bold text-blue-700 text-xs sm:text-sm whitespace-nowrap"><?= htmlspecialchars($plant['specimen_id'] ?: $plant['barcode'] ?: '-') ?></td>
                            <td class="py-3 px-4 text-gray-900 font-bold italic text-sm sm:text-base"><?= htmlspecialchars($plant['scientific_name']) ?></td>
                            <td class="py-3 px-4 text-gray-600 font-medium whitespace-nowrap">
                                <span class="block text-gray-800 font-bold"><?= htmlspecialchars($plant['country'] ?? '-') ?></span>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($plant['island'] ?? '-') ?></span>
                            </td>
                            <td class="py-3 px-4 text-gray-600 font-medium truncate max-w-[150px]"><?= htmlspecialchars($plant['collector_name'] ?? '') ?: '-' ?></td>
                            <td class="py-3 px-4 text-center"><a href="detail.php?id=<?= $plant['id'] ?>" class="inline-flex items-center justify-center bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm w-full">ข้อมูล</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>