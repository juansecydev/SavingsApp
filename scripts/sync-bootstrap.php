<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die('This script must be run from the command line.');
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $command = sprintf('cmd /c if exist "%s" rmdir /s /q "%s"', $path, $path);
        $output = [];
        $status = 0;

        exec($command, $output, $status);

        if ($status !== 0 || is_dir($path)) {
            fail('Failed to remove directory via Windows shell: ' . $path);
        }

        return;
    }

    $entries = scandir($path);
    if ($entries === false) {
        fail('Failed to read directory: ' . $path);
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $entry;

        if (is_link($fullPath)) {
            if (!@unlink($fullPath)) {
                fail('Failed to remove symlink: ' . $fullPath);
            }
            continue;
        }

        if (is_dir($fullPath)) {
            removeDirectory($fullPath);
            continue;
        }

        if (!@unlink($fullPath)) {
            fail('Failed to remove file: ' . $fullPath);
        }
    }

    if (!@rmdir($path)) {
        fail('Failed to remove directory: ' . $path);
    }
}

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    fail('Project root not found.');
}

$bootstrapSource = realpath(__DIR__ . '/../vendor/twbs/bootstrap/dist');
$iconsSource = realpath(__DIR__ . '/../vendor/twbs/bootstrap-icons/font');
$target = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'bootstrap';

if ($bootstrapSource === false || !is_dir($bootstrapSource)) {
    fail("Bootstrap source directory not found: {$projectRoot}/vendor/twbs/bootstrap/dist");
}

if ($iconsSource === false || !is_dir($iconsSource)) {
    fail("Bootstrap Icons source directory not found: {$projectRoot}/vendor/twbs/bootstrap-icons/font");
}

foreach ([$bootstrapSource, $iconsSource] as $source) {
    if (!str_starts_with($source, $projectRoot . DIRECTORY_SEPARATOR)) {
        fail('Asset source path is outside the project root.');
    }
}

if (is_link($target) || is_link(dirname($target))) {
    fail('Refusing to operate on symlinked directories.');
}

if (is_dir($target)) {
    removeDirectory($target);
}

if (!is_dir($target) && !@mkdir($target, 0755, true) && !is_dir($target)) {
    fail('Failed to create target directory: ' . $target);
}

$sources = [
    $bootstrapSource => $target,
    $iconsSource => $target . DIRECTORY_SEPARATOR . 'icons',
];

foreach ($sources as $source => $destination) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = $iterator->getSubPathName();
        $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isLink()) {
            fail('Refusing to copy a symlinked asset.');
        }

        if ($item->isDir()) {
            if (!is_dir($targetPath) && !@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                fail('Failed to create directory: ' . $targetPath);
            }
            continue;
        }

        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir) && !@mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
            fail('Failed to create parent directory: ' . $parentDir);
        }

        if (!@copy($item->getPathname(), $targetPath)) {
            fail('Failed to copy file: ' . $item->getPathname() . ' -> ' . $targetPath);
        }
    }
}

fwrite(STDOUT, "Bootstrap and Bootstrap Icons assets synchronized successfully." . PHP_EOL);
