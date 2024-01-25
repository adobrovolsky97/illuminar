<?php

use Adobrovolsky97\Illuminar\Illuminar;

if (!function_exists('illuminar')) {
    function illuminar(): Illuminar
    {
        return app(Illuminar::class);
    }
}
