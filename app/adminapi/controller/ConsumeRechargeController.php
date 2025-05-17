<?php

namespace app\adminapi\controller;

use app\adminapi\lists\ConsumeRechargeLists;
use app\adminapi\logic\ConsumeRechargeLogic;
use app\adminapi\validate\ConsumeRechargeValidate;
use app\common\service\ConsumeRechargeService;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 话费、电费充值
 * Class RechargeController
 * @package app\adminapi\controller
 */
class ConsumeRechargeController extends BaseAdminController
{
    /**
     * 获取列表
     *
     * @return Json
     */
    public function lists(): Json
    {
        try {
            return $this->dataLists(new ConsumeRechargeLists());
        } catch (Exception $e) {
            Log::record('Exception: rechargeList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 获取列表
     *
     * @return Json
     */
    public function sum(): Json
    {
        try {
            $data = (new ConsumeRechargeLists())->sum();
            return $this->success('', ['sum' => number_format($data, 2)]);
        } catch (Exception $e) {
            Log::record('Exception: rechargingSum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为充值中
     *
     * @return Json
     */
    public function setRecharging(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }
            if ($info['status'] == 2) {
                return $this->success('设置成功', [], 1, 1);
            }
            if ($info['status'] == 3) {
                return $this->fail('设置失败，当前状态为已成功');
            }
            if ($info['status'] == 4) {
                return $this->fail('设置失败，当前状态为已失败');
            }

            $res = ConsumeRechargeLogic::setRecharging($info['id']);

            if (!$res) {
                return $this->fail('设置失败');
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为批量充值中
     *
     * @return Json
     */
    public function setBatchRecharging(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $updateIds = [];
            $failMsg = '';
            foreach ($data as $value) {

                $selectIds[] = $value['id'];

                if ($value['status'] == 2) {
                    continue;
                }
                if ($value['status'] == 3) {
                    $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已成功；';
                    continue;
                }
                if ($value['status'] == 4) {
                    $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已失败；';
                    continue;
                }

                $updateIds[] = $value['id'];
            }

            if (!empty($updateIds)) {
                $res = ConsumeRechargeLogic::setBatchRecharging($updateIds);
                if (!$res) {
                    return $this->fail('批量设置失败');
                }
            }

            if (!empty($failMsg)) {
                return $this->fail('部分失败：' . $failMsg);
            }

            $diffIds = array_diff($params['ids'], $selectIds);
            if (!empty($diffIds)) {
                $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
                return $this->fail($failMsg);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setBatchRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为成功
     *
     * @return Json
     */
    public function setSuccess(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }
            if ($info['status'] == 3) {
                return $this->success('设置成功', [], 1, 1);
            }
            if ($info['status'] == 4) {
                return $this->fail('设置失败，当前状态为已失败');
            }

            $res = ConsumeRechargeLogic::setSuccess($info['id']);

            if (!$res) {
                return $this->fail('设置失败');
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为批量成功
     *
     * @return Json
     */
    public function setBatchSuccess(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $updateIds = [];
            $failMsg = '';
            foreach ($data as $value) {

                $selectIds[] = $value['id'];

                if ($value['status'] == 3) {
                    continue;
                }
                if ($value['status'] == 4) {
                    $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已失败；';
                    continue;
                }

                $updateIds[] = $value['id'];
            }

            if (!empty($updateIds)) {
                $res = ConsumeRechargeLogic::setBatchSuccess($updateIds);
                if (!$res) {
                    return $this->fail('批量设置失败');
                }
            }

            if (!empty($failMsg)) {
                return $this->fail('部分失败：' . $failMsg);
            }

            $diffIds = array_diff($params['ids'], $selectIds);
            if (!empty($diffIds)) {
                $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
                return $this->fail($failMsg);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setBatchSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为失败
     *
     * @return Json
     */
    public function setFail(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }
            if ($info['status'] == 4) {
                return $this->success('设置成功', [], 1, 1);
            }
            if ($info['status'] == 3) {
                return $this->fail('设置失败，当前状态为已成功');
            }

            $res = ConsumeRechargeLogic::setFail($info['id']);
            if (!$res) {
                return $this->fail('设置失败');
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setFail Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为批量失败
     *
     * @return Json
     */
    public function setBatchFail(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $failMsg = '';
            foreach ($data as $value) {

                $selectIds[] = $value['id'];

                if ($value['status'] == 3) {
                    $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已成功；';
                    continue;
                }
                if ($value['status'] == 4) {
                    continue;
                }

                $res = ConsumeRechargeLogic::setFail($value['id']);
                if (!$res) {
                    $failMsg .= '单号：' . $value['sn'] . ' 设置失败';
                }
            }

            if (!empty($failMsg)) {
                return $this->fail('部分失败：' . $failMsg);
            }

            $diffIds = array_diff($params['ids'], $selectIds);
            if (!empty($diffIds)) {
                $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
                return $this->fail($failMsg);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setBatchFail Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 更新余额
     *
     * @return Json
     */
    public function genBalance(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            if ($info['type'] == 1) {
                $requestData = (new ConsumeRechargeService())->getPhoneBalance($info['phone']);
                if (empty($requestData)) {
                    return $this->fail('暂不支持的手机号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
                }

                $price = $requestData['cur_fee'];
            } else {
                $requestData = (new ConsumeRechargeService())->getElectricityBalance($info['account'], ($info['name_area'] + 1));
                if (empty($requestData)) {
                    return $this->fail('暂不支持的卡号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
                }

                $price = $requestData['owed_balance'];
            }

            $res = ConsumeRechargeLogic::setBalance($info['id'], $price);
            if (!$res) {
                return $this->fail('更新失败');
            }

            return $this->success('更新成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量更新余额
     *
     * @return Json
     */
    public function batchGenBalance(): Json
    {
        try {

            $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $failMsg = '';
            foreach ($data as $value) {

                $selectIds[] = $value['id'];

                $price = 0;
                if ($value['type'] == 1) {
                    $requestData = (new ConsumeRechargeService())->getPhoneBalance($value['account']);
                    if (empty($requestData)) {
                        $failMsg .= '单号：' . $value['sn'] . ' 暂不支持的手机号充值';
                        continue;
                    }
                    if (!$requestData['is_success']) {
                        $failMsg .= '单号：' . $value['sn'] . ' ' . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
                        continue;
                    }

                    $price = $requestData['cur_fee'];
                } else {
                    $requestData = (new ConsumeRechargeService())->getElectricityBalance($value['account'], ($value['name_area'] + 1));
                    if (empty($requestData)) {
                        $failMsg .= '单号：' . $value['sn'] . ' 暂不支持的卡号充值';
                        continue;
                    }
                    if (!$requestData['is_success']) {
                        $failMsg .= '单号：' . $value['sn'] . ' ' . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
                        continue;
                    }

                    $price = $requestData['owed_balance'];
                }

                $res = ConsumeRechargeLogic::setBalance($value['id'], $price);
                if (!$res) {
                    $failMsg .= '单号：' . $value['sn'] . ' 更新失败';
                }
            }

            if (!empty($failMsg)) {
                return $this->fail('部分失败：' . $failMsg);
            }

            $diffIds = array_diff($params['ids'], $selectIds);
            if (!empty($diffIds)) {
                $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 更新失败，找不到订单';
                return $this->fail($failMsg);
            }

            return $this->success('更新成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: setBatchRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
