<?php

namespace app\adminapi\controller;

use app\adminapi\lists\ConsumeRechargeLists;
use app\adminapi\logic\ConsumeRechargeLogic;
use app\adminapi\validate\ConsumeRechargeValidate;
use app\common\service\ConfigService;
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
            Log::record('Exception: api-ConsumeRechargeController-lists Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
            Log::record('Exception: api-ConsumeRechargeController-sum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        try {
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
            if ($info['status'] == 5) {
                return $this->fail('设置失败，当前状态为部分成功');
            }

            $res = ConsumeRechargeLogic::setRecharging($info['id'], $this->adminId);

            if (!$res) {
                return $this->fail('设置失败');
            }

            if ($info['is_external'] == 1) {
                (new ConsumeRechargeService())->setStatus($info['sn'], 2);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-setRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        try {
            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $updateIds = [];
            $updateSns = [];
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
                if ($value['is_external'] == 1) {
                    $updateSns[] = $value['sn'];
                }
            }

            if (!empty($updateIds)) {
                $res = ConsumeRechargeLogic::setBatchRecharging($updateIds, $this->adminId);
                if (!$res) {
                    return $this->fail('批量设置失败');
                }

                if (!empty($updateSns)) {
                    foreach ($updateSns as $sn) {
                        (new ConsumeRechargeService())->setStatus($sn, 2);
                    }
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
            Log::record('Exception: api-ConsumeRechargeController-setBatchRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        try {
            $info = ConsumeRechargeLogic::infoHaveUser($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }
            if ($info['status'] == 3) {
                return $this->success('设置成功', [], 1, 1);
            }
            if ($info['status'] == 4) {
                return $this->fail('设置失败，当前状态为已失败');
            }
            if ($info['status'] == 5) {
                return $this->fail('设置失败，当前状态为部分成功');
            }

            $ratioData = [
                'first_ratio' => '',
                'second_ratio' => '',
                'three_ratio' => ''
            ];

            $confNames = [];
            if ($info['type'] == 1) {
                $confNames[] = 'phone_first_ratio';
                $confNames[] = 'phone_second_ratio';
                $confNames[] = 'phone_three_ratio';
            } elseif ($info['type'] == 2) {
                $confNames[] = 'electricity_first_ratio';
                $confNames[] = 'electricity_second_ratio';
                $confNames[] = 'electricity_three_ratio';
            } elseif ($info['type'] == 3) {
                $confNames[] = 'quickly_first_ratio';
                $confNames[] = 'quickly_second_ratio';
                $confNames[] = 'quickly_three_ratio';
            } elseif ($info['type'] == 4) {
                $confNames[] = 'card_first_ratio';
                $confNames[] = 'card_second_ratio';
                $confNames[] = 'card_three_ratio';
            }

            if (empty($confNames)) {
                return $this->fail('类型异常');
            }

            $confData = ConfigService::getByNames('website', $confNames);
            $confData = array_column($confData, 'value', 'name');
            if ($info['type'] == 1) {
                $ratioData['first_ratio'] = $confData['phone_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['phone_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['phone_three_ratio'] ?? '';
            } elseif ($info['type'] == 2) {
                $ratioData['first_ratio'] = $confData['electricity_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['electricity_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['electricity_three_ratio'] ?? '';
            } elseif ($info['type'] == 3) {
                $ratioData['first_ratio'] = $confData['quickly_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['quickly_first_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['quickly_three_ratio'] ?? '';
            } elseif ($info['type'] == 4) {
                $ratioData['first_ratio'] = $confData['card_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['card_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['card_three_ratio'] ?? '';
            }

            $res = ConsumeRechargeLogic::setSuccess($info, $ratioData, $this->adminId);
            if (!$res) {
                return $this->fail('设置失败');
            }

            if ($info['is_external'] == 1) {
                (new ConsumeRechargeService())->setStatus($info['sn'], 3);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-setSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        try {
            $data = ConsumeRechargeLogic::getDataHaveUser($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $types = array_unique(array_column($data, 'type'));

            $confNames = [];
            if (in_array(1, $types)) {
                $confNames[] = 'phone_first_ratio';
                $confNames[] = 'phone_second_ratio';
                $confNames[] = 'phone_three_ratio';
            }
            if (in_array(2, $types)) {
                $confNames[] = 'electricity_first_ratio';
                $confNames[] = 'electricity_second_ratio';
                $confNames[] = 'electricity_three_ratio';
            }
            if (in_array(3, $types)) {
                $confNames[] = 'quickly_first_ratio';
                $confNames[] = 'quickly_second_ratio';
                $confNames[] = 'quickly_three_ratio';
            }
            if (in_array(4, $types)) {
                $confNames[] = 'card_first_ratio';
                $confNames[] = 'card_second_ratio';
                $confNames[] = 'card_three_ratio';
            }
            if (empty($confNames)) {
                return $this->fail('类型异常');
            }

            $confData = ConfigService::getByNames('website', $confNames);
            $confData = array_column($confData, 'value', 'name');

            $selectIds = [];
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

                $ratioData = [
                    'first_ratio' => '',
                    'second_ratio' => '',
                    'three_ratio' => ''
                ];

                if ($value['type'] == 1) {
                    $ratioData['first_ratio'] = $confData['phone_first_ratio'] ?? '';
                    $ratioData['second_ratio'] = $confData['phone_second_ratio'] ?? '';
                    $ratioData['three_ratio'] = $confData['phone_three_ratio'] ?? '';
                } elseif ($value['type'] == 2) {
                    $ratioData['first_ratio'] = $confData['electricity_first_ratio'] ?? '';
                    $ratioData['second_ratio'] = $confData['electricity_second_ratio'] ?? '';
                    $ratioData['three_ratio'] = $confData['electricity_three_ratio'] ?? '';
                } elseif ($value['type'] == 3) {
                    $ratioData['first_ratio'] = $confData['quickly_first_ratio'] ?? '';
                    $ratioData['second_ratio'] = $confData['quickly_first_ratio'] ?? '';
                    $ratioData['three_ratio'] = $confData['quickly_three_ratio'] ?? '';
                } elseif ($value['type'] == 4) {
                    $ratioData['first_ratio'] = $confData['card_first_ratio'] ?? '';
                    $ratioData['second_ratio'] = $confData['card_second_ratio'] ?? '';
                    $ratioData['three_ratio'] = $confData['card_three_ratio'] ?? '';
                }

                $res = ConsumeRechargeLogic::setSuccess($value, $ratioData, $this->adminId);
                if (!$res) {
                    $failMsg .= '单号：' . $value['sn'] . ' ' . ConsumeRechargeLogic::getError();
                }

                if ($value['is_external'] == 1) {
                    (new ConsumeRechargeService())->setStatus($value['sn'], 3);
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
            Log::record('Exception: api-ConsumeRechargeController-setBatchSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为部分成功
     *
     * @return Json
     */
    public function setPartSuccess(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        try {
            if (empty($params['value'])) {
                return $this->fail('请正确输入金额');
            }

            $info = ConsumeRechargeLogic::infoHaveUser($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }
            if ($info['status'] == 3) {
                return $this->success('设置成功', [], 1, 1);
            }
            if ($info['status'] == 4) {
                return $this->fail('设置失败，当前状态为已失败');
            }
            if ($info['status'] == 5) {
                return $this->fail('设置失败，当前状态为部分成功');
            }
            if (bccomp($params['value'], $info['recharge_price'], 2) >= 0) {
                return $this->fail('设置失败，到账金额不能大于等于充值金额');
            }

            $ratioData = [
                'first_ratio' => '',
                'second_ratio' => '',
                'three_ratio' => ''
            ];

            $confNames = [];
            if ($info['type'] == 1) {
                $confNames[] = 'phone_first_ratio';
                $confNames[] = 'phone_second_ratio';
                $confNames[] = 'phone_three_ratio';
            } elseif ($info['type'] == 2) {
                $confNames[] = 'electricity_first_ratio';
                $confNames[] = 'electricity_second_ratio';
                $confNames[] = 'electricity_three_ratio';
            } elseif ($info['type'] == 3) {
                $confNames[] = 'quickly_first_ratio';
                $confNames[] = 'quickly_second_ratio';
                $confNames[] = 'quickly_three_ratio';
            } elseif ($info['type'] == 4) {
                $confNames[] = 'card_first_ratio';
                $confNames[] = 'card_second_ratio';
                $confNames[] = 'card_three_ratio';
            }

            if (empty($confNames)) {
                return $this->fail('类型异常');
            }

            $confData = ConfigService::getByNames('website', $confNames);
            $confData = array_column($confData, 'value', 'name');
            if ($info['type'] == 1) {
                $ratioData['first_ratio'] = $confData['phone_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['phone_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['phone_three_ratio'] ?? '';
            } elseif ($info['type'] == 2) {
                $ratioData['first_ratio'] = $confData['electricity_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['electricity_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['electricity_three_ratio'] ?? '';
            } elseif ($info['type'] == 3) {
                $ratioData['first_ratio'] = $confData['quickly_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['quickly_first_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['quickly_three_ratio'] ?? '';
            } elseif ($info['type'] == 4) {
                $ratioData['first_ratio'] = $confData['card_first_ratio'] ?? '';
                $ratioData['second_ratio'] = $confData['card_second_ratio'] ?? '';
                $ratioData['three_ratio'] = $confData['card_three_ratio'] ?? '';
            }

            $res = ConsumeRechargeLogic::setPartSuccess($info, $ratioData, $this->adminId, $params['value']);
            if (!$res) {
                return $this->fail('设置失败');
            }

            if ($info['is_external'] == 1) {
                (new ConsumeRechargeService())->setStatus($info['sn'], 5);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-setPartSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        try {
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
            if ($info['status'] == 5) {
                return $this->fail('设置失败，当前状态为部分成功');
            }

            $res = ConsumeRechargeLogic::setFail($info['id'], $this->adminId);
            if (!$res) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }

            if ($info['is_external'] == 1) {
                (new ConsumeRechargeService())->setStatus($info['sn'], 4);
            }

            return $this->success('设置成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-setFail Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        try {
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

                $res = ConsumeRechargeLogic::setFail($value['id'], $this->adminId);
                if (!$res) {
                    $failMsg .= '单号：' . $value['sn'] . ' ' . ConsumeRechargeLogic::getError();
                }

                if ($value['is_external'] == 1) {
                    (new ConsumeRechargeService())->setStatus($value['sn'], 4);
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
            Log::record('Exception: api-ConsumeRechargeController-setBatchFail Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        try {
            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info) || empty($info['id'])) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            if ($info['type'] == 1 || $info['type'] == 3) {
                $requestData = (new ConsumeRechargeService())->getPhoneBalance($info['account']);
                if (empty($requestData)) {
                    return $this->fail('暂不支持的手机号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
                }

                $price = $requestData['cur_fee'];
            } elseif ($info['type'] == 2) {
                $requestData = (new ConsumeRechargeService())->getElectricityBalance($info['account'], ($info['name_area'] + 1));
                if (empty($requestData)) {
                    return $this->fail('暂不支持的卡号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
                }

                $price = $requestData['real_balance'];
            } elseif ($info['type'] == 4) {
                return $this->fail('礼品卡不支持更新操作');
            } else {
                return $this->fail('不支持的类型');
            }

            $res = ConsumeRechargeLogic::setBalance($info['id'], $price);
            if (!$res) {
                return $this->fail('更新失败');
            }

            return $this->success('更新成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-genBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        try {
            $data = ConsumeRechargeLogic::getData($params['ids']);
            if (empty($data)) {
                return $this->fail('不存在的数据，请刷新页面后再试');
            }

            $selectIds = [];
            $failMsg = '';
            foreach ($data as $value) {

                $selectIds[] = $value['id'];

                $price = 0;
                if ($value['type'] == 1 || $value['type'] == 3) {
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
                } elseif ($value['type'] == 2) {
                    $requestData = (new ConsumeRechargeService())->getElectricityBalance($value['account'], ($value['name_area'] + 1));
                    if (empty($requestData)) {
                        $failMsg .= '单号：' . $value['sn'] . ' 暂不支持的卡号充值';
                        continue;
                    }
                    if (!$requestData['is_success']) {
                        $failMsg .= '单号：' . $value['sn'] . ' ' . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
                        continue;
                    }

                    $price = $requestData['real_balance'];
                } elseif ($value['type'] == 4) {
                    $failMsg .= '单号：' . $value['sn'] . ' 礼品卡不支持更新操作';
                    continue;
                } else {
                    $failMsg .= '单号：' . $value['sn'] . ' 不支持的类型';
                    continue;
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
            Log::record('Exception: api-ConsumeRechargeController-batchGenBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
