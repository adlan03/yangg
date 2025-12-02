<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Kumpulan fungsi layanan untuk operasi keluarga dan pengaturan (simulasi tanpa database).
 */

function ensure_family_store(): void
{
    if (!isset($_SESSION['families']) || !is_array($_SESSION['families'])) {
        // Dummy data awal
        $_SESSION['families'] = [
            [
                'id' => 1,
                'kepala' => 'Kepala Keluarga 1',
                'infaq' => INFAQ_VALUE,
                'anggota' => [
                    ['nama' => 'Anggota 1', 'jk' => 'L', 'uang' => 1, 'beras' => 0, 'jagung' => 0],
                    ['nama' => 'Anggota 2', 'jk' => 'P', 'uang' => 0, 'beras' => 1, 'jagung' => 0],
                ],
            ],
        ];
        $_SESSION['family_seq'] = 2;
    }

    if (!isset($_SESSION['family_seq'])) {
        $_SESSION['family_seq'] = count($_SESSION['families']) + 1;
    }
}

function is_settings_locked(): bool
{
    $settings = fetch_settings();
    return !empty($settings['locked']);
}

function update_settings(int $harga, float $beras, float $jagung): void
{
    store_settings([
        'harga' => $harga,
        'beras' => $beras,
        'jagung' => $jagung,
    ]);
}

function set_setting_lock(bool $locked): void
{
    $settings = fetch_settings();
    $settings['locked'] = $locked ? 1 : 0;
    store_settings($settings);
}

function collect_members_from_post(array $post): array
{
    $members = [];
    if (empty($post['nama']) || !is_array($post['nama'])) {
        return $members;
    }

    foreach ($post['nama'] as $i => $nama) {
        $nama = trim((string)$nama);
        if ($nama === '') {
            continue;
        }

        $members[] = [
            'nama'   => $nama,
            'jk'     => $post['jk'][$i] ?? '',
            'uang'   => isset($post['uang'][$i]) ? 1 : 0,
            'beras'  => isset($post['beras'][$i]) ? 1 : 0,
            'jagung' => isset($post['jagung'][$i]) ? 1 : 0,
        ];
    }

    return $members;
}

function save_family(string $kepala, int $infaq, array $members): void
{
    ensure_family_store();
    $id = $_SESSION['family_seq']++;
    $_SESSION['families'][] = [
        'id' => $id,
        'kepala' => $kepala,
        'infaq' => $infaq,
        'anggota' => $members,
    ];
}

function fetch_all_families(): array
{
    ensure_family_store();
    return $_SESSION['families'];
}

function delete_family(int $familyId): void
{
    ensure_family_store();
    $_SESSION['families'] = array_values(array_filter(
        $_SESSION['families'],
        static fn($family) => (int)$family['id'] !== $familyId
    ));
}

function reset_all_families(): void
{
    $_SESSION['families'] = [];
    $_SESSION['family_seq'] = 1;
}

function replace_family(int $familyId, int $infaq, array $members): void
{
    ensure_family_store();
    foreach ($_SESSION['families'] as &$family) {
        if ((int)$family['id'] === $familyId) {
            $family['infaq'] = $infaq;
            $family['anggota'] = $members;
            break;
        }
    }
}

function calculate_family_totals(array $family, array $setting): array
{
    $totals = [
        'uang' => 0.0,
        'beras' => 0.0,
        'jagung' => 0.0,
        'infaq' => (int)($family['infaq'] ?? 0),
    ];

    foreach ($family['anggota'] as $member) {
        if (!empty($member['uang'])) {
            $totals['uang'] += setting_value($setting, 'harga');
        }
        if (!empty($member['beras'])) {
            $totals['beras'] += setting_value($setting, 'beras');
        }
        if (!empty($member['jagung'])) {
            $totals['jagung'] += setting_value($setting, 'jagung');
        }
    }

    return $totals;
}

function calculate_overall_totals(array $families, array $setting): array
{
    $overall = ['uang' => 0.0, 'beras' => 0.0, 'jagung' => 0.0, 'infaq' => 0];
    foreach ($families as $family) {
        $familyTotals = calculate_family_totals($family, $setting);
        $overall['uang'] += $familyTotals['uang'];
        $overall['beras'] += $familyTotals['beras'];
        $overall['jagung'] += $familyTotals['jagung'];
        $overall['infaq'] += $familyTotals['infaq'];
    }

    return $overall;
}
