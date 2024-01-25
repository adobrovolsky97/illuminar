<?php

namespace Adobrovolsky97\Illuminar\Tests\Stubs;

/**
 * Class TestEvent
 */
class TestEvent
{
    /**
     * @var array
     */
    public array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
