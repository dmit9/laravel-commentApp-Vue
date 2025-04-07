<?php
$target = __DIR__ . '/storage/app/public';
$link = __DIR__ . '/public/storage';

if (is_link($link) || file_exists($link)) {
    echo 'Symlink already exists or the path is occupied5  .   ';
    echo $link;
} else {
    if (symlink($target, $link)) {
        echo 'Symlink created successfully!';
    } else {
        echo 'Failed to create symlink.';
    }
}
?>
