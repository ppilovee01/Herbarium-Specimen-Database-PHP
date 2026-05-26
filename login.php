<?php
require_once 'db.php';
$error = '';
$success = '';

$active_form = 'login'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // ระบบเข้าสู่ระบบ
    if ($action === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; 
            
            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
            $active_form = 'login';
        }
    } 
    // ระบบสมัครสมาชิก
    elseif ($action === 'register') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "ชื่อผู้ใช้งานหรืออีเมลนี้ มีในระบบแล้ว";
            $active_form = 'register';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                $success = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                $active_form = 'login';
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
                $active_form = 'register';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบฐานข้อมูลพรรณไม้แห้ง</title>
    <script src="js/tailwindcss.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-md mb-4">
        <a href="index.php" class="inline-flex items-center gap-1 text-green-700 hover:text-green-900 font-bold text-sm sm:text-base transition">
            ← กลับไปหน้าแรก
        </a>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-green-900 px-6 py-6 sm:py-8 text-center text-white">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-wide mb-1">🌿 Herbarium</h1>
            <p class="text-green-200 text-xs sm:text-sm">ระบบฐานข้อมูลพรรณไม้แห้งออนไลน์</p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 mb-6 rounded text-sm font-medium shadow-sm">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-3 sm:p-4 mb-6 rounded text-sm font-medium shadow-sm">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div id="login-container" class="<?= $active_form === 'login' ? '' : 'hidden' ?> transition-all">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6 text-center">เข้าสู่ระบบ (Login)</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                        <input type="text" name="username" required class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-sm sm:text-base focus:ring-2 focus:ring-green-500 focus:border-green-500 transition font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">รหัสผ่าน (Password)</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-sm sm:text-base focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-green-700 text-white py-2.5 sm:py-3 px-4 rounded-lg font-bold text-sm sm:text-base hover:bg-green-800 transition shadow-md">เข้าสู่ระบบ</button>
                    </div>
                </form>
                <div class="mt-6 text-center text-xs sm:text-sm text-gray-600">
                    ยังไม่มีบัญชีใช่ไหม? <button type="button" onclick="toggleForm('register')" class="text-green-700 font-bold hover:underline focus:outline-none transition">สมัครสมาชิกใหม่</button>
                </div>
            </div>

            <div id="register-container" class="<?= $active_form === 'register' ? '' : 'hidden' ?> transition-all">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6 text-center">สมัครสมาชิก (Register)</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                        <input type="text" name="full_name" required class="w-full px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">อีเมล (Email)</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                        <input type="text" name="username" required class="w-full px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 font-mono transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">รหัสผ่าน (Password)</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-blue-700 text-white py-2.5 sm:py-3 px-4 rounded-lg font-bold text-sm sm:text-base hover:bg-blue-800 transition shadow-md">ยืนยันการสมัครสมาชิก</button>
                    </div>
                </form>
                <div class="mt-6 text-center text-xs sm:text-sm text-gray-600">
                    มีบัญชีอยู่แล้วใช่ไหม? <button type="button" onclick="toggleForm('login')" class="text-blue-700 font-bold hover:underline focus:outline-none transition">เข้าสู่ระบบเลย</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleForm(target) {
            const loginContainer = document.getElementById('login-container');
            const registerContainer = document.getElementById('register-container');
            
            // ซ่อนแจ้งเตือนเวลากดสลับหน้าต่าง
            document.querySelectorAll('.bg-red-50, .bg-green-50').forEach(box => box.style.display = 'none');

            if (target === 'register') {
                loginContainer.classList.add('hidden');
                registerContainer.classList.remove('hidden');
            } else {
                registerContainer.classList.add('hidden');
                loginContainer.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>