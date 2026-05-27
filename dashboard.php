<?php
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะแอดมิน'); window.location.href='index.php';</script>"; exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("SELECT image_path, thumbnail_path FROM herbariums WHERE id = ?");
    $stmt->execute([$id]);
    $plant = $stmt->fetch();
    if ($plant) {
        if (!empty($plant['thumbnail_path']) && file_exists(__DIR__ . '/' . $plant['thumbnail_path'])) { unlink(__DIR__ . '/' . $plant['thumbnail_path']); }
        if (!empty($plant['image_path']) && !filter_var($plant['image_path'], FILTER_VALIDATE_URL) && file_exists(__DIR__ . '/' . $plant['image_path'])) { unlink(__DIR__ . '/' . $plant['image_path']); }
    }
    $stmt = $pdo->prepare("DELETE FROM herbariums WHERE id = ?"); $stmt->execute([$id]);
    header("Location: dashboard.php"); exit;
}

$search = trim($_GET['search'] ?? '');
$limit = 20; 
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$whereSQL = ""; $params = [];
if ($search !== '') {
    $whereSQL = "WHERE barcode LIKE ? OR specimen_id LIKE ? OR scientific_name LIKE ? OR common_name_th LIKE ?";
    $searchParam = "%$search%"; $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM herbariums $whereSQL");
$stmtCount->execute($params);
$total_records = $stmtCount->fetchColumn();
$total_pages = ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page - 1) * $limit; }

$sql = "SELECT * FROM herbariums $whereSQL ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบพรรณไม้ที่ยังไม่ได้แปลภาษาไทย (ช่วงออฟไลน์)
$stmtPending = $pdo->query("SELECT COUNT(*) FROM herbariums 
    WHERE (description IS NOT NULL AND description != '' AND (description_th IS NULL OR description_th = ''))
       OR (habitat IS NOT NULL AND habitat != '' AND (habitat_th IS NULL OR habitat_th = ''))");
$pending_count = $stmtPending->fetchColumn();
$is_online = ($pending_count > 0) ? is_online() : false;

require_once 'header.php';
?>

<div class="max-w-7xl mx-auto my-4 sm:my-8 px-4 sm:px-6 lg:px-8">
    <?php if(isset($_GET['sync_success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-xl shadow-sm text-sm font-medium">
            ✅ ซิงค์คำแปลภาษาไทยสำเร็จเรียบร้อยแล้วจำนวน <span class="font-bold"><?= (int)$_GET['sync_success'] ?></span> รายการ!
        </div>
    <?php endif; ?>

    <?php if($pending_count > 0 && $is_online): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                <span class="text-xl">🔄</span>
                <div>
                    <p class="text-blue-800 font-bold text-sm sm:text-base">ตรวจพบพรรณไม้ที่ยังไม่ได้แปลภาษาไทย (บันทึกช่วงออฟไลน์)</p>
                    <p class="text-blue-600 text-xs mt-0.5">พบทั้งหมด <span class="font-bold text-blue-800"><?= $pending_count ?></span> รายการที่กรอกไว้ตอนออฟไลน์ สามารถกดซิงค์แปลภาษาไทยเมื่อเชื่อมต่อเน็ตแล้ว</p>
                </div>
            </div>
            <a href="sync_translations.php" class="w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg text-xs sm:text-sm shadow transition">⚡ เริ่มซิงค์คำแปล</a>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">⚙️ จัดการข้อมูลพรรณไม้แห้ง</h2>
            <p class="text-gray-500 text-xs sm:text-sm mt-0.5">ในระบบมีทั้งหมด: <span class="font-bold text-blue-600"><?= number_format($total_records) ?></span> รายการ</p>
        </div>
        <a href="create.php" class="w-full sm:w-auto text-center bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg font-bold shadow transition text-sm">+ เพิ่มข้อมูลใหม่</a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหารหัส, ชื่อวิทย์..." class="w-full sm:w-80 px-4 py-2 border rounded-lg text-sm focus:outline-none">
            <div class="flex gap-2">
                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-bold text-sm">ค้นหา</button>
                <?php if($search): ?><a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-bold text-sm flex items-center justify-center">ล้างค่า</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto">
        <table class="min-w-[800px] w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr class="bg-gray-800 text-white text-xs font-bold uppercase"><td class="py-3 px-4 text-center w-20">รูปภาพ</td><td class="py-3 px-4">Specimen ID</td><td class="py-3 px-4">Scientific Name</td><td class="py-3 px-4">Category</td><td class="py-3 px-4">Locality</td><td class="py-3 px-4 text-center w-40">Action</td></tr>
                <?php foreach($plants as $plant): ?>
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="py-2 px-4 text-center">
                            <?php $display_img = $plant['thumbnail_path'] ?: $plant['image_path'] ?: ''; ?>
                            <img src="<?= htmlspecialchars($display_img) ?>" onerror="this.onerror=null; this.src='images/no-image.jpg';" class="w-10 h-10 object-cover rounded mx-auto border">
                        </td>
                        <td class="py-2 px-4 font-mono font-bold text-blue-700"><?= htmlspecialchars($plant['specimen_id'] ?: $plant['barcode'] ?: '-') ?></td>
                        <td class="py-2 px-4 font-bold italic"><?= htmlspecialchars($plant['scientific_name']) ?></td>
                        <td class="py-2 px-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-700 uppercase"><?= htmlspecialchars($plant['plant_category'] ?? '') ?></span></td>
                        <td class="py-2 px-4 text-xs text-gray-500"><?= htmlspecialchars($plant['country'] ?? '') ?> (<?= htmlspecialchars($plant['island'] ?? '-') ?>)</td>
                        <td class="py-2 px-4 text-center">
                            <div class="flex justify-center gap-1">
                                <a href="edit.php?id=<?= $plant['id'] ?>" class="bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1 rounded text-xs font-bold shadow-sm">แก้ไข</a>
                                <form method="POST" class="inline" onsubmit="return confirm('ลบถาวร?');"><input type="hidden" name="delete_id" value="<?= $plant['id'] ?>"><button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2.5 py-1 rounded text-xs font-bold shadow-sm">ลบ</button></form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4 bg-white p-4 rounded-xl border">
        <div class="text-xs sm:text-sm text-gray-600">หน้า <span class="font-bold text-blue-600"><?= $page ?></span> / <?= $total_pages ?></div>
        <form method="GET" class="flex items-center gap-1.5">
            <?php if($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <span class="text-xs sm:text-sm text-gray-600">ไปหน้า:</span>
            <input type="number" name="page" min="1" max="<?= $total_pages ?>" value="<?= $page ?>" class="w-16 px-2 py-1 border rounded text-center text-xs sm:text-sm font-bold">
            <button type="submit" class="px-3 py-1 bg-gray-800 text-white text-xs sm:text-sm font-bold rounded">ไป</button>
        </form>
        <div class="flex gap-2">
            <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 border rounded text-xs font-bold bg-white">← ถอย</a>
            <a href="?page=<?= min($total_pages, $page+1) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 border rounded text-xs font-bold bg-white">ถัด →</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>