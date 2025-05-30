<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\UserMealDiscount;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use think\Model;
use Exception;

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
     * @param $discount
     * @return bool
     */
    public function addData($userId, $mealId, $discount): bool
    {
        try {
            $billData = [
                'user_id' => $userId,
                'meal_id' => $mealId,
                'discount' => $discount
            ];

            $res = UserMealDiscount::create($billData);
            if (empty($res['id'])) {
                self::setError('保存失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMealLogic-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @param $id
     * @param $discount
     * @return bool
     */
    public function updateData($id, $discount): bool
    {
        try {
            $res = UserMealDiscount::where('id', $id)
                ->update([
                    'discount' => $discount
                ]);

            if (empty($res['id'])) {
                self::setError('保存失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMealLogic-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @param $userId
     * @return UserMealDiscount|array|Model|null
     */
    public function getMealList($userId): UserMealDiscount|array|Model|null
    {
        try {
            return UserMealDiscount::where('user_id', $userId)
                ->field('id,meal_id,discount')
                ->select()
                ->toArray();
        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMealLogic-getMealList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return [];
        }
    }

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
        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMealLogic-getMealInfo Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return [];
        }
    }
}
