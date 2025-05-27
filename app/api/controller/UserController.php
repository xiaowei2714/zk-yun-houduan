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


use app\api\logic\ConsumeQueryLogic;
use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\MealLogic;
use app\api\logic\SubstationLogic;
use app\api\logic\UserLogic;
use app\api\logic\UserMealLogic;
use app\api\logic\UserMoneyLogLogic;
use app\api\validate\PasswordValidate;
use app\api\validate\SetUserInfoValidate;
use app\api\validate\UserValidate;
use app\common\model\SetMeal;
use app\common\model\user\User;
use app\common\model\UserMealDiscount;
use app\common\model\UserMoneyLog;
use app\common\service\FileService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 用户控制器
 * Class UserController
 * @package app\api\controller
 */
class UserController extends BaseApiController
{
    public array $notNeedLogin = ['resetPassword'];


    /**
     * @notes 获取个人中心
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 18:19
     */
    public function center()
    {
        $data = UserLogic::center($this->userInfo);
        return $this->success('', $data);
    }


    /**
     * @notes 获取个人信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/20 19:46
     */
    public function info()
    {
        $result = UserLogic::info($this->userId);
        return $this->data($result);
    }


    /**
     * @notes 重置密码
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/16 18:06
     */
    public function resetPassword()
    {
        $params = (new PasswordValidate())->post()->goCheck('resetPassword');
        $result = UserLogic::resetPassword($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }


    /**
     * @notes 修改密码
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/20 19:16
     */
    public function changePassword()
    {
        $params = (new PasswordValidate())->post()->goCheck('changePassword');
        $result = UserLogic::changePassword($params, $this->userId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }


    /**
     * @notes 获取小程序手机号
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 16:46
     */
    public function getMobileByMnp()
    {
        $params = (new UserValidate())->post()->goCheck('getMobileByMnp');
        $params['user_id'] = $this->userId;
        $result = UserLogic::getMobileByMnp($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('绑定成功', [], 1, 1);
    }


    /**
     * @notes 编辑用户信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 17:01
     */
    public function setInfo()
    {
        $params = (new SetUserInfoValidate())->post()->goCheck(null, ['id' => $this->userId]);
        $result = UserLogic::setInfo($this->userId, $params);
        if (false === $result) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 绑定/变更 手机号
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 17:29
     */
    public function bindMobile()
    {
        $params = (new UserValidate())->post()->goCheck('bindMobile');
        $params['user_id'] = $this->userId;
        $result = UserLogic::bindMobile($params);
        if ($result) {
            return $this->success('绑定成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }

    /**
     * @return Json
     */
    public function countMoney(): Json
    {
        try {
            $newData = [
                'user_money' => 0,
                'phone_today_money' => 0,
                'phone_money' => 0,
                'electricity_today_money' => 0,
                'electricity_money' => 0,
                'query_today_money' => 0,
                'query_money' => 0,
                'next_today_money' => 0,
                'next_money' => 0,
            ];

            // 用户详情
            $userInfo = UserLogic::info($this->userId);

            $tmp = $userInfo['user_money'] ?? 0;
            $newData['user_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            $tmp = $userInfo['total_award_price'] ?? 0;
            $newData['next_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            // 话费、电费 总数
            $rechargeData = ConsumeRechargeLogic::groupSumMoney($this->userId);
            $rechargeData = array_column($rechargeData, 'pay_price', 'type');

            $tmp = $rechargeData[1] ?? 0;
            $newData['phone_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            $tmp = $rechargeData[2] ?? 0;
            $newData['electricity_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            $today = strtotime(date('Y-m-d 00:00:00'));

            // 话费、电费 当天
            $rechargeData = ConsumeRechargeLogic::groupSumMoney($this->userId, $today);
            $rechargeData = array_column($rechargeData, 'pay_price', 'type');

            $tmp = $rechargeData[1] ?? 0;
            $newData['phone_today_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            $tmp = $rechargeData[2] ?? 0;
            $newData['electricity_today_money'] = substr($tmp, 0, strpos($tmp, '.') + 4);

            // 查询
            $querySum = ConsumeQueryLogic::groupSumMoney($this->userId);
            $newData['query_money'] = substr($querySum, 0, strpos($querySum, '.') + 4);

            $querySum = ConsumeQueryLogic::groupSumMoney($this->userId, $today);
            $newData['query_today_money'] = substr($querySum, 0, strpos($querySum, '.') + 4);

            // 返佣
            $cashbackSum = UserMoneyLogLogic::getUserCashback($this->userId, $today);
            $newData['next_today_money'] = substr($cashbackSum, 0, strpos($cashbackSum, '.') + 4);

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-UserController-getCount Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 获取好友列表
     *
     * @return Json
     */
    public function getFriendList(): Json
    {
        try {

            $type = $this->request->get('type');

            $data = UserLogic::getDataByPUserId($this->userId, $type);
            if ($data === false) {
                return $this->fail(UserLogic::getError());
            }

            $nUserIds = array_column($data, 'id');

            // 获取今日收益
            $startTime = strtotime(date('Y-m-d 00:00:00'));
            $endTime = strtotime(date('Y-m-d 23:59:59'));

            // 获取用户返佣
            $todayGroupSum = UserMoneyLogLogic::groupSum($this->userId, $nUserIds, $startTime, $endTime);
            $todayGroupSum = array_column($todayGroupSum, 'change_money', 'n_user_id');
            $totalGroupSum = UserMoneyLogLogic::groupSum($this->userId, $nUserIds);
            $totalGroupSum = array_column($totalGroupSum, 'change_money', 'n_user_id');

            $todayTotal = 0;
            $total = 0;
            $newData = [];
            foreach ($data as $value) {

                $tmpTodayTotal = $todayGroupSum[$value['id']] ?? '0.00';
                $tmpTotal = $totalGroupSum[$value['id']] ?? '0.00';

                $newData[] = [
                    'id' => $value['id'],
                    'nickname' => $value['nickname'],
                    'avatar' => FileService::getFileUrl($value['avatar']),
                    'xjTodayTotal' => $tmpTodayTotal,
                    'xjTotal' => $tmpTotal
                ];

                $todayTotal = bcadd($todayTotal, $tmpTodayTotal, 3);
                $total = bcadd($total, $tmpTotal, 3);
            }

            return $this->success('', [
                'list' => $newData,
                'todayTotal' => $todayTotal,
                'total' => $total
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-UserController-getFriendList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 获取用户折扣
     *
     * @return Json
     */
    public function getFriendMealList(): Json
    {
        try {
            $userId = $this->request->get('user_id');
            if (empty($userId)) {
                return $this->fail('参数缺失');
            }

            // 查看是否开通分站
            $substationInfo = SubstationLogic::info($this->userId);
            if (empty($substationInfo['id'])) {
                return $this->fail('没有权限，请开通分站');
            }
            if ($substationInfo['status'] != 1) {
                return $this->fail('没有权限，请联系客服人员');
            }

            // 查询指定用户的信息
            $curUserInfo = UserLogic::getPreviousUserId($userId);
            if (empty($curUserInfo['id'])) {
                return $this->fail('用户不存在');
            }
            if ($curUserInfo['p_first_user_id'] != $this->userId) {
                return $this->fail('未绑定直邀好友关系');
            }

            // 获取套餐列表
            $mealList = (new MealLogic())->getMealList();

            $newData = [];
            if (empty($mealList)) {
                return $this->success('', $newData);
            }

            // 获取用户列表
            $userMealList = (new UserMealLogic())->getMealList($userId);
            if (!empty($userMealList)) {
                $userMealList = array_column($userMealList, null, 'meal_id');
            }

            foreach ($mealList as $value) {
                $typeShow = '';
                if ($value['type'] == 1) {
                    $typeShow = '话费';
                } elseif ($value['type'] == 2) {
                    $typeShow = '电费';
                } elseif ($value['type'] == 3) {
                    $typeShow = '话费快充';
                }

                $newData[] = [
                    'id' => $value['id'],
                    'name' => $value['name'],
                    'type' => $typeShow,
                    'discount' => isset($userMealList[$value['id']]) ? $userMealList[$value['id']]['discount'] : $value['discount']
                ];
            }

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-UserController-getFriendMealList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * @return Json
     */
    public function setFriendDiscount(): Json
    {
        $params = (new UserValidate())->post()->goCheck('setFriendDiscount');

        try {
            if (empty($params['user_id']) || empty($params['list'])) {
                return $this->fail('参数错误');
            }

            // 查看是否开通分站
            $substationInfo = SubstationLogic::info($this->userId);
            if (empty($substationInfo['id'])) {
                return $this->fail('没有权限，请开通分站');
            }
            if ($substationInfo['status'] != 1) {
                return $this->fail('没有权限，请联系客服人员');
            }

            $paramsList = [];
            $params['list'] = json_decode($params['list'], true);
            foreach ($params['list'] as $value) {
                if (empty($value['id']) || empty($value['discount'])) {
                    return $this->fail('参数错误');
                }
                if ($value['discount'] >= 10 || $value['discount'] <= 0) {
                    return $this->fail('折扣参数错误');
                }

                $paramsList[$value['id']] = $value['discount'];
            }

            $curUserInfo = UserLogic::getPreviousUserId($params['user_id']);
            if (empty($curUserInfo['id'])) {
                return $this->fail('用户不存在');
            }
            if ($curUserInfo['p_first_user_id'] != $this->userId) {
                return $this->fail('未绑定直邀好友关系');
            }

            // 获取套餐列表
            $mealList = (new MealLogic())->getMealList();
            if (empty($mealList)) {
                return $this->fail('没有可用套餐');
            }

            $userMealLogic = new UserMealLogic();

            // 获取用户列表
            $userMealList = $userMealLogic->getMealList($params['user_id']);
            if (!empty($userMealList)) {
                $userMealList = array_column($userMealList, null, 'meal_id');
            }

            $failMsg = '';
            foreach ($mealList as $value) {
                if (!isset($paramsList[$value['id']])) {
                    continue;
                }

                $typeShow = '';
                if ($value['type'] == 1) {
                    $typeShow = '话费';
                } elseif ($value['type'] == 2) {
                    $typeShow = '电费';
                } elseif ($value['type'] == 3) {
                    $typeShow = '话费快充';
                }

                if (!isset($userMealList[$value['id']])) {
                    $res = $userMealLogic->addData($params['user_id'], $value['id'], $paramsList[$value['id']]);
                    if (!$res) {
                        $failMsg .= $value['name'] . '（' . $typeShow . '）更改失败：' . UserMealLogic::getError();
                    }

                    continue;
                }

                $userMealId = $userMealList[$value['id']]['id'];
                $userMealValue = $userMealList[$value['id']]['discount'];
                if (bccomp($paramsList[$value['id']], $userMealValue, 2) == 0) {
                    continue;
                } else {
                    $res = $userMealLogic->updateData($userMealId, $paramsList[$value['id']]);
                    if (!$res) {
                        $failMsg .= $value['name'] . '（' . $typeShow . '）更改失败：' . UserMealLogic::getError();
                    }
                }
            }

            if ($failMsg != '') {
                return $this->fail(UserLogic::getError());
            }

            return $this->success('更新成功');

        } catch (Exception $e) {
            Log::record('Exception: api-UserController-setFriendDiscount Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
