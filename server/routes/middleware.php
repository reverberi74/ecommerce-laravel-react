<?php

use App\Http\Middleware\CheckCart;

return [
    // Middleware di route personalizzati
    'checkCart' => CheckCart::class,
];
