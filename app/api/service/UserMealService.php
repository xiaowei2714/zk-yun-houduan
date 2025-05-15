<?php

namespace app\api\service;

use app\api\logic\MealLogic;
use app\api\logic\UserMealLogic;

class UserMealService
{
    /**
     * @param $type
     * @param $userId
     * @return array
     */
    public function getMealList($type, $userId): array
    {
        $mealList = (new MealLogic())->getMealList($type);

        $userMeal = new UserMealLogic();

        $newData = [];
        foreach ($mealList as $value) {
            $tmpData = [
                'id' => $value['id'],
                'name' => $value['name'],
                'type' => $value['type'],
                'discount' => $value['discount'],
                'price' => $value['price'],
                'hava_user_discount' => false,
                'user_discount' => 0,
                'real_discount' => $value['discount'],
                'discounted_price' => 0,
                'price2' => 0,
            ];

            $userMealInfo = $userMeal->getMealInfo($userId, $value['id']);
            if (!empty($userMealInfo->id)) {
                $tmpData['hava_user_discount'] = true;
                $tmpData['user_discount'] = $userMealInfo->discount;
                $tmpData['real_discount'] = $userMealInfo->discount;
            }

            // 计算折扣价
            $tmpData['discounted_price'] = number_format(bcmul($tmpData['price'], bcdiv($tmpData['real_discount'], 10, 3), 3), 2);

            // 计算优惠价格
            $tmpData['price2'] = number_format(bcsub($tmpData['price'], $tmpData['discounted_price'], 2), 2);

            $newData[] = $tmpData;
        }

        return $newData;
    }

    /**
     * @param $id
     * @param $userId
     * @return array
     */
    public function getMealInfo($id, $userId): array
    {
        $mealInfo = (new MealLogic())->getMealInfo($id);

        $data = [
            'id' => $mealInfo['id'],
            'name' => $mealInfo['name'],
            'type' => $mealInfo['type'],
            'discount' => $mealInfo['discount'],
            'price' => $mealInfo['price'],
            'hava_user_discount' => false,
            'user_discount' => 0,
            'real_discount' => $mealInfo['discount'],
            'discounted_price' => 0
        ];

        $userMealInfo = (new UserMealLogic())->getMealInfo($mealInfo['id'], $userId);
        if (!empty($userMealInfo->id)) {
            $data['hava_user_discount'] = true;
            $data['user_discount'] = $userMealInfo->discount;
            $data['real_discount'] = $userMealInfo->discount;
        }

        $data['discounted_price'] = number_format(bcmul($data['price'], bcdiv($data['real_discount'], 10, 3), 3), 2);

        return $data;
    }

}
