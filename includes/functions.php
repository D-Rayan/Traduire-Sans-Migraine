<?php

function displayBigNumber($number) {
    // Display a big number with a ' every 3 digits
    return number_format($number, 0, ",", "'");
}

function seoSansMigraine_returnLoginError() {
    return [
        "success" => false,
        "data" => [
            "error" => "loginRequired"
        ]
    ];
}
function seoSansMigraine_returnNonceError() {
    return [
        "success" => false,
        "data" => [
            "error" => "loginRequired"
        ]
    ];
}