<?php

namespace app\api\controller;

use app\adminapi\logic\RechargeLogic;
use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\UserLogic;
use app\api\logic\WebSettingLogic;
use app\api\service\UserMealService;
use app\api\validate\ConsumeRechargeValidate;
use app\common\service\ConfigService;
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
     * 列表
     *
     * @return Json
     */
    public function list(): Json
    {
        $params = (new ConsumeRechargeValidate())->get()->goCheck('list', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal']
        ]);

        try {
            switch ($params['type']) {
                case 'mobile':
                    $type = 1;
                    break;

                case 'electricity':
                    $type = 2;
                    break;

                case 'quickly':
                    $type = 3;
                    break;

                case 'card':
                    $type = 4;
                    break;

                default:
                    return $this->fail('参数异常');
            }

            $statusParams = $params['status'] ?? '';
            switch ($statusParams) {
                case 0:
                    $status = null;
                    break;

                case 1:
                    $status = 2;
                    break;

                case 2:
                    $status = 3;
                    break;

                case 3:
                    $status = 4;
                    break;

                default:
                    return $this->fail('参数异常');
            }

            $search = $params['search'] ?? '';

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $list = ConsumeRechargeLogic::list($this->userId, $type, $status, $search);
            if ($list === false) {
                return $this->fail('系统异常，请联系客服');
            }

            $areaData = Config::get('project.area');

            $newData = [];
            foreach ($list as $value) {

                $accountShow = $value['account'];
                $accountNameShow = '';
                if ($value['type'] == 1 || $value['type'] == 3) {
                    switch ($value['account_type']) {
                        case 1:
                            $accountNameShow = '移动';
                            break;

                        case 2:
                            $accountNameShow = '联通';
                            break;

                        case 3:
                            $accountNameShow = '电信';
                            break;

                        case 4:
                            $accountNameShow = '虚拟';
                            break;
                    }
                } elseif ($value['type'] == 2) {
                    $accountNameShow = $areaData[$value['name_area']] ?? '';
                } elseif ($value['type'] == 4) {
                    $accountShow = $value['name_area'];
                }

                $tmpData = [
                    'id' => $value['id'],
                    'sn' => $value['sn'],
                    'account' => $accountShow,
                    'account_name' => $accountNameShow,
                    'price' => $value['recharge_price'],
                    'up_price' => $value['recharge_up_price'] ?? '0.00',
                    'down_price' => $value['recharge_down_price'] ?? '0.00',
                    'balances_price' => $value['balances_price'] ?? '0.00',
                    'pay_price' => $value['pay_price'],
                    'status' => $value['status'],
                    'type' => $value['type'],
                    'time' => $value['create_time']
                ];

                $newData[] = $tmpData;
            }

            $queryPhonePrice = WebSettingLogic::getQueryPhone();
            $queryElectricityPrice = WebSettingLogic::getQueryElectricity();

            return $this->data([
                'list' => $newData,
                'query_p' => $queryPhonePrice,
                'query_e' => $queryElectricityPrice
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 详情
     *
     * @return Json
     */
    public function info(): Json
    {
        $params = (new ConsumeRechargeValidate())->get()->goCheck('id', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal']
        ]);

        try {
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('更新失败，获取不到该订单');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('更新失败，获取不到该订单');
            }

            $rate = WebSettingLogic::getReferenceRate();

            $areaData = Config::get('project.area');
            $operatorConf = Config::get('project.area');

            return $this->data([
                'id' => $info['id'],
                'sn' => $info['sn'],
                'account' => $info['account'],
                'account_name' => $info['name_area'],
                'area' => $info['type'] == 2 ? ($areaData[$info['name_area']] ?? '') : '',
                'operator' => $info['type'] == 1 ? ($operatorConf[$info['account_type']] ?? '') : '',
                'price' => $info['recharge_price'],
                'up_price' => $info['recharge_up_price'],
                'down_price' => $info['recharge_down_price'] ?: $info['recharge_up_price'],
                'balances_price' => $info['balances_price'] ?: 0,
                'meal_discount' => preg_replace('/\.?0*$/', '', $info['meal_discount']),
                'pay_price' => $info['pay_price'],
                'distance_price' => bcsub($info['pay_price'], bcmul($info['pay_price'], bcdiv($info['meal_discount'], 10, 2), 2), 2),
                'hl' => $rate,
                'us_price' => !empty($rate) ? bcdiv($info['pay_price'], $rate, 2) : '',
                'status' => $info['status'],
                'type' => $info['type'],
                'time' => $info['create_time']
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 数量
     *
     * @return Json
     */
    public function everyCount(): Json
    {
        try {

            $data = ConsumeRechargeLogic::groupCount($this->userId);

            $newData = [
                'pi' => 0,
                'ps' => 0,
                'pf' => 0,
                'ei' => 0,
                'es' => 0,
                'ef' => 0,
                'ki' => 0,
                'ks' => 0,
                'kf' => 0,
                'ci' => 0,
                'cs' => 0,
                'cf' => 0,
            ];

            foreach ($data as $value) {
                if ($value['type'] == 1) {
                    if ($value['status'] == 2) {
                        $newData['pi'] = $value['cou'];
                    } elseif ($value['status'] == 3) {
                        $newData['ps'] = $value['cou'];
                    } elseif ($value['status'] == 4) {
                        $newData['pf'] = $value['cou'];
                    }
                } elseif ($value['type'] == 2) {
                    if ($value['status'] == 2) {
                        $newData['ei'] = $value['cou'];
                    } elseif ($value['status'] == 3) {
                        $newData['es'] = $value['cou'];
                    } elseif ($value['status'] == 4) {
                        $newData['ef'] = $value['cou'];
                    }
                } elseif ($value['type'] == 3) {
                    if ($value['status'] == 2) {
                        $newData['ki'] = $value['cou'];
                    } elseif ($value['status'] == 3) {
                        $newData['ks'] = $value['cou'];
                    } elseif ($value['status'] == 4) {
                        $newData['kf'] = $value['cou'];
                    }
                } elseif ($value['type'] == 4) {
                    if ($value['status'] == 2) {
                        $newData['ci'] = $value['cou'];
                    } elseif ($value['status'] == 3) {
                        $newData['cs'] = $value['cou'];
                    } elseif ($value['status'] == 4) {
                        $newData['cf'] = $value['cou'];
                    }
                }
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-everyCount Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

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

        try {
            return $this->commonRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-phoneRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('electricityRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        try {
            return $this->commonRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-electricityRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 话费快充
     *
     * @return Json
     */
    public function quicklyRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('phoneRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 3
        ]);

        try {
            return $this->commonRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-quicklyRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * @param $params
     * @return Json
     * @throws Exception
     */
    private function commonRecharge($params): Json
    {
        $newParams = [
            'user_id' => $params['user_id'],
            'account' => '',
            'account_type' => null,
            'name_area' => '',
            'recharge_price' => $params['money'],
            'meal_id' => $params['meal_id'],
            'meal_discount' => $params['meal_discount'] ?? '',
            'pay_price' => null,
            'rate' => null,
            'type' => $params['type']
        ];

        // 验证数据
        if ($newParams['type'] == 1 || $newParams['type'] == 3) {
            if (!isset($params['name'])) {
                $params['name'] = '';
            }
            if (mb_strlen($params['name']) > 30) {
                return $this->fail('机主姓名不能超过30个字符');
            }

            $newParams['account'] = $params['phone'];
            $newParams['name_area'] = $params['name'];

        } elseif ($newParams['type'] == 2) {
            if (strlen($params['number']) > 30) {
                return $this->fail('户号不能超过30个字符');
            }

            $newParams['account'] = $params['number'];
            $newParams['name_area'] = $params['area'];
        }

        // 验证用户
        $userInfo = UserLogic::info($this->userId);
        if (empty($userInfo)) {
            return $this->fail('当前用户不可用，请联系客服');
        }
        if ($userInfo['is_disable'] == 1) {
            return $this->fail('当前用户账号异常，请联系客服');
        }

        // 获取汇率
        $rate = ConfigService::get('website', 'reference_rate', '');
        if (empty($rate)) {
            return $this->fail('未获取到汇率');
        }

        $newParams['rate'] = $rate;

        // 获取优惠配置
        $mealInfo = (new UserMealService())->getMealInfo($newParams['meal_id'], $this->userId, $rate);
        if ($mealInfo['type'] != $newParams['type']) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $newParams['recharge_price'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $newParams['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入充值页面');
        }

        $newParams['pay_price'] = $mealInfo['discounted_price'];

        // 比较用户余额
        if (bccomp($userInfo['user_money'], $newParams['pay_price'], 2) < 0) {
            return $this->fail('余额不足，请前往充值页面进行充值');
        }

        if ($newParams['type'] == 1 || $newParams['type'] == 3) {

            // 获取实时话费余额
            $requestData = (new ConsumeRechargeService())->getPhoneBalance($newParams['account']);
            if (empty($requestData)) {
                return $this->fail('暂不支持的手机号充值');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
            }

            $newParams['recharge_up_price'] = $requestData['cur_fee'];
            $newParams['account_type'] = $requestData['isp_id'];

        } elseif ($newParams['type'] == 2) {

            // 获取实时电费余额
            $requestData = (new ConsumeRechargeService())->getElectricityBalance($newParams['account'], ($newParams['name_area'] + 1));
            if (empty($requestData)) {
                return $this->fail('暂不支持的卡号充值');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
            }

            $newParams['recharge_up_price'] = $requestData['real_balance'];
        }

        $result = ConsumeRechargeLogic::recharge($newParams);
        if (!$result) {
            return $this->fail(ConsumeRechargeLogic::getError());
        }

        return $this->data([
            'msg' => '充值成功'
        ]);
    }

    /**
     * 礼品卡充值
     *
     * @return Json
     * @throws Exception
     */
    public function cardRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('cardRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 4
        ]);

        try {

            // 验证数据
            if (!isset($params['name'])) {
                $params['name'] = '';
            }
            if (mb_strlen($params['name']) > 30) {
                return $this->fail('卡名不能超过30个字符');
            }

            $newParams = [
                'user_id' => $params['user_id'],
                'account' => '',
                'name_area' => $params['name'],
                'recharge_price' => $params['buy_price'],
                'meal_discount' => $params['discount'] ?? '',
                'pay_price' => $params['pay_price'],
                'type' => $params['type']
            ];

            unset($params);

            // 验证用户
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 获取优惠配置
            $discount = ConfigService::get('website', 'card_discount', '');
            if (empty($discount) || $discount >= 10 || $discount <= 0) {
                $discount = '';
            }
            if ($discount != $newParams['meal_discount']) {
                return $this->fail('充值折扣发生变化，请重新进入充值页面');
            }

            $computePrice = $newParams['recharge_price'];
            if ($discount !== '') {
                $computePrice = number_format(bcmul($newParams['recharge_price'], bcdiv($discount, 10, 3), 3), 2);
            }
            if (bccomp($computePrice, $newParams['pay_price'], 2) != 0) {
                return $this->fail('充值实付金额发生变化，请联系客服人员');
            }

            // 比较用户余额
            if (bccomp($userInfo['user_money'], $newParams['pay_price'], 2) < 0) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            $result = ConsumeRechargeLogic::recharge($newParams);
            if (!$result) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }

            return $this->data([
                'msg' => '充值成功'
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-cardRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('batchPhoneRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 1
        ]);

        try {
            return $this->commonBatchRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-batchPhoneRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('batchElectricityRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 2
        ]);

        try {
            return $this->commonBatchRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-batchElectricityRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量话费快充
     *
     * @return Json
     */
    public function batchQuicklyRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('batchPhoneRecharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
            'type' => 3
        ]);

        try {
            return $this->commonBatchRecharge($params);
        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-batchQuicklyRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量充值
     *
     * @param $params
     * @return Json
     * @throws Exception
     */
    public function commonBatchRecharge($params): Json
    {
        $userInfo = UserLogic::info($this->userId);
        if (empty($userInfo)) {
            return $this->fail('当前用户不可用，请联系客服');
        }
        if ($userInfo['is_disable'] == 1) {
            return $this->fail('当前用户账号异常，请联系客服');
        }

        // 获取汇率
        $rate = ConfigService::get('website', 'reference_rate', '');
        if (empty($rate)) {
            return $this->fail('未获取到汇率');
        }

        // 获取设定的充值信息
        $mealInfo = (new UserMealService())->getMealInfo($params['meal_id'], $this->userId, $rate);
        if ($mealInfo['type'] != $params['type']) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['price'], $params['money'], 2) != 0) {
            return $this->fail('充值信息发生变化，请重新进入充值页面');
        }
        if (bccomp($mealInfo['real_discount'], $params['meal_discount'], 2) != 0) {
            return $this->fail('充值折扣发生变化，请重新进入充值页面');
        }

        // 批量数据
        $batchData = explode(PHP_EOL, $params['batch_data']);

        // 比较用户余额
        $total = bcmul($mealInfo['discounted_price'], count($batchData), 2);
        if (bccomp($userInfo['user_money'], $total, 2) < 0) {
            return $this->fail('余额不足，请前往充值页面进行充值');
        }

        $areaData = array_flip(Config::get('project.area'));

        $msg = '';
        $haveFail = false;
        foreach ($batchData as $value) {
            $tmpData = explode(' ', $value);
            if (empty($tmpData[0])) {
                return $this->fail('请正确输入号码');
            }

            $tmpParams = [
                'user_id' => $params['user_id'],
                'account' => $tmpData[0],
                'account_type' => null,
                'name_area' => '',
                'recharge_price' => $params['money'],
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'pay_price' => $mealInfo['discounted_price'],
                'rate' => $rate,
                'type' => $params['type'],
                'recharge_up_price' => 0
            ];

            $name = $tmpData[1] ?? '';

            if ($params['type'] == 1 || $params['type'] == 3) {
                if (!preg_match("/^1[3456789]\d{9}$/", $tmpParams['account'])) {
                    $msg .= $value . ' 请正确输入手机号' . PHP_EOL;
                    continue;
                }
                if (mb_strlen($name) > 30) {
                    $msg .= $value . ' 机主姓名不能超过30个字符' . PHP_EOL;
                    continue;
                }

                // 获取实时话费余额
                $requestData = (new ConsumeRechargeService())->getPhoneBalance($tmpParams['account']);
                if (empty($requestData)) {
                    $msg .= $value . ' 暂不支持的手机号充值' . PHP_EOL;
                }
                if (!$requestData['is_success']) {
                    $msg .= $value . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值') . PHP_EOL;
                }

                $tmpParams['name_area'] = $name;
                $tmpParams['account_type'] = $requestData['isp_id'];
                $tmpParams['recharge_up_price'] = $requestData['cur_fee'];

            } elseif ($params['type'] == 2) {
                if (strlen($tmpParams['account']) > 30) {
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

                $tmpParams['name_area'] = $areaData[$name];

                // 获取实时电费余额
                $requestData = (new ConsumeRechargeService())->getElectricityBalance($tmpParams['account'], ($tmpParams['name_area'] + 1));
                if (empty($requestData)) {
                    $msg .= $value . ' 暂不支持的户号充值' . PHP_EOL;
                    continue;
                }
                if (!$requestData['is_success']) {
                    $msg .= $value . (!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的户号充值') . PHP_EOL;
                    continue;
                }

                $tmpParams['recharge_up_price'] = $requestData['real_balance'];
            }

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
     * 取消充值
     *
     * @return Json
     */
    public function cancelRecharge(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('id', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal']
        ]);

        try {
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $res = ConsumeRechargeLogic::cancel($params['id'], $this->userId);
            if (!$res) {
                return $this->fail(RechargeLogic::getError());
            }

            return $this->success('取消成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-cancelRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new ConsumeRechargeValidate())->post()->goCheck('id', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal']
        ]);

        try {
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $info = ConsumeRechargeLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('更新失败，获取不到该订单');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('更新失败，获取不到该订单');
            }
            if ($info['status'] != 3) {
                return $this->fail('更新失败，订单未充值成功');
            }

            if ($info['type'] == 1 || $info['type'] == 3) {
                $queryPrice = WebSettingLogic::getQueryPhone();
                if ($queryPrice === '') {
                    return $this->fail('系统未设置查询，请联系客服');
                }

                // 比较用户余额
                if (bccomp($userInfo['user_money'], $queryPrice, 2) != 1) {
                    return $this->fail('余额不足，请前往充值页面进行充值');
                }

                $requestData = (new ConsumeRechargeService())->getPhoneBalance($info['phone']);
                if (empty($requestData)) {
                    return $this->fail('暂不支持的手机号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号充值');
                }

                $price = $requestData['cur_fee'];
            } elseif ($info['type'] == 2) {
                if (!is_numeric($info['name_area'])) {
                    return $this->fail('暂不支持的更新余额');
                }

                $queryPrice = WebSettingLogic::getQueryElectricity();
                if ($queryPrice === '') {
                    return $this->fail('系统未设置查询，请联系客服');
                }

                // 比较用户余额
                if (bccomp($userInfo['user_money'], $queryPrice, 2) != 1) {
                    return $this->fail('余额不足，请前往充值页面进行充值');
                }

                $requestData = (new ConsumeRechargeService())->getElectricityBalance($info['account'], ($info['name_area'] + 1));
                if (empty($requestData)) {
                    return $this->fail('暂不支持的卡号充值');
                }
                if (!$requestData['is_success']) {
                    return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的卡号充值');
                }

                $price = $requestData['real_balance'];
            }

            $res = ConsumeRechargeLogic::setBalance($info['id'], $price, $queryPrice);
            if (!$res) {
                return $this->fail(RechargeLogic::getError());
            }

            return $this->success('更新成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeRechargeController-genBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
