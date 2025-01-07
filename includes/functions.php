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

function seoSansMigraine_returnErrorIsset()
{
    return [
        "success" => false,
        "data" => [
            "error" => "issetError"
        ]
    ];
}

function seoSansMigraine_returnErrorForImpossibleReasons()
{
    return [
        "success" => false,
        "data" => [
            "error" => "retry"
        ]
    ];
}


$styleBeenPrinted = false;
function render_seoSansMigraine_alert($title, $message, $type)
{
    global $styleBeenPrinted;
    if (!$styleBeenPrinted) {
        $styleBeenPrinted = true;
        ?>
        <style>
            #notice-tsm p {
                margin: 0;
            }

            #notice-tsm .title {
                font-weight: bold;
                padding: 0;
            }

            #notice-tsm {
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                color: white;
                box-shadow: unset;
                border-left: unset;
            }

            #notice-tsm.error {
                background-color: #F44336;
            }

            #notice-tsm.success {
                background-color: #4CAF50;
            }

            #notice-tsm.info {
                background-color: #2196F3;
            }
        </style>
        <?php
    }
    ?>
    <div class="notice <?php echo $type; ?>" id="notice-tsm">
        <p class="title"><?php echo $title; ?></p>
        <p class="message">
            <?php echo $message; ?>
        </p>
    </div>
    <?php
}

function tsm_log($message)
{
    global $tsm;
    $lineCaller = debug_backtrace()[0]["line"];
    $fileCaller = debug_backtrace()[0]["file"];
    $tsm->getClient()->sendLog($message, $lineCaller, $fileCaller);
}