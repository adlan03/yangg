<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/family_service.php';

/* --- Ambil setting --- */
$setting = fetch_settings();
$harga  = setting_value($setting, 'harga');
$berasV = setting_value($setting, 'beras');
$jagungV = setting_value($setting, 'jagung');

$families = fetch_all_families();

// Mode publik atau admin
if (!empty($_SESSION['username'])) {
    $families = array_values(array_filter(
        $families,
        static fn($family) => strtolower($family['kepala']) === strtolower($_SESSION['username']) || $_SESSION['username'] === 'admin'
    ));
}

// Koleksi data untuk tabel dan ringkasan
$aggregate = ['uang' => 0.0, 'beras' => 0.0, 'jagung' => 0.0, 'infaq' => 0.0];
foreach ($families as $index => $family) {
    $memberCount = count($family['anggota']);
    $families[$index]['jumlah_anggota'] = $memberCount;
    $jmlU = 0;
    $jmlB = 0;
    $jmlJ = 0;
    foreach ($family['anggota'] as $member) {
        $jmlU += !empty($member['uang']) ? 1 : 0;
        $jmlB += !empty($member['beras']) ? 1 : 0;
        $jmlJ += !empty($member['jagung']) ? 1 : 0;
    }
    $families[$index]['jml_uang'] = $jmlU;
    $families[$index]['jml_beras'] = $jmlB;
    $families[$index]['jml_jagung'] = $jmlJ;
    $families[$index]['infaq'] = $family['infaq'] ?? 0;

    $aggregate['uang']   += $jmlU * $harga;
    $aggregate['beras']  += $jmlB * $berasV;
    $aggregate['jagung'] += $jmlJ * $jagungV;
    $aggregate['infaq']  += $families[$index]['infaq'];
}

$totalFamilies = count($families);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My Zakat</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/public.css">
</head>

<body>
    <nav class="navbar">
        <div class="container navbar-inner">
            <div class="brand"><span class="dot"></span> My Zakat</div>
            <div class="nav-links">
                <a href="#hero">Beranda</a>
                <a href="#stats">Statistik</a>
                <a href="#data">Data</a>
            </div>
            <?php if (!empty($_SESSION['username'])): ?>
                <a class="cta-nav" href="logout.php">Keluar</a>
            <?php else: ?>
                <a class="cta-nav" href="login.php">Masuk Admin</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero" id="hero">
        <div class="container hero-grid">
            <div>
                <div class="hero-badge">Zakat Lebih Transparant</div>
                <h1>SELAMAT DATANG DI PENCATATAN ZAKAT.</h1>
                <p>Ikuti perkembangan zakat keluarga dan perhatikan transparansi dari pembayaran Zakat di Desa Barugae</p>
                <div class="hero-actions">
                    <button class="btn-primary" onclick="document.getElementById('data').scrollIntoView({behavior:'smooth'});">Lihat Data</button>
                </div>
                <p style="margin-top:18px;color:#4c5b55;">Saat ini tercatat <strong><?= $totalFamilies; ?></strong> keluarga dengan pemantauan simulasi.</p>
            </div>
            <div class="hero-card">
                <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80" alt="Ilustrasi komunitas berzakat yang harmonis">
            </div>
        </div>
    </section>

    <section id="stats">
        <div class="container">
            <h2>Statistik Zakat Ringkas</h2>
            <p class="muted">Total real-time pencatatan zakat setiap penginputan otomatis terhitung.</p>
            <div class="stats-grid" aria-label="Ringkasan statistik zakat">
                <div class="stat-card">
                    <p class="stat-title">Total Uang</p>
                    <p class="stat-value">Rp <?= format_rupiah($aggregate['uang']); ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-title">Total Beras</p>
                    <p class="stat-value"><?= number_format($aggregate['beras'], 1); ?> kg</p>
                </div>
                <div class="stat-card">
                    <p class="stat-title">Total Jagung</p>
                    <p class="stat-value"><?= number_format($aggregate['jagung'], 1); ?> kg</p>
                </div>
                <div class="stat-card">
                    <p class="stat-title">Total Infaq</p>
                    <p class="stat-value">Rp <?= format_rupiah($aggregate['infaq']); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section id="data">
        <div class="container">
            <div class="card table-card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h2 style="margin:0;">Data Infaq &amp; Zakat</h2>
                        <p class="muted" style="margin:6px 0 0;">Tabel data simulasi tanpa database.</p>
                    </div>
                    <?php if (!empty($_SESSION['username'])): ?>
                        <div style="color:#4c5b55;">Login sebagai <strong><?= htmlspecialchars($_SESSION['username']); ?></strong></div>
                    <?php endif; ?>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Kepala Keluarga</th>
                                <th>Jumlah Anggota</th>
                                <th>Uang (Rp)</th>
                                <th>Beras (kg)</th>
                                <th>Jagung (kg)</th>
                                <th>Infaq (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($families as $family): ?>
                                <?php
                                $uangRp   = ($family['jml_uang'] ?? 0) * $harga;
                                $berasKg  = ($family['jml_beras'] ?? 0) * $berasV;
                                $jagungKg = ($family['jml_jagung'] ?? 0) * $jagungV;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($family['kepala']); ?></strong><br>
                                        <small>(+ <?= max(0, (int)$family['jumlah_anggota'] - 1); ?> anggota)</small>
                                    </td>
                                    <td><?= (int)$family['jumlah_anggota']; ?></td>
                                    <td><?= format_rupiah((float)$uangRp); ?></td>
                                    <td><?= number_format((float)$berasKg, 1); ?></td>
                                    <td><?= number_format((float)$jagungKg, 1); ?></td>
                                    <td><?= format_rupiah((float)($family['infaq'] ?? 0)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container footer-grid">
            <div class="footer-brand">My Zakat</div>
            <div>
                <strong></strong>
                <ul class="breakpoints">
                    <li>Desa Barugae Dusun Waepejje </li>
                </ul>
            </div>
            <div>
                <strong>Kontak sekertaris</strong>
                <p style="margin:6px 0 0; color:#c0d5cd;">Email: adlankhalid10@gmail.com. <br> WA: 081245434516</p>
            </div>
        </div>
    </footer>

</body>

</html>
