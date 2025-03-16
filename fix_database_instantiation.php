<?php

function fixDatabaseInstantiation($directory) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getRealPath());
            if (strpos($content, 'new Database()') !== false) {
                $content = str_replace(
                    '$database = Database::getInstance();',
                    '$database = Database::getInstance();',
                    $content
                );
                file_put_contents($file->getRealPath(), $content);
                echo "Fixed file: " . $file->getRealPath() . "\n";
            }
        }
    }
}

// Fix all PHP files in the project
fixDatabaseInstantiation(__DIR__);
echo "Database instantiation fix completed!\n";
