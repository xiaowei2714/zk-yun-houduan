<?php


namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 用户账户记录验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class UserAccountLogValidate extends BaseValidate
{
    protected $rule = [
        'account' => 'require',
        'money' => 'require|gt:0',
    ];

    protected $message = [
        'account' => '划转账户不能为空',
        'money.require' => '请填写转账金额',
        'money.gt' => '请填写大于0的转账金额',
    ];

    /**
     * 转账
     *
     * @return UserAccountLogValidate
     */
    public function sceneTransfer(): UserAccountLogValidate
    {
        return $this->only(['account', 'money']);
    }
}
