<?php

namespace app\api\controller;

use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\UserLogic;
use app\api\service\UserMealService;
use app\api\validate\ConsumeRechargeValidate;
use app\common\service\ConsumeRechargeService;
use think\facade\Config;
use think\facade\Log;
use think\response\Json;
use Exception;

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
        try {
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

            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
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

            // 比较用户余额
            if (bccomp($userInfo['user_money'], $params['meal_discounted_price'], 2) != 1) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            // 获取实时话费余额
            $requestData = (new ConsumeRechargeService())->getPhoneBalance($params['phone']);
            if (empty($requestData)) {
                return $this->fail('暂不支持的手机号充值');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
            }

            $params['recharge_up_price'] = $requestData['cur_fee'];
            $params['account_type'] = $requestData['isp_id'];
            $params['account'] = $params['phone'];
            $params['name_area'] = $params['name'];
            unset($params['phone']);
            unset($params['name']);

            $result = ConsumeRechargeLogic::recharge($params);
            if (false === $result) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }

            return $this->data($result);

        } catch (Exception $e) {
            Log::record('Exception: phoneRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量话费充值
     *
     * @return Json
     */
    public function batchPhoneRecharge(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('batchPhoneRecharge', [
                'user_id' => $this->userId,
                'terminal' => $this->userInfo['terminal'],
                'type' => 1
            ]);

            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 批量数据
            $batchData = explode(PHP_EOL, $params['batchData']);

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

            // 比较用户余额
            $total = bcmul($params['meal_discounted_price'], count($batchData), 2);
            if (bccomp($userInfo['user_money'], $total, 2) != 1) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            $msg = '';
            $haveFail = false;
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

                // 获取实时话费余额
                $requestData = (new ConsumeRechargeService())->getPhoneBalance($phone);
                if (empty($requestData)) {
                    $msg .= $value . ' 暂不支持的手机号充值' . PHP_EOL;
                }
                if (!$requestData['is_success']) {
                    $msg .= $value . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值') . PHP_EOL;
                }

                $tmpParams = [
                    'user_id' => $params['user_id'],
                    'money' => $params['money'],
                    'account' => $phone,
                    'name_area' => $name,
                    'meal_id' => $params['meal_id'],
                    'meal_discount' => $params['meal_discount'],
                    'meal_discounted_price' => $mealInfo['discounted_price'],
                    'type' => $params['type'],
                    'recharge_up_price' => $requestData['cur_fee'],
                    'account_type' => $requestData['isp_id']
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

        } catch (Exception $e) {
            Log::record('Exception: batchPhoneRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 电费充值
     *
     * @return Json
     */
    public function electricityRecharge(): Json
    {
        try {
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

            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
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

            // 比较用户余额
            if (bccomp($userInfo['user_money'], $params['meal_discounted_price'], 2) != 1) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            // 获取实时电费余额
            $requestData = (new ConsumeRechargeService())->getElectricityBalance($params['number'], ($params['area'] + 1));
            if (empty($requestData)) {
                return $this->fail('暂不支持的卡号充值');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
            }

            $params['recharge_up_price'] = $requestData['owed_balance'];
            $params['account_type'] = '';
            $params['account'] = $params['number'];
            unset($params['number']);

            $params['name_area'] = $params['area'];
            unset($params['area']);

            $result = ConsumeRechargeLogic::recharge($params);
            if (false === $result) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }
            return $this->data($result);

        } catch (Exception $e) {
            Log::record('Exception: electricityRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量电费充值
     *
     * @return Json
     */
    public function batchElectricityRecharge(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('batchElectricityRecharge', [
                'user_id' => $this->userId,
                'terminal' => $this->userInfo['terminal'],
                'type' => 2
            ]);

            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
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

            $batchData = explode(PHP_EOL, $params['batchData']);

            // 比较用户余额
            $total = bcmul($params['meal_discounted_price'], count($batchData), 2);
            if (bccomp($userInfo['user_money'], $total, 2) != 1) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            $areaData = array_flip(Config::get('project.area'));

            $msg = '';
            $haveFail = false;
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
                    $msg .= $value . ' 区域不能超过30个字符' . PHP_EOL;
                    continue;
                }
                if (!isset($areaData[$name])) {
                    $msg .= $value . ' 不支持的区域' . PHP_EOL;
                    continue;
                }

                $areaCode = $areaData[$name];

                // 获取实时电费余额
                $requestData = (new ConsumeRechargeService())->getElectricityBalance($number, ($areaCode + 1));
                if (empty($requestData)) {
                    $msg .= $value . ' 暂不支持的户号充值' . PHP_EOL;
                }
                if (!$requestData['is_success']) {
                    $msg .= $value . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的户号充值') . PHP_EOL;
                }

                $tmpParams = [
                    'user_id' => $params['user_id'],
                    'money' => $params['money'],
                    'account' => $number,
                    'name_area' => $areaCode,
                    'meal_id' => $params['meal_id'],
                    'meal_discount' => $params['meal_discount'],
                    'meal_discounted_price' => $mealInfo['discounted_price'],
                    'type' => $params['type'],
                    'recharge_up_price' => $requestData['owed_balance'],
                    'account_type' => ''
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
        } catch (Exception $e) {
            Log::record('Exception: batchElectricityRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
