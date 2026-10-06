<?php
/**
 * Kumpulan fungsi bantu yang dipakai di seluruh halaman.
 */

function formatRupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function formatTanggal($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function formatTanggalWaktu($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }
    return formatTanggal($tanggal) . ' ' . date('H:i', strtotime($tanggal));
}

function bersihkan(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function setPesan(string $tipe, string $pesan): void
{
    $_SESSION['pesan'] = ['tipe' => $tipe, 'teks' => $pesan];
}

function tampilkanPesan(): void
{
    if (!empty($_SESSION['pesan'])) {
        $tipe = $_SESSION['pesan']['tipe'] === 'sukses' ? 'success' : 'danger';
        $teks = htmlspecialchars($_SESSION['pesan']['teks'], ENT_QUOTES, 'UTF-8');
        echo '<div class="alert alert-' . $tipe . ' alert-dismissible fade show" role="alert">'
            . $teks
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
        unset($_SESSION['pesan']);
    }
}

/**
 * Membuat nomor dokumen berurutan dengan awalan tertentu, contoh: TRX-20260101-0001
 */
function buatNomor(PDO $pdo, string $tabel, string $kolom, string $awalan): string
{
    $tanggal = date('Ymd');
    $prefix = $awalan . '-' . $tanggal . '-';
    $stmt = $pdo->prepare(
        "SELECT $kolom FROM $tabel WHERE $kolom LIKE ? ORDER BY $kolom DESC LIMIT 1"
    );
    $stmt->execute([$prefix . '%']);
    $terakhir = $stmt->fetchColumn();

    if ($terakhir) {
        $urut = (int) substr($terakhir, -4) + 1;
    } else {
        $urut = 1;
    }

    return $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
}
