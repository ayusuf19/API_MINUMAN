<?php

declare(strict_types=1);

require_once __DIR__ . '/response.php';

try {
    require_once __DIR__ . '/database.php';
} catch (PDOException $error) {
    kirimResponse(500, 'Koneksi database gagal. Periksa layanan MySQL dan konfigurasi database.');
}

function prepareMinumanPayload(array $payload, string $method): array
{
    $allowed = ['nama', 'kategori', 'harga', 'stok', 'deskripsi'];
    foreach (array_keys($payload) as $key) {
        if (!in_array($key, $allowed, true)) {
            throw new InvalidArgumentException("Field '$key' tidak dikenal atau tidak dapat diubah.");
        }
    }

    $required = $method === 'PATCH' ? [] : ['nama', 'kategori', 'harga', 'stok'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $payload) || $payload[$field] === '') {
            throw new InvalidArgumentException("Field '$field' wajib diisi.");
        }
    }
    if ($method === 'PATCH' && $payload === []) {
        throw new InvalidArgumentException('PATCH harus memuat setidaknya satu field.');
    }

    $data = array_intersect_key($payload, array_flip($allowed));
    foreach (['harga', 'stok'] as $field) {
        if (array_key_exists($field, $data)) {
            $value = filter_var($data[$field], FILTER_VALIDATE_INT);
            if ($value === false || $value < ($field === 'harga' ? 1 : 0)) {
                throw new InvalidArgumentException("Field '$field' harus berupa bilangan bulat yang valid.");
            }
            $data[$field] = $value;
        }
    }
    foreach (['nama', 'kategori'] as $field) {
        if (isset($data[$field]) && trim((string)$data[$field]) === '') {
            throw new InvalidArgumentException("Field '$field' tidak boleh kosong.");
        }
    }
    if (array_key_exists('deskripsi', $data) && trim((string)$data['deskripsi']) === '') {
        $data['deskripsi'] = null;
    }
    return $data;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pathInfo = trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
if ($pathInfo === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $pathInfo = trim(substr($requestPath, strlen($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}
$segments = array_values(array_filter(explode('/', $pathInfo), static fn(string $segment): bool => $segment !== ''));
$entity = strtolower($segments[0] ?? '');
$id = isset($segments[1]) && ctype_digit($segments[1]) ? (int)$segments[1] : null;

if ($entity === '' && count($segments) === 0) {
    kirimResponse(200, 'API Menu Minuman Kafe siap digunakan.', ['docs' => './']);
}
if ($entity !== 'minuman') {
    kirimResponse(404, 'Endpoint tidak ditemukan. Gunakan /minuman.');
}
if (count($segments) > 2 || (isset($segments[1]) && $id === null)) {
    kirimResponse(400, 'Format endpoint tidak valid. ID harus berupa bilangan bulat.');
}

try {
    if ($method === 'GET') {
        $result = dbGetMinuman($id);
        if ($id !== null && $result === null) {
            kirimResponse(404, 'Data tidak ditemukan.');
        }
        kirimResponse(200, $id === null ? 'Daftar data berhasil diambil.' : 'Detail data berhasil diambil.', $result);
    }

    if ($method === 'POST') {
        if ($id !== null) {
            kirimResponse(405, 'POST hanya digunakan pada endpoint koleksi tanpa ID.');
        }
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            kirimResponse(400, 'Body harus berupa JSON object yang valid.');
        }
        $result = dbCreateMinuman(prepareMinumanPayload($payload, 'POST'));
        kirimResponse(201, 'Minuman berhasil ditambahkan ke menu.', $result);
    }

    if (in_array($method, ['PUT', 'PATCH'], true)) {
        if ($id === null) {
            kirimResponse(400, "$method memerlukan ID pada URL.");
        }
        $current = dbGetMinuman($id);
        if (!$current) {
            kirimResponse(404, 'Data tidak ditemukan.');
        }
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            kirimResponse(400, 'Body harus berupa JSON object yang valid.');
        }
        $data = prepareMinumanPayload($payload, $method);
        $result = dbUpdateMinuman($id, $data);
        kirimResponse(200, 'Data minuman berhasil diperbarui.', $result);
    }

    if ($method === 'DELETE') {
        if ($id === null) {
            kirimResponse(400, 'DELETE memerlukan ID pada URL.');
        }
        if (!dbDeleteMinuman($id)) {
            kirimResponse(404, 'Minuman tidak ditemukan.');
        }
        kirimResponse(200, 'Minuman berhasil dihapus dari menu.', ['id_minuman' => $id]);
    }

    header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    kirimResponse(405, "Method $method tidak diizinkan.");
} catch (InvalidArgumentException $error) {
    kirimResponse(400, $error->getMessage());
} catch (PDOException $error) {
    if ($error->getCode() === '23000') {
        kirimResponse(409, 'Nama minuman tersebut sudah terdaftar.');
    }
    kirimResponse(500, 'Terjadi kesalahan database. Periksa struktur tabel dan konfigurasi koneksi.');
} catch (Throwable $error) {
    kirimResponse(500, 'Terjadi kesalahan pada server.');
}
