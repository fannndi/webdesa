<?php
define('DESA_NAMA',       'Desa Sukamaju');
define('DESA_KECAMATAN',  'Kecamatan Cikaret');
define('DESA_KABUPATEN',  'Kabupaten Bogor');
define('DESA_PROVINSI',   'Jawa Barat');
define('KEPALA_DESA',     'H. Suparman, S.Sos');
$document_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
$project_dir = str_replace('\\', '/', dirname(__DIR__));
$base_path = str_replace($document_root, '', $project_dir);
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . $base_path . '/');
