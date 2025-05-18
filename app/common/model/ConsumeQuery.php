<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * ConsumeRecharge.php模型
 * Class Recharge
 * @package app\common\model
 */
class ConsumeQuery extends BaseModel
{
    use SoftDelete;
    protected $name = 'consume_query';
    protected $deleteTime = 'delete_time';

}
