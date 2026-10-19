<?php
/**
 * Script Uji Kelayakan Hosting Abasthan untuk Proyek Laravel Baca Buku.
 * File mandiri (tanpa framework/dependency).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$extensions_needed = [
    'pdo' => 'Wajib untuk koneksi database',
    'pdo_pgsql' => 'Wajib untuk koneksi PostgreSQL (Supabase)',
    'openssl' => 'Wajib untuk keamanan HTTPS & OAuth Google',
    'mbstring' => 'Wajib untuk manipulasi teks Laravel',
    'tokenizer' => 'Wajib untuk parsing kode Laravel',
    'xml' => 'Wajib untuk parsing dokumen & DOCX',
    'ctype' => 'Wajib untuk tipe data Laravel',
    'json' => 'Wajib untuk data JSON',
    'bcmath' => 'Wajib untuk perhitungan presisi',
    'fileinfo' => 'Wajib untuk verifikasi tipe file upload (MIME)',
    'gd' => 'Penting untuk pemrosesan/resize gambar avatar',
    'zip' => 'Penting untuk membaca file DOCX chapter',
    'curl' => 'Wajib untuk panggilan API Google Drive & HTTP request',
];

$db_test_result = null;
$smtp_test_result = null;

// Uji koneksi DB jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_db') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_port = trim($_POST['db_port'] ?? '6543');
    $db_name = trim($_POST['db_name'] ?? 'postgres');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');

    if (!extension_loaded('pdo_pgsql')) {
        $db_test_result = ['status' => 'error', 'message' => 'Ekstensi pdo_pgsql TIDAK aktif di server ini!'];
    } elseif (empty($db_host) || empty($db_user)) {
        $db_test_result = ['status' => 'error', 'message' => 'Host dan User database wajib diisi.'];
    } else {
        try {
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require";
            $start = microtime(true);
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $time = round((microtime(true) - $start) * 1000, 2);
            $stmt = $pdo->query('SELECT version()');
            $ver = $stmt->fetchColumn();
            $db_test_result = [
                'status' => 'success',
                'message' => "BERHASIL terhubung ke Supabase! Waktu respon: {$time} ms. Versi DB: {$ver}",
            ];
        } catch (Exception $e) {
            $db_test_result = [
                'status' => 'error',
                'message' => "GAGAL terhubung: " . $e->getMessage(),
            ];
        }
    }
}

// Uji koneksi SMTP Brevo jika tombol diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_smtp') {
    $smtp_host = 'smtp-relay.brevo.com';
    $ports = [587, 465, 25];
    $results = [];

    foreach ($ports as $port) {
        $prefix = ($port === 465) ? 'ssl://' : '';
        $fp = @fsockopen($prefix . $smtp_host, $port, $errno, $errstr, 5);
        if ($fp) {
            $response = fgets($fp, 512);
            fclose($fp);
            $results[] = "<span style='color:green'>✔ Port {$port}: Terbuka</span> (Respon: " . htmlspecialchars(trim($response)) . ")";
        } else {
            $results[] = "<span style='color:red'>✖ Port {$port}: Tertutup / Diblokir</span> ({$errno}: {$errstr})";
        }
    }
    $smtp_test_result = implode('<br>', $results);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Kelayakan Hosting Abasthan</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.5; background: #f4f6f8; color: #333; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; color: #1a202c; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; font-size: 24px; }
        h2 { color: #2d3748; margin-top: 25px; margin-bottom: 10px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f7fafc; color: #4a5568; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-danger { background: #fed7d7; color: #742a2a; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #def7ec; color: #03543f; border: 1px solid #84e1bc; }
        .alert-error { background: #fde8e8; color: #9b1c1c; border: 1px solid #f8b4b4; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #4a5568; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px; box-sizing: border-box; }
        button { background: #3182ce; color: #fff; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #2b6cb0; }
        .row { display: flex; gap: 10px; }
        .col { flex: 1; }
        .note { font-size: 12px; color: #718096; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Hasil Uji Kelayakan Hosting (Abasthan)</h1>

    <!-- 1. Informasi PHP & Server -->
    <h2>1. Lingkungan PHP & Server</h2>
    <table>
        <tr>
            <th>Parameter</th>
            <th>Nilai Server</th>
            <th>Keterangan</th>
        </tr>
        <tr>
            <td>Versi PHP</td>
            <td><strong><?= PHP_VERSION ?></strong></td>
            <td><?= version_compare(PHP_VERSION, '8.2.0', '>=') ? '<span class="badge badge-success">OK (Laravel 11/12 Cocok)</span>' : '<span class="badge badge-danger">Harap upgrade minimal PHP 8.2</span>' ?></td>
        </tr>
        <tr>
            <td>Web Server Software</td>
            <td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Tidak terdeteksi') ?></td>
            <td>-</td>
        </tr>
        <tr>
            <td>Memory Limit</td>
            <td><?= ini_get('memory_limit') ?></td>
            <td>Minimal disarankan 128M / 256M</td>
        </tr>
        <tr>
            <td>Upload Max Filesize</td>
            <td><?= ini_get('upload_max_filesize') ?></td>
            <td>Batas maksimal upload per file (PDF/Cover/DOCX)</td>
        </tr>
        <tr>
            <td>Post Max Size</td>
            <td><?= ini_get('post_max_size') ?></td>
            <td>Batas form POST (harus >= upload_max_filesize)</td>
        </tr>
        <tr>
            <td>Max Execution Time</td>
            <td><?= ini_get('max_execution_time') ?> detik</td>
            <td>Waktu timeout proses PHP</td>
        </tr>
    </table>

    <!-- 2. Status Ekstensi PHP -->
    <h2>2. Ekstensi PHP yang Dibutuhkan</h2>
    <table>
        <tr>
            <th>Nama Ekstensi</th>
            <th>Status</th>
            <th>Kegunaan di Website</th>
        </tr>
        <?php foreach ($extensions_needed as $ext => $desc): ?>
            <?php $loaded = extension_loaded($ext); ?>
            <tr>
                <td><strong><?= htmlspecialchars($ext) ?></strong></td>
                <td>
                    <?php if ($loaded): ?>
                        <span class="badge badge-success">Tersedia</span>
                    <?php else: ?>
                        <span class="badge badge-danger">TIDAK ADA</span>
                    <?php endif; ?>
                </td>
                <td style="color: #4a5568;"><?= htmlspecialchars($desc) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- 3. Uji Port Email Brevo -->
    <h2>3. Uji Koneksi Port Email (Brevo)</h2>
    <p style="font-size: 13px; color: #4a5568;">Menguji apakah server Abasthan memblokir port keluar (outbound port) untuk pengiriman email transaksi.</p>
    <?php if ($smtp_test_result): ?>
        <div class="alert alert-success" style="line-height: 1.8;">
            <?= $smtp_test_result ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="action" value="test_smtp">
        <button type="submit">Uji Koneksi Port Brevo (SMTP)</button>
    </form>

    <!-- 4. Uji Koneksi Supabase -->
    <h2>4. Uji Koneksi Database Supabase (PostgreSQL)</h2>
    <p style="font-size: 13px; color: #4a5568;">
        Masukkan kredensial Supabase Anda untuk memastikan server Abasthan dapat menjangkau pooler Supabase (port 6543 / 5432).
        <em>(Data ini tidak disimpan ke disk mana pun).</em>
    </p>

    <?php if ($db_test_result): ?>
        <div class="alert <?= $db_test_result['status'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($db_test_result['message']) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="test_db">
        <div class="row">
            <div class="col form-group">
                <label>DB Host</label>
                <input type="text" name="db_host" placeholder="aws-0-ap-southeast-1.pooler.supabase.com" value="<?= htmlspecialchars($_POST['db_host'] ?? '') ?>" required>
            </div>
            <div class="form-group" style="width: 100px;">
                <label>Port</label>
                <input type="text" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '6543') ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'postgres') ?>" required>
            </div>
            <div class="col form-group">
                <label>User</label>
                <input type="text" name="db_user" placeholder="postgres.proyek_id" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="db_pass" placeholder="Password database Supabase" required>
        </div>
        <button type="submit">Uji Koneksi Supabase Sekarang</button>
    </form>
</div>
</body>
</html>
