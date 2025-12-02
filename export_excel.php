<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/family_service.php';

require_login();

$type = $_GET['type'] ?? 'summary';
$setting = fetch_settings();
$harga  = setting_value($setting, 'harga');
$berasV = setting_value($setting, 'beras');
$jagungV = setting_value($setting, 'jagung');
$families = fetch_all_families();

$filename = "export_{$type}_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo "\xEF\xBB\xBF"; // BOM untuk UTF-8

// =====================
// CSS LANGSUNG DI HTML
// =====================
echo <<<CSS
<style>
table {
  border-collapse: collapse;
  width: 100%;
  font-family: Arial, sans-serif;
}
th {
  background: #fdd835;
  color: #176b41;
  font-weight: bold;
  border: 1px solid #999;
  padding: 8px;
  text-align: center;
}
td {
  border: 1px solid #999;
  padding: 8px;
  text-align: center;
}
tr:nth-child(even) { background: #f5f5f5; }
tr:hover { background: #e8f5e9; }
h2 {
  color: #176b41;
}
</style>
CSS;

// =====================
// MODE RINGKAS
// =====================
if ($type === 'summary') {
    echo "<h2>Data Ringkas Infaq & Zakat</h2>";
    echo "<table><thead><tr>
        <th>No</th><th>Kepala Keluarga</th><th>Jumlah Anggota</th>
        <th>Uang (Rp)</th><th>Beras (kg)</th><th>Jagung (kg)</th><th>Infaq (Rp)</th>
    </tr></thead><tbody>";

    $no = 1;
    $grandUang = 0;
    $grandBeras = 0;
    $grandJagung = 0;
    $grandInfaq = 0;
    foreach ($families as $family) {
        $memberCount = count($family['anggota']);
        $jmlUang = 0;
        $jmlBeras = 0;
        $jmlJagung = 0;
        foreach ($family['anggota'] as $member) {
            $jmlUang += !empty($member['uang']) ? 1 : 0;
            $jmlBeras += !empty($member['beras']) ? 1 : 0;
            $jmlJagung += !empty($member['jagung']) ? 1 : 0;
        }

        $uangRp = $jmlUang * $harga;
        $beras = $jmlBeras * $berasV;
        $jagung = $jmlJagung * $jagungV;
        $infaq = $family['infaq'] ?? 0;
        echo "<tr>
            <td>{$no}</td>
            <td>{$family['kepala']}</td>
            <td>{$memberCount}</td>
            <td>" . format_rupiah((float)$uangRp) . "</td>
            <td>{$beras}</td>
            <td>{$jagung}</td>
            <td>" . format_rupiah((float)$infaq) . "</td>
        </tr>";
        $grandUang += $uangRp;
        $grandBeras += $beras;
        $grandJagung += $jagung;
        $grandInfaq += $infaq;
        $no++;
    }

    echo "<tr style='font-weight:bold;background:#fff59d;'>
        <td colspan='3'>TOTAL</td>
        <td>" . format_rupiah((float)$grandUang) . "</td>
        <td>{$grandBeras}</td>
        <td>{$grandJagung}</td>
        <td>" . format_rupiah((float)$grandInfaq) . "</td>
    </tr>";

    echo "</tbody></table>";
    exit;
}

// ====== MODE DETAIL (tanpa kolom ID, dengan total akhir) ======
if ($type === 'detail') {
    echo "<h2>Data Detail Anggota</h2>";
    echo "<table><thead><tr>
        <th>No</th>
        <th>Kepala</th>
        <th>Nama Anggota</th>
        <th>JK</th>
        <th>Pilihan</th>
        <th>Uang (Rp)</th>
        <th>Beras (kg)</th>
        <th>Jagung (kg)</th>
        <th>Infaq (Rp)</th>
    </tr></thead><tbody>";

    $no = 1;
    $lastFamily = null;

    // variabel total keseluruhan
    $totalUang = 0;
    $totalBeras = 0;
    $totalJagung = 0;
    $totalInfaq = 0;

    foreach ($families as $family) {
        foreach ($family['anggota'] as $member) {
            $infaqOut = '';
            if ($lastFamily !== $family['id']) {
                $infaqOut = (int)($family['infaq'] ?? 0);
                $totalInfaq += $infaqOut;
                $lastFamily = $family['id'];
            }

            $pilihan = [];
            $uangRp = 0;
            $berasKg = 0;
            $jagungKg = 0;

            if (!empty($member['uang'])) {
                $pilihan[] = 'Uang';
                $uangRp = $harga;
                $totalUang += $uangRp;
            }
            if (!empty($member['beras'])) {
                $pilihan[] = 'Beras';
                $berasKg = $berasV;
                $totalBeras += $berasKg;
            }
            if (!empty($member['jagung'])) {
                $pilihan[] = 'Jagung';
                $jagungKg = $jagungV;
                $totalJagung += $jagungKg;
            }

            echo "<tr>
                <td>{$no}</td>
                <td>{$family['kepala']}</td>
                <td>{$member['nama']}</td>
                <td>{$member['jk']}</td>
                <td>" . implode('+', $pilihan) . "</td>
                <td>" . format_rupiah((float)$uangRp) . "</td>
                <td>{$berasKg}</td>
                <td>{$jagungKg}</td>
                <td>" . ($infaqOut ? format_rupiah((float)$infaqOut) : '') . "</td>
            </tr>";
            $no++;
        }
    }

    // tambahkan baris total keseluruhan
    echo "<tr style='font-weight:bold; background:#fff59d;'>
        <td colspan='5' style='text-align:right;'>TOTAL KESELURUHAN</td>
        <td>Rp " . format_rupiah((float)$totalUang) . "</td>
        <td>{$totalBeras}</td>
        <td>{$totalJagung}</td>
        <td>Rp " . format_rupiah((float)$totalInfaq) . "</td>
    </tr>";

    echo "</tbody></table>";
    exit;
}

// Jika tipe tidak dikenali
// Fitur ini dinonaktifkan pada versi tanpa database
exit('Tipe export tidak dikenali.');
