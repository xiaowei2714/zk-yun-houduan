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
namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\user\UserLists;
use app\adminapi\lists\user\UserMoneyLists;
use app\adminapi\logic\user\UserLogic;
use app\adminapi\validate\user\AdjustUserMoney;
use app\adminapi\validate\user\UserValidate;
use app\common\model\SetMeal;
use app\common\model\user\User;
use app\common\model\UserMealDiscount;
use think\facade\Log;
use Exception;
use think\response\Json;

/**
 * 用户控制器
 * Class UserController
 * @package app\adminapi\controller\user
 */
class UserController extends BaseAdminController
{

    /**
     * @notes 用户列表
     * @return Json
     * @author 段誉
     * @date 2022/9/22 16:16
     */
    public function lists()
    {
        return $this->dataLists(new UserLists());
    }

    /**
     * 保存折扣
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveMealList()
    {
        $list = $this->request->post('list');
        foreach ($list as $v) {
            $userMealDiscount = UserMealDiscount::where(['meal_id' => $v['id'], 'user_id' => $v['user_id']])->find();
            if (!$userMealDiscount) {
                UserMealDiscount::create([
                    'meal_id' => $v['id'],
                    'user_id' => $v['user_id'],
                    'discount' => $v['discount']
                ]);
            } else {
                $userMealDiscount->discount = $v['discount'];
                $userMealDiscount->save();
            }
        }
        return $this->success();
    }

    /**
     * 获取用户折扣
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMealList()
    {
        $userId = $this->request->get('user_id');
        if (empty($userId)) {
            return $this->fail('参数缺失');
        }
        $user = User::find($userId);
        if (empty($user)) {
            return $this->fail('用户不存在');
        }
        // 获取套餐列表
        $meal_list = SetMeal::order('type', 'asc')->order('sort', 'desc')->field('id,name,type,discount')->select()->toArray();
        foreach ($meal_list as &$item) {
            $userMeal = UserMealDiscount::where(['user_id' => $userId, 'meal_id' => $item['id']])->find();
            if ($userMeal) {
                $item['discount'] = $userMeal->discount;
            }
            $item['user_id'] = $userId;
        }
        return $this->success('', [
            'list' => $meal_list
        ]);
    }

    /**
     * @return Json
     */
    public function searchUser(): Json
    {
        try {
            $search = $this->request->get('search');
            if (empty($search)) {
                return $this->success('', []);
            }

            $data = UserLogic::searchData($search);
            if ($data === false) {
                return $this->fail(UserLogic::getError());
            }

            return $this->success('', $data);
        } catch (Exception $e) {
            Log::record('Exception: api-UserController-searchUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }


    /**
     * @notes 获取用户详情
     * @return Json
     * @author 段誉
     * @date 2022/9/22 16:34
     */
    public function detail()
    {
        $params = (new UserValidate())->goCheck('detail');
        $detail = UserLogic::detail($params['id']);
        return $this->success('', $detail);
    }


    /**
     * @notes 编辑用户信息
     * @return Json
     * @author 段誉
     * @date 2022/9/22 16:34
     */
    public function edit()
    {
        $params = (new UserValidate())->post()->goCheck('setInfo');
        UserLogic::setUserInfo($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 调整用户余额
     * @return Json
     * @author 段誉
     * @date 2023/2/23 14:33
     */
    public function adjustMoney()
    {
        $params = (new AdjustUserMoney())->post()->goCheck();
        $res = UserLogic::adjustUserMoney($params);
        if (true === $res) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail($res);
    }

    /**
     * 修改密码
     *
     * @return Json
     */
    public function resetPassword()
    {
        $params = (new UserValidate())->post()->goCheck('password');
        $res = UserLogic::resetPassword($params['id'], $params['password']);
        if ($res === true) {
            return $this->success('操作成功', [], 1, 1);
        }

        return $this->fail(UserLogic::getError());
    }

    public function userMoneyLog()
    {
        (new UserValidate())->get()->goCheck('userId');

        return $this->dataLists(new UserMoneyLists());
    }
}
