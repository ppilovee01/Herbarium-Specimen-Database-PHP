<?php
require_once 'db.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM herbariums WHERE id = ?");
$stmt->execute([$id]);
$plant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plant) { echo "<script>alert('ไม่พบข้อมูล'); window.location.href='index.php';</script>"; exit; }
require_once 'header.php';

// ดึงรูปภาพ โดยเลือกใช้ Local file/Thumbnail ก่อน ถ้าไม่มีให้เลือกใช้ URL ภาพดั้งเดิม
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
?>

<div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-90 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <button onclick="closeModal()" class="absolute top-4 right-4 sm:top-8 sm:right-8 text-white hover:text-gray-300 bg-gray-800 rounded-full p-2"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
    <img id="modalImage" src="" class="max-w-full max-h-full object-contain border-2 border-gray-700 rounded shadow-2xl">
</div>

<div class="max-w-7xl mx-auto my-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Herbarium Sheet Detail</h1>
        <a href="index.php" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 bg-blue-50 px-4 py-2 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg> Return to Search</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">
            <div class="lg:col-span-5 bg-gray-100 p-6 flex flex-col items-center justify-start border-b lg:border-b-0 lg:border-r border-gray-200">
                <?php if($display_img): ?>
                    <div class="relative group cursor-zoom-in" onclick="openModal('<?= htmlspecialchars($display_img) ?>')">
                        <img src="<?= htmlspecialchars($display_img) ?>" onerror="this.onerror=null; this.src='images/no-image.jpg';" class="max-w-full h-auto rounded shadow-md border border-gray-300 group-hover:opacity-90 transition">
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black bg-opacity-20 rounded"><span class="bg-black bg-opacity-70 text-white px-4 py-2 rounded-full font-bold flex gap-2 items-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg> คลิกเพื่อขยายภาพ</span></div>
                    </div>
                    <a href="<?= htmlspecialchars($display_img) ?>" download="<?= htmlspecialchars($plant['specimen_id'] ?? 'herbarium_image') ?>" class="mt-6 w-full text-center bg-gray-800 hover:bg-black text-white font-bold py-2.5 px-4 rounded transition shadow" target="_blank">↓ Download Image</a>
                <?php else: ?>
                    <div class="w-full aspect-[3/4] bg-gray-200 rounded flex items-center justify-center text-gray-500 shadow-inner">ไม่มีภาพพรรณไม้แห้ง</div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-7 p-8 sm:p-10 text-sm">
                <h2 class="text-3xl font-bold text-green-900 mb-6 italic border-b-4 border-green-800 pb-2 inline-block"><?= htmlspecialchars($plant['scientific_name']) ?></h2>

                <div class="space-y-2 text-gray-700">
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Specimen ID:</span> <span class="font-mono font-bold text-blue-700 text-base"><?= htmlspecialchars($plant['specimen_id'] ?? '-') ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Herbarium Name:</span> <span><?= htmlspecialchars($plant['herbarium_name'] ?? 'NTBG') ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Barcode:</span> <span class="font-mono"><?= htmlspecialchars($plant['barcode'] ?: '-') ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Plant Category:</span> <span class="uppercase font-bold text-green-800 bg-green-50 px-2 py-0.5 rounded"><?= htmlspecialchars($plant['plant_category'] ?? '') ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Family:</span> <span class="uppercase"><?= htmlspecialchars($plant['family_name'] ?? '') ?: '-' ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2 mb-6"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Genus:</span> <span class="italic"><?= htmlspecialchars($plant['genus'] ?? '') ?: '-' ?></span></p>

                    <div class="pt-4"></div>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Collector Name:</span> <span><?= htmlspecialchars($plant['collector_name'] ?? '') ?: '-' ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Collection Date:</span> <span><?= $plant['collection_date'] ? date('F d, Y', strtotime($plant['collection_date'])) : '-' ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Country:</span> <span><?= htmlspecialchars($plant['country'] ?? '') ?: '-' ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Island (เกาะ):</span> <span><?= htmlspecialchars($plant['island'] ?? '-') ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Elevation (m):</span> <span><?= htmlspecialchars($plant['elevation'] ?? '') ?: '-' ?></span></p>
                    <p class="flex flex-col sm:flex-row border-b border-gray-100 pb-2 mb-6"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0 mb-1 sm:mb-0">Locality:</span> <span class="leading-relaxed"><?= nl2br(htmlspecialchars($plant['locality'] ?? '')) ?: '-' ?></span></p>
                    
                    <div class="pt-4"></div>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Habit:</span> <span><?= htmlspecialchars($plant['habit'] ?? '') ?: '-' ?></span></p>
                    <p class="flex border-b border-gray-100 pb-2"><span class="font-bold text-gray-900 w-40 sm:w-48 shrink-0">Habitat:</span> <span><?= htmlspecialchars($plant['habitat'] ?? '') ?: '-' ?></span></p>
                    <div class="border-b border-gray-100 pb-2 mb-2"><span class="font-bold text-gray-900 block mb-1">Plant Description:</span> <p class="bg-gray-50 p-3 rounded text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($plant['description'] ?? '')) ?: '-' ?></p></div>
                    
                    <?php if($plant['associated_species']): ?><div class="border-b border-gray-100 pb-2 mb-2"><span class="font-bold text-gray-900 block mb-1">Associated Species:</span> <p class="text-gray-600"><?= nl2br(htmlspecialchars($plant['associated_species'] ?? '')) ?></p></div><?php endif; ?>
                    <?php if($plant['taxonomical_notes']): ?><div class="border-b border-gray-100 pb-2 mb-2"><span class="font-bold text-gray-900 block mb-1">Taxonomical Notes:</span> <p class="text-gray-600"><?= nl2br(htmlspecialchars($plant['taxonomical_notes'] ?? '')) ?></p></div><?php endif; ?>
                    <?php if($plant['ethnobotanical_notes']): ?><div class="border-b border-gray-100 pb-2 mb-2"><span class="font-bold text-gray-900 block mb-1">Ethnobotanical Notes:</span> <p class="bg-yellow-50 p-3 rounded text-gray-700"><?= nl2br(htmlspecialchars($plant['ethnobotanical_notes'] ?? '')) ?></p></div><?php endif; ?>
                    <?php if($plant['medical_notes']): ?><div class="border-b border-gray-100 pb-2 mb-2"><span class="font-bold text-gray-900 block mb-1">Medicinal Use Notes:</span> <p class="bg-blue-50 p-3 rounded text-blue-800"><?= nl2br(htmlspecialchars($plant['medical_notes'] ?? '')) ?></p></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function openModal(imgSrc) { document.getElementById('modalImage').src = imgSrc; document.getElementById('imageModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal() { document.getElementById('imageModal').classList.add('hidden'); document.body.style.overflow = 'auto'; }
    document.getElementById('imageModal').addEventListener('click', function(e) { if(e.target === this) closeModal(); });
</script>
<?php require_once 'footer.php'; ?>