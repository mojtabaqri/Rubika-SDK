<?php

declare(strict_types=1);

if (!defined('APP_TITLE')) {
    require_once __DIR__ . '/config.php';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.4/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        content: [],
        theme: {
            extend: {
                fontFamily: {
                    vazir: ['Vazir', 'sans-serif']
                }
            }
        }
    }
</script>
