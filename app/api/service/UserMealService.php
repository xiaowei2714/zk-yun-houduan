<?php

namespace app\api\service;

use app\api\logic\MealLogic;
use app\api\logic\UserMealLogic;

class UserMealService
{
    /**
     * @param $type
     * @param $userId
     * @param $rate
     * @return array
     */
    public function getMealList($type, $userId, $rate): array
    {
        $mealList = (new MealLogic())->getMealListByType($type);

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
            $realDiscount = bcdiv($tmpData['real_discount'], 10, 4);
            $discountPrice = bcmul($tmpData['price'], $realDiscount, 4);
            $realPrice = bcdiv($discountPrice, $rate, 34);

            $tmpData['discounted_price'] = number_format($realPrice, 3);

            // 计算优惠价格
            $subPrice = bcsub($tmpData['price'], $discountPrice, 4);
            $subRealPrice = bcdiv($subPrice, $rate, 4);
            $tmpData['price2'] = number_format($subRealPrice, 3);

            $newData[] = $tmpData;
        }

        return $newData;
    }

    /**
     * @param $id
     * @param $userId
     * @param $rate
     * @return array
     */
    public function getMealInfo($id, $userId, $rate): array
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
            'discounted_price' => 0,
            'day_astrict_num' => $mealInfo['day_astrict_num'],
            'meanwhile_order_num' => $mealInfo['meanwhile_order_num'],
            'operator'  => json_decode($mealInfo['operator'], true),
            'forbid_buy_city'  => !empty($mealInfo['forbid_buy_city']) ? explode(',', $mealInfo['forbid_buy_city']) : [],
            'allow_buy_nums'  => !empty($mealInfo['allow_buy_nums']) ? explode(',', $mealInfo['allow_buy_nums']) : [],
            'forbid_buy_nums'  => !empty($mealInfo['forbid_buy_nums']) ? explode(',', $mealInfo['forbid_buy_nums']) : [],
        ];

        $userMealInfo = (new UserMealLogic())->getMealInfo($userId, $mealInfo['id']);
        if (!empty($userMealInfo->id)) {
            $data['hava_user_discount'] = true;
            $data['user_discount'] = $userMealInfo->discount;
            $data['real_discount'] = $userMealInfo->discount;
        }

        // 计算折扣价
        $realDiscount = bcdiv($data['real_discount'], 10, 4);
        $discountPrice = bcmul($data['price'], $realDiscount, 4);
        $realPrice = bcdiv($discountPrice, $rate, 34);

        $data['discounted_price'] = number_format($realPrice, 3);

        return $data;
    }

}
