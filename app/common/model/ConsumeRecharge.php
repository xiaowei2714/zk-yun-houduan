<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * ConsumeRecharge模型
 * Class Recharge
 * @package app\common\model
 */
class ConsumeRecharge extends BaseModel
{
    use SoftDelete;
    protected $name = 'consume_recharge';
    protected $deleteTime = 'delete_time';

}
