<?php

namespace app\api\controller;

use app\api\lists\recharge\RechargeLists;
use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\RechargeLogic;
use app\api\service\UserMealService;
use app\api\validate\ConsumeRechargeValidate;
use app\api\validate\RechargeValidate;
use think\response\Json;

/**
 * 话费、电费、油费充值
 *
 * Class RechargeController
 * @package app\shopapi\controller
 */
class ConsumeRechargeController extends BaseApiController
{
    /**
     * 话费充值
     *
     * @return Json
     */
    public function phoneRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('phoneRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 1
        ]);

        if (!isset($params['name'])) {
            $params['name'] = '';
        }
        if (mb_strlen($params['name']) > 30) {
            return $this->fail('机主姓名不能超过30个字符');
        }

        // 获取设定的充值信息
        $mealInfo = (new UserMealService())->getMealInfo($params['meal_id'], $this->userId);
        if ($mealInfo['type'] != 1) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $params['money'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入话费充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $params['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入话费充值页面');
        }
        if (bccomp($mealInfo['discounted_price'], $params['meal_discounted_price'], 2) != 0) {
            return $this->fail('充值实付金额发生变化，请重新进入话费充值页面');
        }

        $params['account'] = $params['phone'];
        unset($params['phone']);

        $params['name_area'] = $params['name'];
        unset($params['name']);

        $result = ConsumeRechargeLogic::recharge($params);
        if (false === $result) {
            return $this->fail(ConsumeRechargeLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * 批量话费充值
     *
     * @return Json
     */
    public function batchPhoneRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('batchPhoneRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 1
        ]);

        // 获取设定的充值信息
        $mealInfo = (new UserMealService())->getMealInfo($params['meal_id'], $this->userId);
        if ($mealInfo['type'] != 1) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $params['money'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入话费充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $params['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入话费充值页面');
        }
        if (bccomp($mealInfo['discounted_price'], $params['meal_discounted_price'], 2) != 0) {
            return $this->fail('充值实付金额发生变化，请重新进入话费充值页面');
        }

        $msg = '';
        $haveFail = false;
        $batchData = explode(PHP_EOL, $params['batchData']);
        foreach ($batchData as $value) {
            $tmpData = explode(' ', $value);
            if (empty($tmpData[0])) {
                return $this->fail('请正确输入手机号');
            }

            $phone = $tmpData[0];
            $name = $tmpData[1] ?? '';

            if (!preg_match("/^1[3456789]\d{9}$/", $phone)) {
                $msg .= $value . ' 请正确输入手机号' . PHP_EOL;
                continue;
            }
            if (mb_strlen($name) > 30) {
                $msg .= $value . ' 机主姓名不能超过30个字符' . PHP_EOL;
                continue;
            }

            $tmpParams = [
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'account' => $phone,
                'name_area' => $name,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'meal_discounted_price' => $mealInfo['discounted_price'],
                'type' => $params['type']
            ];

            $result = ConsumeRechargeLogic::recharge($tmpParams);
            if ($result) {
                $msg .= $value . ' 充值成功' . PHP_EOL;
            } else {
                $haveFail = true;
                $msg .= $value . ' 充值失败' . PHP_EOL;
            }
        }

        if ($haveFail) {
            return $this->fail($msg);
        }

        return $this->data([
            'msg' => $msg
        ]);
    }

    /**
     * 电费充值
     *
     * @return Json
     */
    public function electricityRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('electricityRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        if (strlen($params['number']) > 30) {
            return $this->fail('户号不能超过30个字符');
        }
        if (!isset($params['area'])) {
            $params['area'] = '';
        }
        if (mb_strlen($params['area']) > 30) {
            return $this->fail('地区不能超过30个字符');
        }

        // 获取设定的充值信息
        $mealInfo = (new UserMealService())->getMealInfo($params['meal_id'], $this->userId);
        if ($mealInfo['type'] != 2) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $params['money'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入电费充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $params['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入电费充值页面');
        }
        if (bccomp($mealInfo['discounted_price'], $params['meal_discounted_price'], 2) != 0) {
            return $this->fail('充值实付金额发生变化，请重新进入电费充值页面');
        }

        $params['account'] = $params['number'];
        unset($params['number']);

        $params['name_area'] = $params['area'];
        unset($params['area']);

        $result = ConsumeRechargeLogic::recharge($params);
        if (false === $result) {
            return $this->fail(ConsumeRechargeLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * 批量电费充值
     *
     * @return Json
     */
    public function batchElectricityRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('batchElectricityRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        // 获取设定的充值信息
        $mealInfo = (new UserMealService())->getMealInfo($params['meal_id'], $this->userId);
        if ($mealInfo['type'] != 2) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $params['money'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入电费充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $params['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入电费充值页面');
        }
        if (bccomp($mealInfo['discounted_price'], $params['meal_discounted_price'], 2) != 0) {
            return $this->fail('充值实付金额发生变化，请重新进入电费充值页面');
        }

        $msg = '';
        $haveFail = false;
        $batchData = explode(PHP_EOL, $params['batchData']);
        foreach ($batchData as $value) {
            $tmpData = explode(' ', $value);
            if (empty($tmpData[0])) {
                return $this->fail('请正确输入户号');
            }

            $number = $tmpData[0];
            $name = $tmpData[1] ?? '';

            if (strlen($number) > 30) {
                $msg .= $value . ' 户号不能超过30个字符' . PHP_EOL;
                continue;
            }
            if (mb_strlen($name) > 30) {
                $msg .= $value . ' 地区不能超过30个字符' . PHP_EOL;
                continue;
            }

            $tmpParams = [
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'account' => $number,
                'name_area' => $name,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'meal_discounted_price' => $mealInfo['discounted_price'],
                'type' => $params['type']
            ];

            $result = ConsumeRechargeLogic::recharge($tmpParams);
            if ($result) {
                $msg .= $value . ' 充值成功' . PHP_EOL;
            } else {
                $haveFail = true;
                $msg .= $value . ' 充值失败' . PHP_EOL;
            }
        }

        if ($haveFail) {
            return $this->fail($msg);
        }

        return $this->data([
            'msg' => $msg
        ]);
    }
}
