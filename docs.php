<?php

declare(strict_types=1);

$schemas = [
    'MinumanInput' => [
        'type' => 'object',
        'required' => ['nama', 'kategori', 'harga', 'stok'],
        'properties' => [
            'nama' => ['type' => 'string', 'example' => 'Cafe Latte'],
            'kategori' => ['type' => 'string', 'example' => 'Kopi'],
            'harga' => ['type' => 'integer', 'minimum' => 1, 'example' => 28000],
            'stok' => ['type' => 'integer', 'minimum' => 0, 'example' => 16],
            'deskripsi' => ['type' => 'string', 'nullable' => true, 'example' => 'Espresso dengan susu steamed.'],
        ],
    ],
    'MinumanPatch' => [
        'type' => 'object',
        'properties' => [
            'nama' => ['type' => 'string'],
            'kategori' => ['type' => 'string'],
            'harga' => ['type' => 'integer', 'minimum' => 1],
            'stok' => ['type' => 'integer', 'minimum' => 0],
            'deskripsi' => ['type' => 'string', 'nullable' => true],
        ],
    ],
];

$paths = [
    '/minuman' => [
        'get' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Melihat seluruh menu minuman',
            'responses' => ['200' => ['description' => 'Daftar minuman berhasil diambil.']],
        ],
        'post' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Menambahkan minuman ke menu',
            'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MinumanInput']]]],
            'responses' => ['201' => ['description' => 'Minuman berhasil dibuat.'], '409' => ['description' => 'Nama minuman sudah terdaftar.']],
        ],
    ],
    '/minuman/{id}' => [
        'parameters' => [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer', 'minimum' => 1],
        ]],
        'get' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Melihat detail minuman',
            'responses' => ['200' => ['description' => 'Minuman ditemukan.'], '404' => ['description' => 'Minuman tidak ditemukan.']],
        ],
        'put' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Mengganti seluruh data minuman',
            'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MinumanInput']]]],
            'responses' => ['200' => ['description' => 'Minuman berhasil diperbarui.'], '400' => ['description' => 'Input tidak valid.']],
        ],
        'patch' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Memperbarui sebagian data minuman',
            'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MinumanPatch']]]],
            'responses' => ['200' => ['description' => 'Minuman berhasil diperbarui.'], '400' => ['description' => 'Input tidak valid.']],
        ],
        'delete' => [
            'tags' => ['Menu Minuman'],
            'summary' => 'Menghapus minuman dari menu',
            'responses' => ['200' => ['description' => 'Minuman berhasil dihapus.'], '404' => ['description' => 'Minuman tidak ditemukan.']],
        ],
    ],
];

return [
    'openapi' => '3.0.3',
    'info' => [
        'title' => 'API Menu Minuman Kafe',
        'description' => 'REST API untuk melihat dan mengelola daftar minuman, harga, kategori, deskripsi, dan stok.',
        'version' => '1.0.0',
    ],
    'servers' => [['url' => 'api.php', 'description' => 'API Menu Minuman Kafe']],
    'tags' => [['name' => 'Menu Minuman']],
    'paths' => $paths,
    'components' => ['schemas' => $schemas],
];
