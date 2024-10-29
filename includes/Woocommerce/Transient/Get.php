<?php

namespace TraduireSansMigraine\Woocommerce\Transient;

class Get
{
    private $WC_TRANSIENTS = [
        "product_query-transient-version",
    ];

    public function __construct()
    {

    }

    public function init()
    {
        foreach ($this->WC_TRANSIENTS as $transientName) {
            add_filter("pre_transient_$transientName", [$this, "addLanguageToTransient"], 10, 2);
            add_action("set_transient_$transientName", [$this, "setLanguageToTransient"], 10, 3);
        }
    }

    public function addLanguageToTransient($preTransient, $transient)
    {
        global $tsm;
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $value = get_transient($transient . "_$currentLanguage");
        if ($value === false) {
            delete_transient($transient); // clear cache
        }

        return $value;
    }

    public function setLanguageToTransient($value, $expiration, $transient)
    {
        global $tsm;
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        
        set_transient($transient . "_$currentLanguage", $value, $expiration);
    }
}

$Get = new Get();
$Get->init();