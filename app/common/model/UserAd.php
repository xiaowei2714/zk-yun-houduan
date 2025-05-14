<?php
/**
 * Author: Jarshs
 * 2025/5/2
 */

namespace app\common\model;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


/**
 * SetMeal模型
 * Class SetMeal
 * @package app\common\model
 */
class UserAd extends BaseModel
{
    use SoftDelete;

    protected $name = 'user_ad';
    protected $deleteTime = 'delete_time';


}