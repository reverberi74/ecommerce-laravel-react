<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Definisce quale driver usare per Intervention Image.
    | Può essere: "gd", "imagick", oppure "auto" (default, autodetect).
    |
    */
    'driver' => env('IMAGE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Qualità di output per ciascun formato
    |--------------------------------------------------------------------------
    |
    | Definisce la qualità di compressione per i formati JPEG, PNG, WebP, ecc.
    | PNG usa valori da 0 (nessuna compressione) a 9 (massima compressione).
    |
    */
    'quality' => [
        'jpeg' => 85, // 0–100
        'webp' => 80, // 0–100
        'png'  => 8,  // 0–9
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail settings (default)
    |--------------------------------------------------------------------------
    |
    | Imposta dimensioni e metodo per generare miniature:
    | - cover: taglia e riempie l'area (ritaglio centrato)
    | - fit: adatta mantenendo proporzioni
    | - resize: scala esattamente (può deformare)
    | - canvas: ridimensiona mantenendo proporzioni + sfondo
    | - smart_canvas: come canvas ma ottimizzato per prodotti
    |
    */
    'thumbnail' => [
        'width' => 300,
        'height' => 300,
        'method' => 'canvas', // cover | fit | resize | canvas | smart_canvas
        'format' => 'jpeg',   // jpeg | png | webp
        'position' => 'center',
        'background_color' => '#ffffff', // Colore sfondo per metodi canvas
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurazioni specifiche per tipo di contenuto
    |--------------------------------------------------------------------------
    |
    | Permette di avere impostazioni diverse per products, categories, ecc.
    | Se non specificate, vengono usate quelle di default sopra.
    |
    */
    'thumbnails' => [

        // Configurazione per prodotti (laptop, elettronica, ecc.)
        'products' => [
            'width' => 300,
            'height' => 300,
            'method' => 'smart_canvas', // Ottimizzato per prodotti
            'format' => 'jpeg',
            'background_color' => '#ffffff', // Sfondo bianco pulito
            'position' => 'center',
        ],

        // Configurazione per categorie
        'categories' => [
            'width' => 250,
            'height' => 250,
            'method' => 'canvas',
            'format' => 'jpeg',
            'background_color' => '#f8f9fa', // Sfondo grigio chiaro
            'position' => 'center',
        ],

        // Configurazione per banner/slider (se ne hai)
        'banners' => [
            'width' => 800,
            'height' => 400,
            'method' => 'cover', // Per i banner va bene il crop
            'format' => 'jpeg',
            'position' => 'center',
        ],

        // Configurazione per avatar utenti (se ne hai)
        'avatars' => [
            'width' => 150,
            'height' => 150,
            'method' => 'cover', // Avatar sempre quadrati
            'format' => 'jpeg',
            'position' => 'center',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Formati supportati per upload
    |--------------------------------------------------------------------------
    |
    | Lista dei formati di immagine accettati per l'upload
    |
    */
    'allowed_formats' => [
        'jpeg', 'jpg', 'png', 'webp', 'gif'
    ],

    /*
    |--------------------------------------------------------------------------
    | Dimensione massima file (in KB)
    |--------------------------------------------------------------------------
    |
    | Dimensione massima accettata per l'upload delle immagini
    |
    */
    'max_file_size' => 5120, // 5MB in KB

    /*
    |--------------------------------------------------------------------------
    | Watermark settings (opzionale)
    |--------------------------------------------------------------------------
    |
    | Se vuoi aggiungere watermark alle immagini
    |
    */
    'watermark' => [
        'enabled' => false,
        'image' => null, // path al file watermark
        'position' => 'bottom-right',
        'opacity' => 50, // 0-100
    ],

];
