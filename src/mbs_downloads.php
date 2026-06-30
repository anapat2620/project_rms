<?php
session_start();
if (!isset($_SESSION['Email'])) {
    header("Location: login.php");
    exit();
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

$files = [];
$dir = 'mbs_files';
$full = __DIR__ . "/$dir";
if (is_dir($full)) {
    foreach (scandir($full) as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'doc', 'docx'])) {
            $files[] = [
                'name' => $f,
                'path' => $path,
                'size' => formatSize(filesize($full . "/" . $f)),
                'ext' => $ext,
            ];
        }
    }
}
?>
<?php $isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1'; ?>
<?php if (!$isEmbed): ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ดาวน์โหลดเอกสารทุนคณะ MBS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/mbs_downloads.css">
</head>
<body class="bg-base-200 min-h-screen">
<?php endif; ?>

    <div class="max-w-2xl mx-auto py-10">
        <div class="bg-base-100 rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-primary mb-2">ดาวน์โหลดเอกสารทุนคณะ MBS</h2>
            <p class="text-gray-500 mb-4">รวมไฟล์เอกสารที่เกี่ยวข้องกับทุนคณะ MBS สามารถดูหรือดาวน์โหลดได้ที่นี่</p>
            <div class="divider"></div>
            <?php if (count($files) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($files as $i => $file): ?>
                        <div class="flex items-center justify-between bg-base-200 rounded-lg px-4 py-3 shadow-sm hover:bg-primary/10 transition">
                            <div>
                                <span class="font-medium text-primary mr-2"><?= htmlspecialchars($file['name']) ?></span>
                                <span class="text-xs text-gray-500">(<?= htmlspecialchars($file['size']) ?>)</span>
                            </div>
                            <div class="flex gap-2">
                                <a href="<?= htmlspecialchars($file['path']) ?>" download class="btn btn-success btn-sm">ดาวน์โหลด</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">ไม่พบไฟล์สำหรับดาวน์โหลด</div>
            <?php endif; ?>
            <div class="mt-8 text-center">
                <a href="https://drive.google.com/drive/folders/1OdCK_H6MSSVFaga0OgAAdsgK6t1fbZ2q" target="_blank" rel="noopener noreferrer" class="btn btn-info mr-2">ดูเอกสารบน Google Drive</a>
            </div>
        </div>
    </div>
    <!-- PDF Modal -->
    <dialog id="pdfModal" class="modal">
      <div class="modal-box w-11/12 max-w-3xl bg-base-100">
        <h3 class="font-bold text-lg mb-2 text-primary">ดูเอกสาร PDF</h3>
        <iframe id="pdfFrame" src="" class="w-full h-[70vh] rounded border border-base-300" frameborder="0"></iframe>
        <div class="modal-action">
          <form method="dialog">
          </form>
        </div>
      </div>
    </dialog>
    <script src="assets/mbs_downloads.js"></script>
<?php if (!$isEmbed): ?>
</body>
</html>
<?php endif; ?>