<?php

namespace Adobrovolsky97\Illuminar\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TestUserModel
 */
class TestUserModel extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'test_users';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'is_active'
    ];
}
