<?php

use Adobrovolsky97\Illuminar\Illuminar;
use Adobrovolsky97\Illuminar\Support\PendingDump;

if (!function_exists('illuminar')) {
    /**
     * illuminar()                        - Illuminar instance: illuminar()->trackQueries()
     * illuminar($user, $order)           - shortcut for illuminar()->dump($user, $order)
     * illuminar($user)->tag('x')->red()  - dump can be decorated
     *
     * @param mixed ...$args
     * @return ($args is empty ? Illuminar : PendingDump)
     */
    function illuminar(...$args)
    {
        $illuminar = app(Illuminar::class);

        return empty($args) ? $illuminar : $illuminar->dump(...$args);
    }
}
