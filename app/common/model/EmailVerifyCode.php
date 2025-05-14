<?php
/**
 * Author: Jarshs
 * 2025/4/6
 */

namespace app\common\model;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


/**
 * EmailVerifyCode模型
 * Class EmailVerifyCode
 * @package app\common\model
 */
class EmailVerifyCode extends BaseModel
{
    protected $name = 'email_verify_code';
}