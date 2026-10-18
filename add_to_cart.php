<?php
session_start();
require_once 'db.php';

// Initialize cart array if it doesn't exist
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $added_count = 0;

    // -------------------------------------------------------------
    // 1. BATCH ADDITION PROCESSOR (From shop.php multi-select form)
    // -------------------------------------------------------------
    if (isset($_POST['batch_add']) && isset($_POST['items']) && is_array($_POST['items'])) {
        
        foreach ($_POST['items'] as $product_id => $item_data) {
            // Skip items that were not checked
            if (empty($item_data['selected'])) {
                continue;
            }

            $product_id = (int)$product_id;

            // Retrieve current product details from database
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                continue;
            }

            $price_per_kg = (float)($product['price'] ?? $product['price_per_kg'] ?? 0);
            if ($price_per_kg <= 0) {
                continue;
            }

            $buy_mode = $item_data['buy_mode'] ?? 'weight';
            $cut_preference = !empty($item_data['cut_preference']) ? htmlspecialchars(trim($item_data['cut_preference'])) : 'Standard Cut';

            if ($buy_mode === 'amount') {
                $amount_kes = (float)($item_data['amount_kes'] ?? 0);
                if ($amount_kes <= 0) {
                    continue;
                }
                $total_price = $amount_kes;
                $weight_kg = round($amount_kes / $price_per_kg, 3);
            } else {
                $weight_kg = (float)($item_data['weight_kg'] ?? 1.0);
                if ($weight_kg <= 0) {
                    continue;
                }
                $total_price = round($weight_kg * $price_per_kg, 2);
            }

            // Store in cart array with a unique cart item ID
            $_SESSION['cart'][] = [
                'cart_id'        => uniqid('item_'),
                'product_id'     => $product_id,
                'name'           => $product['name'],
                'price_per_kg'   => $price_per_kg,
                'buy_mode'       => $buy_mode,
                'weight_kg'      => $weight_kg,
                'total_price'    => $total_price,
                'cut_preference' => $cut_preference
            ];

            $added_count++;
        }

        if ($added_count > 0) {
            $_SESSION['cart_message'] = "Successfully added $added_count item(s) to your cart.";
        } else {
            $_SESSION['cart_error'] = "Please check the 'Select' box for at least one item before adding to cart.";
        }

    // -------------------------------------------------------------
    // 2. SINGLE ITEM ADDITION PROCESSOR (Direct button submissions)
    // -------------------------------------------------------------
    } elseif (isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];

        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $price_per_kg = (float)($product['price'] ?? $product['price_per_kg'] ?? 0);
            $buy_mode = $_POST['buy_mode'] ?? 'weight';
            $cut_preference = !empty($_POST['cut_preference']) ? htmlspecialchars(trim($_POST['cut_preference'])) : 'Standard Cut';

            if ($buy_mode === 'amount') {
                $amount_kes = (float)($_POST['amount_kes'] ?? 0);
                $total_price = $amount_kes;
                $weight_kg = $price_per_kg > 0 ? round($amount_kes / $price_per_kg, 3) : 0;
            } else {
                $weight_kg = (float)($_POST['weight_kg'] ?? 1.0);
                $total_price = round($weight_kg * $price_per_kg, 2);
            }

            if ($total_price > 0 && $weight_kg > 0) {
                $_SESSION['cart'][] = [
                    'cart_id'        => uniqid('item_'),
                    'product_id'     => $product_id,
                    'name'           => $product['name'],
                    'price_per_kg'   => $price_per_kg,
                    'buy_mode'       => $buy_mode,
                    'weight_kg'      => $weight_kg,
                    'total_price'    => $total_price,
                    'cut_preference' => $cut_preference
                ];
                $_SESSION['cart_message'] = "Added " . htmlspecialchars($product['name']) . " to your cart.";
            } else {
                $_SESSION['cart_error'] = "Invalid weight or amount entered.";
            }
        } else {
            $_SESSION['cart_error'] = "Product not found.";
        }
    }
}

// Redirect back to shop page
header('Location: shop.php');
exit;