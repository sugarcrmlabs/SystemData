#!/usr/bin/env php
<?php

// config variables

$id = 'cli_system_data';
$name = 'CLI System Data Export Import tool';
$built_in_version = '7.9.1.0';
$author = 'Enrico Simonetti, SugarCRM Inc.';
$regex_matches = '^7.9.[\d]+.[\d]+$';

// end config variables


if (empty($argv[1])) {
    die("Use $argv[0] [version]\n");
}

$version = $argv[1];
$id .= '_'. $version;
$zipFile = "releases/module_{$id}.zip";

if (file_exists($zipFile)) {
    die("Release $zipFile already exists!\n");
}

$manifest = array(
    'id' => $id,
    'built_in_version' => $built_in_version,
    'name' => $name,
    'description' => $name,
    'version' => $version,
    'author' => $author,
    'is_uninstallable' => true,
    'published_date' => date("Y-m-d H:i:s"),
    'type' => 'module',
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(
        ),
        'regex_matches' => array(
            $regex_matches,
        ),
    ),
);

$installdefs = array('copy' => array());
echo "Creating {$zipFile} ... \n";

$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE);
$basePath = realpath('src/');

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if ($file->isFile()) {
        $fileReal = $file->getRealPath();
        if(!in_array($file->getFilename(), array('.DS_Store'))) {
            $fileRelative = '' . str_replace($basePath.'/', '', $fileReal);
            echo " [*] $fileRelative \n";
            $zip->addFile($fileReal, $fileRelative);

            if(!in_array($file->getFilename(), array('LICENSE'))) {
                $installdefs['copy'][] = array(
                    'from' => '<basepath>/' . $fileRelative,
                    'to' => $fileRelative,
                );
            }
        }
    }
}

$manifestContent = sprintf(
    "<?php\n\$manifest = %s;\n\$installdefs = %s;\n",
    var_export($manifest, true),
    var_export($installdefs, true)
);

$zip->addFromString('manifest.php', $manifestContent);
$zip->close();

echo "done\n";
exit(0);
