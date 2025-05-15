<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\UserMealDiscount;
use think\Model;

/**
 * 用户充值金额
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class UserMealLogic extends BaseLogic
{
    /**
     * @param $userId
     * @param $mealId
     * @return UserMealDiscount|array|Model|null
     */
    public function getMealInfo($userId, $mealId): UserMealDiscount|array|Model|null
    {
        try {
            return UserMealDiscount::where(['user_id' => $userId, 'meal_id' => $mealId])
                ->field('id,discount')
                ->find();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }
}
