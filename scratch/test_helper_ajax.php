<?php
declare(strict_types=1);

$endpoint = $argv[1] ?? '';
$param = $argv[2] ?? '';

if ($endpoint === 'selector') {
    $_GET['option_id'] = (int)$param;
    include __DIR__ . '/../ajax/selector_products.php';
} elseif ($endpoint === 'search') {
    $_GET['q'] = $param;
    include __DIR__ . '/../ajax/search.php';
}
