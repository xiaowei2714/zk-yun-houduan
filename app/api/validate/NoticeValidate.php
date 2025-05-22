<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 消息通知
 */
class NoticeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number|between:1,10000000',
        'type' => 'require|number|between:1,10',
    ];

    protected $message = [
        'id' => '参数错误',
        'type' => '参数错误',
    ];

    /**
     * Id
     *
     * @return NoticeValidate
     */
    public function sceneId(): NoticeValidate
    {
        return $this->only(['id']);
    }

    /**
     * 类型
     *
     * @return NoticeValidate
     */
    public function sceneType(): NoticeValidate
    {
        return $this->only(['type']);
    }
}
