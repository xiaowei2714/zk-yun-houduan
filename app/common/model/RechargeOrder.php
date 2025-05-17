<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * Recharge模型
 * Class Recharge
 * @package app\common\model
 */
class RechargeOrder extends BaseModel
{
    use SoftDelete;
    protected $name = 'recharge';
    protected $deleteTime = 'delete_time';

}
