<?php

/**
 * Fallback Root Entrypoint untuk CloudPanel
 * Memastikan aplikasi tetap berjalan baik meskipun Document Root mengarah ke root folder maupun /public
 */
require __DIR__ . '/public/index.php';
