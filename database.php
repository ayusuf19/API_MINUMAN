<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'cafe_menu';
const DB_USER = 'root';
const DB_PASS = '';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

function dbGetMinuman(?int $id = null): array|null
{
    global $pdo;

    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM minuman WHERE id_minuman = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    return $pdo->query('SELECT * FROM minuman ORDER BY kategori ASC, nama ASC')->fetchAll();
}

function dbCreateMinuman(array $data): array
{
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO minuman (nama, kategori, harga, stok, deskripsi) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['nama'],
        $data['kategori'],
        $data['harga'],
        $data['stok'],
        $data['deskripsi'] ?? null,
    ]);

    return dbGetMinuman((int)$pdo->lastInsertId());
}

function dbUpdateMinuman(int $id, array $data): array|null
{
    global $pdo;
    $current = dbGetMinuman($id);
    if (!$current) {
        return null;
    }

    $fields = ['nama', 'kategori', 'harga', 'stok', 'deskripsi'];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = array_key_exists($field, $data) ? $data[$field] : $current[$field];
    }
    $stmt = $pdo->prepare(
        'UPDATE minuman SET nama = ?, kategori = ?, harga = ?, stok = ?, deskripsi = ? WHERE id_minuman = ?'
    );
    $stmt->execute([...array_values($values), $id]);

    return dbGetMinuman($id);
}

function dbDeleteMinuman(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM minuman WHERE id_minuman = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}
