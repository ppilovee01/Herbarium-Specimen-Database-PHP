<?php
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

    // เช็คว่า Username หรือ Email ซ้ำไหม
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $error = "❌ Username หรือ Email นี้มีผู้ใช้งานแล้ว!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed_password, $email, $full_name, $role])) {
            echo "<script>alert('เพิ่มผู้ใช้งานสำเร็จ!'); window.location.href='users.php';</script>"; exit;
        }
    }
}
require_once 'header.php';
?>
<div class="max-w-3xl mx-auto my-12 px-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-green-800 px-8 py-6 text-white"><h2 class="text-2xl font-bold">➕ เพิ่มบัญชีผู้ใช้ใหม่</h2></div>
        
        <form method="POST" class="p-8 space-y-5 bg-gray-50">
            <?php if($error): ?><div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><?= $error ?></div><?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-bold text-gray-700 mb-1">ชื่อ-นามสกุล (Full Name)</label><input type="text" name="full_name" required class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">อีเมล (Email)</label><input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">ชื่อผู้ใช้ (Username)</label><input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg font-mono"></div>
                <div><label class="block text-sm font-bold text-gray-700 mb-1">รหัสผ่าน (Password)</label><input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg"></div>
                <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">ระดับสิทธิ์ผู้ใช้งาน (Role)</label>
                    <select name="role" class="w-full px-4 py-2 border rounded-lg bg-white">
                        <option value="user">ผู้ใช้งานทั่วไป (User - ค้นหาและดูข้อมูลเท่านั้น)</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin - จัดการพรรณไม้และบัญชีได้)</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-4 pt-6"><a href="users.php" class="bg-gray-300 text-gray-700 font-bold py-2.5 px-6 rounded-lg">ยกเลิก</a><button type="submit" class="bg-green-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md">บันทึกข้อมูล</button></div>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>