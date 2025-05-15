<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------
namespace app\api\controller;

use app\api\lists\recharge\RechargeLists;
use app\api\logic\RechargeLogic;
use app\api\service\UserMealService;
use app\api\validate\RechargeValidate;
use think\response\Json;


/**
 * 充值控制器
 * Class RechargeController
 * @package app\shopapi\controller
 */
class RechargeController extends BaseApiController
{

    /**
     * @notes 获取充值列表
     * @return Json
     * @author 段誉
     * @date 2023/2/23 18:55
     */
    public function lists()
    {
        return $this->dataLists(new RechargeLists());
    }

    /**
     * 话费充值
     *
     * @return Json
     */
    public function phoneRecharge(): Json
    {
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 1
        ]);

        if (empty($params['meal_id']) || !isset($params['meal_discount']) || !isset($params['meal_discounted_price'])) {
            return $this->fail('充值选项不能为空');
        }

        if (empty($params['money'])) {
            return $this->fail('充值金额不能为空');
        }

        if (empty($params['phone'])) {
            return $this->fail('手机号不能为空');
        }
        if (!preg_match("/^1[3456789]\d{9}$/", $params['phone'])) {
            return $this->fail('请正确输入手机号');
        }

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

        $params['number'] = $params['phone'];
        unset($params['phone']);

        $result = RechargeLogic::recharge($params);
        if (false === $result) {
            return $this->fail(RechargeLogic::getError());
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
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 1
        ]);

        if (empty($params['meal_id']) || !isset($params['meal_discount']) || !isset($params['meal_discounted_price'])) {
            return $this->fail('充值选项不能为空');
        }
        if (empty($params['batchData'])) {
            return $this->fail('请正确输入手机号');
        }
        if (empty($params['money'])) {
            return $this->fail('充值金额不能为空');
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
                'terminal' => $params['terminal'],
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'number' => $phone,
                'name' => $name,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'meal_discounted_price' => $mealInfo['discounted_price'],
                'type' => $params['type']
            ];

            $result = RechargeLogic::recharge($tmpParams);
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
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        if (empty($params['meal_id']) || !isset($params['meal_discount']) || !isset($params['meal_discounted_price'])) {
            return $this->fail('充值选项不能为空');
        }

        if (empty($params['money'])) {
            return $this->fail('充值金额不能为空');
        }

        if (empty($params['number'])) {
            return $this->fail('户号不能为空');
        }
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

        $params['name'] = $params['area'];
        unset($params['area']);

        $result = RechargeLogic::recharge($params);
        if (false === $result) {
            return $this->fail(RechargeLogic::getError());
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
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        if (empty($params['meal_id']) || !isset($params['meal_discount']) || !isset($params['meal_discounted_price'])) {
            return $this->fail('充值选项不能为空');
        }
        if (empty($params['batchData'])) {
            return $this->fail('请正确输入手机号');
        }
        if (empty($params['money'])) {
            return $this->fail('充值金额不能为空');
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
                'terminal' => $params['terminal'],
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'number' => $number,
                'name' => $name,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'meal_discounted_price' => $mealInfo['discounted_price'],
                'type' => $params['type']
            ];

            $result = RechargeLogic::recharge($tmpParams);
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
     * @notes 充值配置
     * @return Json
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public function config()
    {
        return $this->data(RechargeLogic::config($this->userId));
    }


}
