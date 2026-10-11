<?php
// Lists constants the portal uses but never defines (PHP 8 stops on them with an error). Runs in
// the portal's container, or anywhere its settings are in the environment.
// Usage: php undefined-constants.php [portal root, default /app]
$app = rtrim($argv[1] ?? '/app', '/');
require $app . '/src/bootstrap.php';
$defined = array_change_key_case(get_defined_constants(), CASE_UPPER) + ['TRUE' => 1, 'FALSE' => 1, 'NULL' => 1];

$skip = [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION, T_CONST, T_CLASS, T_INSTANCEOF, T_EXTENDS, T_NAMESPACE, T_USE];
$missing = [];
foreach ([$app . '/src', $app . '/public', $app . '/profiles', $app . '/profile'] as $root) {
    if (!is_dir($root)) {
        continue;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($files as $file) {
        $path = $file->getPathname();
        if (substr($path, -4) !== '.php') {
            continue;
        }
        $tokens = token_get_all(file_get_contents($path));
        foreach ($tokens as $i => $t) {
            if (!is_array($t) || $t[0] !== T_STRING || !preg_match('/^[A-Z][A-Z0-9_]+$/', $t[1])) {
                continue;
            }
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], $skip, true)) {
                continue;
            }
            $next = $i + 1;
            while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                $next++;
            }
            if (isset($tokens[$next]) && $tokens[$next] === '(') {
                continue;
            }
            if (!array_key_exists($t[1], $defined)) {
                $missing[$t[1]][] = $path . ':' . $t[2];
            }
        }
    }
}
ksort($missing);
foreach ($missing as $name => $where) {
    echo $name . '  ' . implode(' ', array_unique($where)) . PHP_EOL;
}
echo count($missing) . " undefined\n";
exit($missing ? 1 : 0);
