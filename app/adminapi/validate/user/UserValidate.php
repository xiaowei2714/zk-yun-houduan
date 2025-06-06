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
namespace app\adminapi\validate\user;


use app\common\model\user\User;
use app\common\validate\BaseValidate;

/**
 * 用户验证
 * Class UserValidate
 * @package app\adminapi\validate\user
 */
class UserValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require|checkUser',
        'user_id' => 'require|checkUser',
        'field' => 'require|checkField',
        'value' => 'require',
        'password' => 'require|length:6,20|alphaNum',
    ];

    protected $message = [
        'id.require' => '请选择用户',
        'user_id.require' => '请选择用户',
        'field.require' => '请选择操作',
        'value.require' => '请输入内容',
        'password.require' => '请输入密码',
        'password.length' => '密码须在6-25位之间',
        'password.alphaNum' => '密码须为字母数字组合',
    ];


    /**
     * @notes 详情场景
     * @return UserValidate
     * @author 段誉
     * @date 2022/9/22 16:35
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @return UserValidate
     */
    public function sceneUserId()
    {
        return $this->only(['user_id']);
    }

    /**
     * 密码
     *
     * @return UserValidate
     */
    public function scenePassword(): UserValidate
    {
        return $this->only(['id', 'password']);
    }

    /**
     * @notes 用户信息校验
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/22 17:03
     */
    public function checkUser($value, $rule, $data)
    {
        $userIds = is_array($value) ? $value : [$value];

        foreach ($userIds as $item) {
            if (!User::find($item)) {
                return '用户不存在！';
            }
        }
        return true;
    }


    /**
     * @notes 校验是否可更新信息
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2022/9/22 16:37
     */
    public function checkField($value, $rule, $data)
    {
        $allowField = ['account', 'sex', 'mobile', 'real_name', 'ad_perm'];

        if (!in_array($value, $allowField)) {
            return '用户信息不允许更新';
        }

        switch ($value) {
            case 'account':
                //验证手机号码是否存在
                $account = User::where([
                    ['id', '<>', $data['id']],
                    ['account', '=', $data['value']]
                ])->findOrEmpty();

                if (!$account->isEmpty()) {
                    return '账号已被使用';
                }
                break;

            case 'mobile':
                if (false == $this->validate($data['value'], 'mobile', $data)) {
                    return '手机号码格式错误';
                }

                //验证手机号码是否存在
                $mobile = User::where([
                    ['id', '<>', $data['id']],
                    ['mobile', '=', $data['value']]
                ])->findOrEmpty();

                if (!$mobile->isEmpty()) {
                    return '手机号码已存在';
                }
                break;
        }
        return true;
    }


}
