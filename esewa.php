<?php

if (!function_exists('generateEsewaSignature')) {

    function generateEsewaSignature(
        $totalAmount,
        $transactionUuid,
        $productCode
    ) {
        $secretKey = "8gBm/:&EnhH.1/q";

        $message =
            "total_amount={$totalAmount}," .
            "transaction_uuid={$transactionUuid}," .
            "product_code={$productCode}";

        return base64_encode(
            hash_hmac(
                'sha256',
                $message,
                $secretKey,
                true
            )
        );
    }

}