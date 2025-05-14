<?php
/**
 * Author: Jarshs
 * 2025/4/30
 */

namespace app\common\model;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


/**
 * SetMeal模型
 * Class SetMeal
 * @package app\common\model
 */
class UserMoneyLog extends BaseModel
{
    use SoftDelete;

    protected $name = 'user_money_log';
    protected $deleteTime = 'delete_time';


}