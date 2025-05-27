<?php

namespace app\common\model;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * RechargeApi模型
 * Class Recharge
 * @package app\common\model
 */
class RechargeApi extends BaseModel
{
    use SoftDelete;
    protected $name = 'recharge_api';
    protected $deleteTime = 'delete_time';
}
