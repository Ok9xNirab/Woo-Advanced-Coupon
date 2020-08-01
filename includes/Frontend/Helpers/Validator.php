<?php

namespace WpAdroit\Wac_Coupon\Frontend\Helpers;

use WC_Coupon;
use WC_Discounts;

/**
 * Coupon Validator class
 */
class Validator
{

    static public function check($coupon, $post_id)
    {
        $result = self::wac_fiter_validate($coupon, $post_id) ? true : false;
        if ($result) {
            $result = self::wac_rules_validate($coupon, $post_id) ? self::wac_rules_validate($coupon, $post_id) : false;
        }
        return $result;
    }

    static public function basic_validate($coupon)
    {
        $coupon = new WC_Coupon($coupon);
        $discounts = new WC_Discounts(WC()->cart);
        $valid_response = $discounts->is_coupon_valid($coupon);
        if (
            is_wp_error($valid_response)
        ) {
            return false;
        } else {
            return true;
        }
    }

    static public function wac_fiter_validate($coupon, $post_id)
    {
        $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
        $result = true;
        if (empty($post_meta["list_id"])) {
            return $result;
        }

        $filters = get_post_meta($post_meta["list_id"], "wac_filters", true);

        foreach ($filters as $filter) {
            if ($filter["type"] == "all_products") {
                $result = true;
                break;
            } else {
                $products = WC()->cart->get_cart();
                $wac_products = [];
                $productLists = [];
                foreach ($filter["items"] as $filterItem) {
                    array_push($wac_products, $filterItem["value"]);
                }
                foreach ($products as $values) {
                    array_push($productLists, $values["product_id"]);
                }
                $productLists = array_map(function ($piece) {
                    return (string) $piece;
                }, $productLists);
                if ($filter["lists"] == "inList") {
                    foreach ($wac_products as $wac_product) {
                        foreach ($productLists as $productList) {
                            if ($wac_product != $productList) {
                                $result = false;
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($wac_products as $wac_product) {
                        $result = in_array($wac_product, $productLists);
                        if ($result == true) {
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }

    static function wac_rules_validate($coupon, $post_id)
    {
        $result = true;
        $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
        if (empty($post_meta["list_id"])) {
            return $result;
        } else {
            $rules = get_post_meta($post_meta["list_id"], "wac_coupon_rules", true);
            if ($rules["rules"] == null) {
                return $result;
            }
        }

        $relation = $rules["relation"];

        foreach ($rules["rules"] as $rule) {
            $operator = $rule["operator"];
            $value = $rule["item_count"];
            $calculate = $rule["calculate"];
            if ($rule["type"] == "cart_subtotal") {
                switch ($operator) {
                    case 'less_than':
                        $subtotal = WC()->cart->get_cart_subtotal();
                        if ($calculate == "from_cart") {
                            if (!($subtotal < $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $amount = self::wac_cart_filter_subtotal($post_meta["list_id"]);
                            if (!($amount < (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'less_than_or_equal':
                        $subtotal = WC()->cart->get_cart_subtotal();
                        if ($calculate == "from_cart") {
                            if (!($subtotal <= $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $amount = self::wac_cart_filter_subtotal($post_meta["list_id"]);
                            if (!($amount <= (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'greater_than':
                        $subtotal = WC()->cart->get_cart_subtotal();
                        if ($calculate == "from_cart") {
                            if (!($subtotal > $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $amount = self::wac_cart_filter_subtotal($post_meta["list_id"]);
                            if (!($amount > (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'greater_than_or_equal':
                        $subtotal = WC()->cart->get_cart_subtotal();
                        if ($calculate == "from_cart") {
                            if (!($subtotal >= $value)) {
                                $result = false;
                            }
                        } else if ($calculate >= "from_filter") {
                            $amount = self::wac_cart_filter_subtotal($post_meta["list_id"]);
                            if (!($amount < (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                }
                if ($result === true) {
                    if ($relation == "match_any") {
                        break;
                    }
                }
            } else if ($rule["type"] == "cart_line_items_count") {
                $line_total = count(WC()->cart->get_cart());
                switch ($operator) {
                    case 'less_than':
                        if ($calculate == "from_cart") {
                            if (!($line_total < $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $total = self::wac_cart_filter_line_total($post_meta["list_id"]);
                            if (!($total < (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'less_than_or_equal':
                        if ($calculate == "from_cart") {
                            if (!($line_total <= $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $total = self::wac_cart_filter_line_total($post_meta["list_id"]);
                            if (!($total <= (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'greater_than':
                        if ($calculate == "from_cart") {
                            if (!($line_total > $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $total = self::wac_cart_filter_line_total($post_meta["list_id"]);
                            if (!($total > (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                    case 'greater_than_or_equal':
                        if ($calculate == "from_cart") {
                            if (!($line_total >= $value)) {
                                $result = false;
                            }
                        } else if ($calculate == "from_filter") {
                            $total = self::wac_cart_filter_line_total($post_meta["list_id"]);
                            if (!($total >= (float) $value)) {
                                $result = false;
                            }
                        }
                        break;
                }
            }
        }
        return $result;
    }

    static public function wac_cart_filter_subtotal($post_id)
    {
        $filters = get_post_meta($post_id, "wac_filters", true);
        $wac_products = [];
        $amount = 0;
        foreach ($filters as $filter) {
            foreach ($filter["items"] as $filterItem) {
                array_push($wac_products, $filterItem["value"]);
            }
        }
        foreach (WC()->cart->get_cart() as $value) {
            foreach ($wac_products as $wac_product) {
                if ((string) $value["product_id"] == $wac_product) {
                    $amount = $amount + $value["line_subtotal"];
                }
            }
        }
        return $amount;
    }

    static public function wac_cart_filter_line_total($post_id)
    {
        $filters = get_post_meta($post_id, "wac_filters", true);
        $wac_products = [];
        $total = 0;
        foreach ($filters as $filter) {
            foreach ($filter["items"] as $filterItem) {
                array_push($wac_products, $filterItem["value"]);
            }
        }
        foreach (WC()->cart->get_cart() as $value) {
            foreach ($wac_products as $wac_product) {
                if ((string) $value["product_id"] == $wac_product) {
                    $total += 1;
                }
            }
        }
        return $total;
    }
}