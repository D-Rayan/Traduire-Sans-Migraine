<?php

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