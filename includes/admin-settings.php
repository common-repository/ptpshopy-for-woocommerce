<?php
if (!defined("ABSPATH")) {
    exit();
}

return apply_filters("wc_ptpshopy_settings", [
    "enabled" => [
        "title" => __("Enable/Disable", "ptpshopy-for-woocommerce"),
        "label" => __("Enable PTPShopy", "ptpshopy-for-woocommerce"),
        "type" => "checkbox",
        "description" => "",
        "default" => "yes",
    ],
    "title" => [
        "title" => __("Title", "ptpshopy-for-woocommerce"),
        "type" => "text",
        "description" => __(
            "This controls the title which the user sees during checkout.",
            "ptpshopy-for-woocommerce"
        ),
        "default" => __("Crypto Payment PTPShopy", "ptpshopy-for-woocommerce"),
    ],
    "description" => [
        "title" => __("Description", "ptpshopy-for-woocommerce"),
        "type" => "text",
        "description" => __(
            "This controls the description which the user sees during checkout.",
            "ptpshopy-for-woocommerce"
        ),
        "default" => __(
            "Pay with crypto via PTPShopy payment gateway",
            "ptpshopy-for-woocommerce"
        ),
    ],
    "ipn_url" => [
        "title" => __("IPN Url", "ptpshopy-for-woocommerce"),
        "type" => "text",
        "description" => sprintf(
            __(
                'Copy this url to "IPN Url" field on %1$s',
                "ptpshopy-for-woocommerce"
            ),
            '<a target="_blank" href="https://merchant.ptpshopy.com">merchant.ptpshopy.com</a>'
        ),
        "default" => esc_url(
            get_site_url() . "?wc-api=wc_ptpshopy_gateway_payment"
        ),
        "custom_attributes" => ["readonly" => "readonly"],
    ],
    "api_key" => [
        "title" => __("API Code", "ptpshopy-for-woocommerce"),
        "type" => "text",
        "description" => __(
            "Get your API Code from your PTPShopy account.",
            "ptpshopy-for-woocommerce"
        ),
        "default" => "",
    ],
    "ipn_key" => [
        "title" => __("IPN Key", "ptpshopy-for-woocommerce"),
        "type" => "text",
        "description" => __(
            "Get your IPN Key from your PTPShopy account.",
            "ptpshopy-for-woocommerce"
        ),
        "default" => "",
    ],
]);
