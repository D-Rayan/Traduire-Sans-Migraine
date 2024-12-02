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
    $url = "https://api.seo-sans-migraine.fr/api/send-slack-message";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    // send in json
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $data = json_encode([
        "channel" => "notifications",
        "message" => $message
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $response = curl_exec($curl);
    if ($response === false) {
        __("Error: " . curl_error($curl));
    } else {
        __("Success: " . $response);
    }
    curl_close($curl);
}