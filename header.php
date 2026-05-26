<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบฐานข้อมูลพรรณไม้แห้ง</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <nav class="bg-green-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-lg sm:text-xl font-bold flex items-center gap-2">
                        🌿 <span class="inline">ฐานข้อมูลพรรณไม้แห้ง</span>
                    </a>
                </div>
                
                <div class="hidden lg:flex items-center gap-4 text-sm">
                    <a href="index.php" class="hover:text-green-300 transition-colors">หน้าแรก</a>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <div class="flex items-center gap-4 border-l border-green-700 pl-4 ml-2">
                                <a href="dashboard.php" class="hover:text-green-300 text-yellow-300 font-bold transition-colors">⚙️ จัดการพรรณไม้</a>
                                <a href="users.php" class="hover:text-green-300 text-yellow-300 font-bold transition-colors">👥 จัดการผู้ใช้</a>
                            </div>
                        <?php endif; ?>
                        
                        <span class="border-l border-green-700 pl-4 ml-2 text-green-200">
                            👤 <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?> 
                        </span>
                        <a href="logout.php" class="bg-red-700 px-3 py-1.5 rounded-lg hover:bg-red-800 transition-colors font-semibold ml-2">ออก</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-white text-green-900 px-4 py-2 rounded-lg font-bold hover:bg-green-100 transition-colors shadow-sm ml-2">เข้าสู่ระบบ</a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center lg:hidden">
                    <button id="mobileMenuBtn" class="text-white hover:text-green-300 focus:outline-none p-2 rounded-lg bg-green-800/50">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path id="menuIcon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden bg-green-950 border-t border-green-800 transition-all duration-300">
            <div class="px-4 pt-3 pb-4 space-y-2 text-base">
                <a href="index.php" class="block py-2 hover:bg-green-800 px-3 rounded transition-colors">หน้าแรก</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <div class="border-t border-green-800 pt-2 my-2 space-y-1">
                            <div class="px-3 text-xs text-yellow-400 font-bold uppercase tracking-wider mb-1">เมนูผู้ดูแลระบบ</div>
                            <a href="dashboard.php" class="block py-2 text-yellow-300 hover:bg-green-800 px-3 rounded transition-colors">⚙️ จัดการพรรณไม้</a>
                            <a href="users.php" class="block py-2 text-yellow-300 hover:bg-green-800 px-3 rounded transition-colors">👥 จัดการผู้ใช้</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-green-800 pt-2 flex flex-col gap-2">
                        <span class="px-3 py-1 text-sm text-green-300">
                            👤 <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                        </span>
                        <a href="logout.php" class="block text-center bg-red-700 py-2 rounded-lg hover:bg-red-800 transition-colors font-semibold">ออกจากระบบ</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="block text-center bg-white text-green-900 py-2 rounded-lg font-bold hover:bg-green-100 transition-colors shadow-sm">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                menuIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
            } else {
                menuIcon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
        });
    </script>
    <main class="flex-grow w-full">