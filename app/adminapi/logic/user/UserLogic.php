<?php

namespace app\adminapi\logic\user;

use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use think\Model;
use think\facade\Config;
use Exception;

/**
 * 用户逻辑层
 * Class UserLogic
 * @package app\adminapi\logic\user
 */
class UserLogic extends BaseLogic
{
    /**
     * 汇总数据
     *
     * @param $createTime
     * @return false|int
     */
    public static function count($createTime = null)
    {
        try {
            if (!empty($createTime)) {
                return User::where('create_time', '>=', $createTime)->count();
            }

            return User::count();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @param $value
     * @return array|false
     */
    public static function searchData($value)
    {
        try {
            return User::where('nickname', 'like', '%' . $value . '%')->field('id,nickname')->select()->toArray();
        } catch (Exception $e) {
            Log::record('Exception: Sql-UserLogic-searchData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @param int $userId
     * @return User|array|mixed|Model
     */
    public static function info(int $userId)
    {
        $field = [
            'id', 'p_first_user_id', 'p_second_user_id', 'p_three_user_id'
        ];

        return User::where(['id' => $userId])->field($field)->findOrEmpty();
    }


    /**
     * @notes 用户详情
     * @param int $userId
     * @return array
     * @author 段誉
     * @date 2022/9/22 16:32
     */
    public static function detail(int $userId): array
    {
        $field = [
            'id', 'sn', 'account', 'nickname', 'avatar', 'real_name',
            'sex', 'mobile', 'create_time', 'login_time', 'channel',
            'user_money', 'ad_perm'
        ];

        $user = User::where(['id' => $userId])->field($field)
            ->findOrEmpty();

        $user['channel'] = UserTerminalEnum::getTermInalDesc($user['channel']);
        $user->sex = $user->getData('sex');
        return $user->toArray();
    }


    /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserInfo(array $params)
    {
        return User::update([
            'id' => $params['id'],
            $params['field'] => $params['value']
        ]);
    }


    /**
     * @notes 调整用户余额
     * @param array $params
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 14:25
     */
    public static function adjustUserMoney(array $params)
    {
        Db::startTrans();
        try {
            $user = User::find($params['user_id']);
            if (AccountLogEnum::INC == $params['action']) {

                //调整可用余额
                $user->user_money += $params['num'];
                $user->save();

                //记录日志
                $res = AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_INC_ADMIN,
                    AccountLogEnum::INC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );

                if (empty($res['id'])) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存记录日志异常');
                    self::setError('保存记录日志异常');
                    Db::rollback();
                    return false;
                }

                $userInfo = User::where('id', $params['user_id'])->find();

                // 流水
                $billData = [
                    'user_id' => $user->id,
                    'type' => 5,
                    'desc' => '后台充值余额',
                    'change_type' => 1,
                    'change_money' => $params['num'],
                    'changed_money' => $userInfo['user_money']
                ];

                $res = UserMoneyLog::create($billData);
                if (empty($res['id'])) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存流水异常');
                    self::setError('保存流水异常');
                    Db::rollback();
                    return false;
                }

                // 消息通知
                $noticeData = [
                    'user_id' => $user->id,
                    'title' => 'Y币充值成功提醒',
                    'content' => '后台于 ' . date('Y-m-d H:i:s') . ' 成功充值余额 ' . $params['num'] . ' Y币',
                    'scene_id' => 0,
                    'read' => 0,
                    'recipient' => 1,
                    'send_type' => 1,
                    'notice_type' => 1,
                    'type' => 3
                ];

                $res = NoticeRecord::create($noticeData);
                if (empty($res)) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存消息异常');
                    self::setError('保存消息异常');
                    Db::rollback();
                    return false;
                }

            } else {

                $user->user_money -= $params['num'];
                $user->save();

                //记录日志
                $res = AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );

                if (empty($res['id'])) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存记录日志异常');
                    self::setError('保存记录日志异常');
                    Db::rollback();
                    return false;
                }

                $userInfo = User::where('id', $params['user_id'])->find();

                // 流水
                $billData = [
                    'user_id' => $user->id,
                    'type' => 5,
                    'desc' => '后台扣除余额',
                    'change_type' => 2,
                    'change_money' => $params['num'],
                    'changed_money' => $userInfo['user_money']
                ];

                $res = UserMoneyLog::create($billData);
                if (empty($res['id'])) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存流水异常');
                    self::setError('保存流水异常');
                    Db::rollback();
                    return false;
                }

                // 消息通知
                $noticeData = [
                    'user_id' => $user->id,
                    'title' => 'Y币充值扣除提醒',
                    'content' => '后台于 ' . date('Y-m-d H:i:s') . ' 成功扣除余额 ' . $params['num'] . ' Y币',
                    'scene_id' => 0,
                    'read' => 0,
                    'recipient' => 1,
                    'send_type' => 1,
                    'notice_type' => 1,
                    'type' => 3
                ];

                $res = NoticeRecord::create($noticeData);
                if (empty($res)) {
                    Log::record('Error: UserLogic-adjustUserMoney 保存消息异常');
                    self::setError('保存消息异常');
                    Db::rollback();
                    return false;
                }
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    /**
     * @notes 重置登录密码
     * @param $id
     * @param $password
     * @return bool
     * @author 段誉
     * @date 2022/9/16 18:06
     */
    public static function resetPassword($id, $password)
    {
        try {
            // 重置密码
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($password, $passwordSalt);

            // 更新
            $res = User::where('id', $id)
                ->update([
                    'password' => $password
                ]);

            if (empty($res)) {
                self::setError('修改失败，不能与原密码一样');
                return false;
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
