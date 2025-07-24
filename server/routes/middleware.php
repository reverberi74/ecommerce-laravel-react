<?php

use App\Http\Middleware\CheckCartOwnership;

return [
    // Middleware di route personalizzati
    'checkCart' => CheckCartOwnership::class,
];
