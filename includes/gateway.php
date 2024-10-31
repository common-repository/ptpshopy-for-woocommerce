<?php
if (!defined("ABSPATH")) {
    exit();
}

class WC_Ptpshopy_Gateway extends WC_Payment_Gateway
{
    /**
     * API access key
     *
     * @var string
     */
    public $api_key;
    public $ipn_key;

    public function __construct()
    {
        $this->id = "ptpshopy";
        $this->method_title = "PTPShopy";
        $this->method_description = sprintf(
            esc_html__(
                'PTPShopy works by adding crypto payment fields on the checkout and then sending the details to a secure server for payment. %1$s for a PTPShopy account, and get your API keys.',
                "ptpshopy-for-woocommerce"
            ),
            '<a href="https://auth.ptpshopy.com/sign-up-merchant" target="_blank">Sign up</a>'
        );
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option("title");
        $this->description = $this->get_option("description");
        $this->icon = apply_filters(
            "woocommerce_ptpshopy_icon",
            plugins_url(
                "assets/icon.svg",
                str_replace("includes", "", __FILE__)
            ),
            $this->id
        );
        $this->enabled = $this->get_option("enabled");
        $this->api_key = $this->get_option("api_key");
        $this->ipn_key = $this->get_option("ipn_key");

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);
        add_action(
            "woocommerce_api_" . strtolower(get_class($this)) . "_payment",
            [$this, "callback_handler"]
        );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = require dirname(__FILE__) . "/admin-settings.php";
    }

    /**
     * Handles the notification return.
     */
    public function callback_handler()
    {
        $request_headers = getallheaders();

        if (!isset($request_headers["Hmac"])) {
            wp_die("Access denied!", "", ["response" => 403]);
        }

        $request = file_get_contents("php://input");

        $hmac = hash_hmac("sha512", $request, $this->ipn_key);

        if ($hmac !== $request_headers["Hmac"]) {
            wp_die("Access denied!", "", ["response" => 401]);
        }

        $request_data = json_decode($request, true);

        $request_order_id = $request_data["orderId"]
            ? (int) sanitize_text_field($request_data["orderId"])
            : "";
        $request_order_status = $request_data["status"]
            ? sanitize_text_field($request_data["status"])
            : "";
        $request_order_amount = $request_data["amount"]
            ? sanitize_text_field($request_data["amount"])
            : "";

        if ($request_order_status === "" || $request_order_amount === "") {
            wp_die("IPN request failed", "", ["response" => 200]);
        }

        $order = wc_get_order($request_order_id);

        if (!$order) {
            wp_die(
                sprintf(
                    "Order #%s does not exists",
                    esc_html($request_order_id)
                ),
                "",
                [
                    "response" => 200,
                ]
            );
        }

        $response = [];

        if ($request_order_status === "done") {
            $order->update_status("processing");

            $response = [
                "status" => "success",
            ];
        } elseif ($request_order_status === "failed") {
            $order->update_status("failed");

            $response = [
                "status" => "failed",
            ];
        }

        wp_die($response, "", [
            "response" => 200,
        ]);
    }

    /**
     * Process the payment
     *
     * @return array|void
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $app_code = $this->api_key;

        $request = wp_remote_get(
            "https://public.ptpshopy.com/create-token?price=" .
                $order->get_total() .
                "&orderId=" .
                $order_id .
                "&code=" .
                $app_code
        );

        if (is_wp_error($request)) {
            $error_message = $request->get_error_message();
            wc_add_notice(
                sprintf(
                    esc_html__(
                        'Something went wrong: %1$s',
                        "ptpshopy-for-woocommerce"
                    ),
                    esc_html($error_message)
                ),
                "error"
            );
            return [
                "result" => "failure",
                "messages" => "failure",
            ];
        }

        $body = wp_remote_retrieve_body($request);
        $response = json_decode($body);

        if (isset($response->message)) {
            wc_add_notice(esc_html__($response->message), "error");
            return [
                "result" => "failure",
                "messages" => "failure",
            ];
        }

        if (isset($response->payToken)) {
            $woocommerce->cart->empty_cart();

            $order->update_status(
                "on-hold",
                esc_html__(
                    "Awaiting crypto payment",
                    "ptpshopy-for-woocommerce"
                )
            );

            $token = sanitize_text_field($response->payToken);
            $return_url = $order->get_checkout_order_received_url();
            $app_code = $this->api_key;

            return [
                "result" => "success",
                "redirect" => "https://checkout.ptpshopy.com/a/$app_code?token=$token&returnUrl=$return_url",
            ];
        }

        wc_add_notice(
            esc_html__("Error pay token", "ptpshopy-for-woocommerce"),
            "error"
        );
        return [
            "result" => "failure",
            "messages" => "failure",
        ];
    }
}
