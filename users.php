<?php
require_once 'db.php';

// อนุญาตเฉพาะ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('ปฏิเสธการเข้าถึง! เฉพาะผู้ดูแลระบบเท่านั้น'); window.location.href='index.php';</script>"; exit;
}

// ระบบลบผู้ใช้งาน (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('ไม่สามารถลบบัญชีของตัวเองที่กำลังใช้งานอยู่ได้!');</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: users.php"); exit;
    }
}

// ระบบค้นหา & แบ่งหน้า (Pagination)
$search = trim($_GET['search'] ?? '');
$limit = 15;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$whereSQL = ""; $params = [];
if ($search !== '') {
    $whereSQL = "WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ?";
    $searchParam = "%$search%"; $params = [$searchParam, $searchParam, $searchParam];
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
$stmtCount->execute($params);
$total_records = $stmtCount->fetchColumn();
$total_pages = ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page - 1) * $limit; }

$sql = "SELECT id, username, email, full_name, role, created_at FROM users $whereSQL ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="max-w-7xl mx-auto my-4 sm:my-8 px-4 sm:px-6 lg:px-8">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">👥 จัดการผู้ใช้งานระบบ</h2>
            <p class="text-gray-500 text-xs sm:text-sm mt-1">ผู้ใช้ทั้งหมด: <span class="font-bold text-blue-600"><?= number_format($total_records) ?></span> บัญชี</p>
        </div>
        <div class="flex flex-col sm:flex-row w-full md:w-auto gap-2">
            <a href="dashboard.php" class="w-full sm:w-auto text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2.5 rounded-lg font-bold shadow-sm transition text-sm">
                ← กลับหน้าพรรณไม้
            </a>
            <a href="create_user.php" class="w-full sm:w-auto text-center bg-green-700 hover:bg-green-800 text-white px-5 py-2.5 rounded-lg font-bold shadow-sm transition text-sm flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                เพิ่มผู้ใช้ใหม่
            </a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row w-full gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหา Username, ชื่อ, อีเมล..." class="w-full sm:w-96 px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition">
            <div class="flex gap-2">
                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-sm transition text-sm">ค้นหา</button>
                <?php if($search): ?>
                    <a href="users.php" class="w-full sm:w-auto bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-bold flex items-center justify-center transition text-sm border border-gray-200">ล้างค่า</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto">
        <table class="min-w-[700px] w-full text-sm text-left">
            <thead class="bg-gray-800 text-white border-b border-gray-700">
                <tr>
                    <th class="py-3.5 px-4 text-center w-16 uppercase tracking-wider text-xs font-bold">ID</th>
                    <th class="py-3.5 px-4 uppercase tracking-wider text-xs font-bold">ข้อมูลบัญชี (Account)</th>
                    <th class="py-3.5 px-4 uppercase tracking-wider text-xs font-bold">อีเมล (Email)</th>
                    <th class="py-3.5 px-4 text-center uppercase tracking-wider text-xs font-bold">สิทธิ์ (Role)</th>
                    <th class="py-3.5 px-4 text-center w-40 uppercase tracking-wider text-xs font-bold">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if(empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-16 text-gray-500 bg-gray-50">📭 ไม่พบข้อมูลบัญชีผู้ใช้ในระบบ</td></tr>
                <?php endif; ?>
                
                <?php foreach($users as $u): ?>
                    <tr class="hover:bg-blue-50/50 transition-colors">
                        <td class="py-3.5 px-4 text-center text-gray-400 font-bold"><?= $u['id'] ?></td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-gray-900 block truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($u['full_name']) ?></span>
                            <span class="text-xs text-gray-500 font-mono mt-0.5 block">@<?= htmlspecialchars($u['username']) ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-gray-600 truncate max-w-[150px] sm:max-w-xs"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="py-3.5 px-4 text-center">
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wide border border-purple-200">Admin</span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wide border border-gray-200">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex justify-center gap-1.5">
                                <a href="edit_user.php?id=<?= $u['id'] ?>" class="text-white bg-amber-500 hover:bg-amber-600 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">แก้ไข</a>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบบัญชีผู้ใช้นี้อย่างถาวร?');">
                                        <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">ลบ</button>
                                    </form>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed" title="ไม่สามารถลบบัญชีตัวเองได้">ล็อค</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        
        <div class="text-sm text-gray-600 font-medium text-center md:text-left">
            หน้า <span class="font-bold text-blue-600 text-lg"><?= $page ?></span> / <span class="font-bold text-gray-900"><?= $total_pages ?></span>
        </div>

        <form method="GET" class="flex items-center gap-2 w-full justify-center md:w-auto">
            <?php if($search): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <label class="text-sm text-gray-600 font-medium whitespace-nowrap">ไปที่หน้า :</label>
            <input type="number" name="page" min="1" max="<?= $total_pages ?>" value="<?= $page ?>" class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-bold">
            <button type="submit" class="px-4 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-sm font-bold rounded-lg transition shadow-sm">ไป</button>
        </form>

        <div class="flex gap-2 w-full justify-center md:w-auto">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 font-bold transition text-sm shadow-sm w-full text-center md:w-auto">← ก่อนหน้า</a>
            <?php else: ?>
                <span class="px-4 py-2 border border-gray-200 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed font-bold text-sm w-full text-center md:w-auto">← ก่อนหน้า</span>
            <?php endif; ?>

            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 font-bold transition text-sm shadow-sm w-full text-center md:w-auto">ถัดไป →</a>
            <?php else: ?>
                <span class="px-4 py-2 border border-gray-200 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed font-bold text-sm w-full text-center md:w-auto">ถัดไป →</span>
            <?php endif; ?>
        </div>
        
    </div>
    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>