<?php
echo "<h1>PHP 7 Compatibility Check</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

function checkFile($file) {
    if (!file_exists($file)) {
        return;
    }

    $content = file_get_contents($file);
    $issues = [];

    // Check for PHP 8 specific syntax
    $patterns = [
        'nullsafe_operator' => [
            'pattern' => '/\?->/',
            'message' => 'Nullsafe operator (?->) is only available in PHP 8+'
        ],
        'named_arguments' => [
            'pattern' => '/\w+\s*:\s*(?!:)/',
            'message' => 'Possible named arguments usage (PHP 8+ feature)'
        ],
        'union_types' => [
            'pattern' => '/function\s+\w+\s*\([^)]*\)\s*:\s*\w+\|\w+/',
            'message' => 'Union types are only available in PHP 8+'
        ],
        'nullsafe_coalesce' => [
            'pattern' => '/\?\?=/',
            'message' => 'Nullsafe coalescing assignment (??=) is only available in PHP 8+'
        ],
        'match_expression' => [
            'pattern' => '/\bmatch\s*\(/',
            'message' => 'Match expression is only available in PHP 8+'
        ],
        'constructor_promotion' => [
            'pattern' => '/public|private|protected\s+\w+\s+\$\w+/',
            'message' => 'Possible constructor property promotion (PHP 8+ feature)'
        ]
    ];

    foreach ($patterns as $name => $check) {
        if (preg_match($check['pattern'], $content)) {
            $issues[] = $check['message'];
        }
    }

    if (!empty($issues)) {
        echo "<h3>File: " . basename($file) . "</h3>";
        foreach ($issues as $issue) {
            echo "<p class='warning'>⚠️ {$issue}</p>";
        }
    }
}

// Check required PHP version
echo "<h2>Current PHP Version</h2>";
echo "<p>Running PHP version: " . phpversion() . "</p>";
echo "<p>Target PHP version: 7.x</p>";

// Check required extensions
echo "<h2>Required Extensions Check</h2>";
$required_extensions = [
    'mysqli',
    'pdo',
    'pdo_mysql',
    'json',
    'gd',
    'mbstring'
];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ {$ext} extension is available</p>";
    } else {
        echo "<p class='error'>✗ {$ext} extension is missing</p>";
    }
}

// Scan PHP files for compatibility issues
echo "<h2>PHP 8 Features Check</h2>";
echo "<p>Scanning files for PHP 8 specific features that need to be adjusted...</p>";

function scanDirectory($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            checkFile($path);
        }
    }
}

// Start scanning from current directory
scanDirectory(__DIR__);

// Check MySQL version compatibility
echo "<h2>MySQL Version Check</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    $version = $db->query('SELECT VERSION() as version')->fetch(PDO::FETCH_ASSOC);
    echo "<p>MySQL Version: " . $version['version'] . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>Could not check MySQL version: " . $e->getMessage() . "</p>";
}

// Provide recommendations
echo "<h2>Recommendations</h2>";
echo "<ol>
    <li>Make sure PHP 7.x has all required extensions enabled in php.ini</li>
    <li>If you see any warnings about PHP 8 features above, those files need to be modified</li>
    <li>Test the application thoroughly after migration</li>
    <li>Keep a backup of both PHP 8 and PHP 7 compatible versions</li>
</ol>";
?>
