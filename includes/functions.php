<?php

function seoSansMigraine_returnLoginError()
{
    return [
        "success" => false,
        "data" => [
            "error" => "loginRequired"
        ]
    ];
}

function seoSansMigraine_returnNonceError()
{
    return [
        "success" => false,
        "data" => [
            "error" => "nonceError"
        ]
    ];
}

function render_seoSansMigraine_alert($title, $message, $type)
{
    ?>
    <div class="notice" style="
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            background-color: <?php echo $type === "error" ? "#f44336" : ($type === "success" ? "#4CAF50" : "#2196F3"); ?>;
            color: white;
            box-shadow: unset;
            border-left: unset;
            ">
        <p style="
            font-weight: bold;
            margin: 0;
            padding: 0;
        "><?php echo $title; ?></p>
        <p style="
            margin: 0;
            ">
            <?php echo $message; ?>
        </p>
    </div>
    <?php
}