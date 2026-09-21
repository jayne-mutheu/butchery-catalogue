<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- MODE 1: BATCH / MULTI-ITEM SELECTION ---
    if (isset($_POST['batch_add']) && isset($_POST['items']) && is_array($_POST['items'])) {
        $added_count = 0;

        foreach ($_POST['items'] as $product_id => $item_data) {
            // Check if customer marked checkbox for this product
            if (empty($item_data['selected'])) {
                continue;
            }

            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if ($product) {
                $price_per_kg = (float)($product['price'] ?? $product['price_per_kg'] ?? 0);
                $cut = $item_data['cut_preference'] ?? 'Standard Cut';
                $buy_mode = $item_data['buy_mode'] ?? 'weight';

                if ($buy_mode === 'amount') {
                    $amount_kes = (float)($item_data['amount_kes'] ?? 0);
                    if ($amount_kes <= 0 || $price_per_kg <= 0) continue;
                    $weight_kg = round($amount_kes / $price_per_kg, 3);
                    $line_total = $amount_kes;
                } else {
                    $weight_kg = (float)($item_data['weight_kg'] ?? 0);
                    if ($weight_kg <= 0) continue;
                    $line_total = round($weight_kg * $price_per_kg, 2);
                }

                $cart_key = $product_id . '_' . md5($cut . '_' . $buy_mode);

                if (isset($_SESSION['cart'][$cart_key])) {
                    $_SESSION['cart'][$cart_key]['weight_kg'] += $weight_kg;
                    $_SESSION['cart'][$cart_key]['total_price'] += $line_total;
                } else {
                    $_SESSION['cart'][$cart_key] = [
                        'product_id'   => $product['id'],
                        'name'         => $product['name'],
                        'price_per_kg' => $price_per_kg,
                        'weight_kg'    => $weight_kg,
                        'cut_preference'=> $cut,
                        'total_price'  => $line_total,
                        'buy_mode'     => $buy_mode
                    ];
                }
                $added_count++;
            }
        }

        if ($added_count > 0) {
            $_SESSION['cart_message'] = "Added {$added_count} meat item(s) to your cart!";
        } else {
            $_SESSION['cart_error'] = "Please check at least one item and enter a valid quantity or amount.";
            header('Location: shop.php');
            exit;
        }

    // --- MODE 2: SINGLE ITEM SELECTION ---
    } elseif (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $cut = $_POST['cut_preference'] ?? 'Standard Cut';
        $buy_mode = $_POST['buy_mode'] ?? 'weight';

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $price_per_kg = (float)($product['price'] ?? $product['price_per_kg'] ?? 0);

            if ($buy_mode === 'amount') {
                $amount_kes = (float)($_POST['amount_kes'] ?? 0);
                if ($amount_kes <= 0) {
                    $_SESSION['cart_error'] = "Please enter a valid amount in KES.";
                    header('Location: shop.php');
                    exit;
                }
                $weight_kg = round($amount_kes / $price_per_kg, 3);
                $line_total = $amount_kes;
            } else {
                $weight_kg = (float)($_POST['weight_kg'] ?? 0);
                if ($weight_kg <= 0) {
                    $_SESSION['cart_error'] = "Please enter a valid weight in KG.";
                    header('Location: shop.php');
                    exit;
                }
                $line_total = round($weight_kg * $price_per_kg, 2);
            }

            $cart_key = $product_id . '_' . md5($cut . '_' . $buy_mode);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['weight_kg'] += $weight_kg;
                $_SESSION['cart'][$cart_key]['total_price'] += $line_total;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'product_id'   => $product['id'],
                    'name'         => $product['name'],
                    'price_per_kg' => $price_per_kg,
                    'weight_kg'    => $weight_kg,
                    'cut_preference'=> $cut,
                    'total_price'  => $line_total,
                    'buy_mode'     => $buy_mode
                ];
            }

            $_SESSION['cart_message'] = "Added {$product['name']} to your cart!";
        }
    }
}

header('Location: cart.php');
exit;