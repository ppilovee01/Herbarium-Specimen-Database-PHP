<?php
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header("Location: users.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $password = $_POST['password'];

    // ป้องกันแอดมินเปลี่ยนสิทธิ์ตัวเองเป็น User (เดี๋ยวเข้าหลังบ้านไม่ได้)
    if ($id == $_SESSION['user_id'] && $role == 'user') {
        $error = "❌ คุณไม่สามารถปลดสิทธิ์ Admin ของตัวเองได้!";
    } else {
        // เช็คว่า Username/Email ไปซ้ำกับไอดีคนอื่นไหม
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "❌ Username หรือ Email นี้มีผู้ใช้งานอื่นใช้อยู่แล้ว!";
        } else {
            if (!empty($password)) {
                // เปลี่ยนรหัสผ่านด้วย
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?");
                $stmt->execute([$username, $email, $full_name, $role, $hashed_password, $id]);
            } else {
                // อัปเดตแค่ข้อมูล ไม่เปลี่ยนรหัสผ่าน
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $full_name, $role, $id]);
            }
            echo "<script>alert('อัปเดตข้อมูลบัญชีสำเร็จ!'); window.location.href='users.php';</script>"; exit;
        }
    }
}
require_once 'header.php';
?>
<div class="max-w-3xl mx-auto my-12 px-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-amber-600 px-8 py-6 text-white"><h2 class="text-2xl font-bold">✏️ แก้ไขบัญชีผู้ใช้ (ID: <?= $user['id'] ?>)</h2></div>
        
        <form method="POST" class="p-8 space-y-5 bg-gray-50">
            <?php if($error): ?><div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><?= $error ?></div><?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-bold text-gray-700 mb-1">ชื่อ-นามสกุล</label><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">อีเมล</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">ชื่อผู้ใช้ (Username)</label><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="w-full px-4 py-2 border rounded-lg font-mono"></div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">รีเซ็ตรหัสผ่านใหม่</label>
                    <input type="text" name="password" placeholder="เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน" class="w-full px-4 py-2 border border-amber-300 rounded-lg bg-amber-50 placeholder-amber-400">
                </div>
                <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">ระดับสิทธิ์ (Role)</label>
                    <select name="role" class="w-full px-4 py-2 border rounded-lg bg-white">
                        <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>ผู้ใช้งานทั่วไป (User)</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-4 pt-6"><a href="users.php" class="bg-gray-300 text-gray-700 font-bold py-2.5 px-6 rounded-lg">ยกเลิก</a><button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md">บันทึกการแก้ไข</button></div>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>