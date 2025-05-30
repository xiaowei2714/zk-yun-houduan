<?php


namespace app\api\validate;

use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 交易大厅
 */
class AdValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
        'num' => 'require|float|gt:0',
        'price' => 'require|float|gt:0',
        'min_price' => 'require|float|gt:0|lt:max_price',
        'max_price' => 'require|float|gt:0|elt:num',
        'pay_time' => 'require|integer|egt:10|elt:30',
        'pay_type' => 'require',
        'type' => 'require|integer|egt:1|elt:2',
    ];

    protected $message = [
        'id' => '参数错误',
        'num' => '输入出售数量错误',
        'price' => '输入出售价格错误',
        'min_price' => '输入最小交易额错误',
        'max_price' => '输入最大交易额错误',
        'pay_time' => '输入付款时限错误',
        'pay_type' => '输入支付方式错误',
        'type' => '输入买入卖出错误',
    ];

    /**
     * 增加
     *
     * @return AdValidate
     */
    public function sceneAdd(): AdValidate
    {
        return $this->only(['num', 'price', 'min_price', 'min_price', 'pay_time', 'pay_type', 'type']);
    }

    /**
     * 列表
     *
     * @return AdValidate
     */
    public function sceneList(): AdValidate
    {
        return $this->only(['type']);
    }

    /**
     * ID
     *
     * @return AdValidate
     */
    public function sceneId(): AdValidate
    {
        return $this->only(['id']);
    }

    /**
     * 购买
     *
     * @return AdValidate
     */
    public function sceneAdBuy(): AdValidate
    {
        return $this->only(['id', 'pay_type', 'price']);
    }

}
