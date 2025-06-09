<?php
// Set the default timezone for the application
\date_default_timezone_set('Asia/Kuala_Lumpur');

if (file_exists(dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php';
}
