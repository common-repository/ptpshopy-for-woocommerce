<?php
if (!defined("ABSPATH")) {
    exit();
}

class WC_Ptpshopy_Admin_Notices
{
    public $notices = [];

    public function __construct()
    {
        add_action("admin_notices", [$this, "admin_notices"]);
        add_action("wp_loaded", [$this, "hide_notices"]);
    }

    public function add_admin_notice(
        $slug,
        $class,
        $message,
        $dismissible = false
    ) {
        $this->notices[$slug] = [
            "class" => $class,
            "message" => $message,
            "dismissible" => $dismissible,
        ];
    }

    public function admin_notices()
    {
        if (!current_user_can("manage_woocommerce")) {
            return;
        }

        $this->check_environment();
        $this->check_public_version_plugin();

        foreach ((array) $this->notices as $notice_key => $notice) {
            echo '<div class="' .
                esc_attr($notice["class"]) .
                '" style="position:relative;">';

            if ($notice["dismissible"]) { ?>
				<a href="<?php echo esc_url(
        wp_nonce_url(
            add_query_arg("wc-ptpshopy-hide-notice", $notice_key),
            "WC_Ptpshopy_hide_notices_nonce",
            "_WC_Ptpshopy_notice_nonce"
        )
    ); ?>" class="woocommerce-message-close notice-dismiss" style="position:absolute;right:1px;padding:9px;text-decoration:none;"></a>
			<?php }

            echo "<p>";
            echo wp_kses($notice["message"], ["a" => ["href" => []]]);
            echo "</p></div>";
        }
    }

    private function check_public_version_plugin()
    {
        $public_plugin_url =
            "https://wordpress.org/plugins/ptpshopy-for-woocommerce/";
        $last_check_datetime = get_option(
            "wc_ptpshopy_latest_datetime_check_public_version_plugin"
        );
        $datetime_now = new DateTime();
        try {
            $datetime_diff = $last_check_datetime->diff($datetime_now);
            if ($datetime_diff->format("%h") > 24) {
                $response = wp_remote_get($public_plugin_url, [
                    "redirection" => 0,
                ]);
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code === 200) {
                    $this->add_admin_notice(
                        "pluginver",
                        "notice notice-warning",
                        sprintf(
                            esc_html__(
                                'The new version of PTPShopy is now available on the wordpress.org website (%1$s). Please update.',
                                "ptpshopy-for-woocommerce"
                            ),
                            '<a target="_blank" href="' .
                                esc_url($public_plugin_url) .
                                '">' .
                                esc_url($public_plugin_url) .
                                "</a>"
                        ),
                        true
                    );
                }
                update_option(
                    "wc_ptpshopy_latest_datetime_check_public_version_plugin",
                    $datetime_now
                );
            }
        } catch (\Throwable $th) {
            update_option(
                "wc_ptpshopy_latest_datetime_check_public_version_plugin",
                $datetime_now
            );
        }
    }

    public function check_environment()
    {
        $show_keys_notice = get_option("wc_ptpshopy_show_keys_notice");
        $show_ssl_notice = get_option("wc_ptpshopy_show_ssl_notice");
        $show_phpver_notice = get_option("wc_ptpshopy_show_phpver_notice");
        $show_wcver_notice = get_option("wc_ptpshopy_show_wcver_notice");
        $show_curl_notice = get_option("wc_ptpshopy_show_curl_notice");
        $show_curl_notice = get_option("wc_ptpshopy_show_curl_notice");
        $options = get_option("woocommerce_ptpshopy_settings");
        $api_key = isset($options["api_key"]) ? $options["api_key"] : "";
        $ipn_key = isset($options["ipn_key"]) ? $options["ipn_key"] : "";

        if (isset($options["enabled"]) && "yes" === $options["enabled"]) {
            if (empty($show_phpver_notice)) {
                if (
                    version_compare(phpversion(), WC_PTPSHOPY_MIN_PHP_VER, "<")
                ) {
                    $this->add_admin_notice(
                        "phpver",
                        "error",
                        sprintf(
                            esc_html__(
                                'WooCommerce PTPShopy - The minimum PHP version required for this plugin is %1$s. You are running %2$s.',
                                "ptpshopy-for-woocommerce"
                            ),
                            esc_html(WC_PTPSHOPY_MIN_PHP_VER),
                            esc_html(phpversion())
                        ),
                        true
                    );
                    return;
                }
            }

            if (empty($show_wcver_notice)) {
                if (version_compare(WC_VERSION, WC_PTPSHOPY_MIN_WC_VER, "<")) {
                    $this->add_admin_notice(
                        "wcver",
                        "notice notice-warning",
                        sprintf(
                            esc_html__(
                                'WooCommerce PTPShopy - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.',
                                "ptpshopy-for-woocommerce"
                            ),
                            esc_html(WC_PTPSHOPY_MIN_WC_VER),
                            esc_html(WC_VERSION)
                        ),
                        true
                    );
                    return;
                }
            }

            if (empty($show_curl_notice)) {
                if (!function_exists("curl_init")) {
                    $this->add_admin_notice(
                        "curl",
                        "notice notice-warning",
                        esc_html__(
                            "WooCommerce PTPShopy - cURL is not installed.",
                            "ptpshopy-for-woocommerce"
                        ),
                        true
                    );
                }
            }

            if (empty($show_keys_notice)) {
                $options = get_option("woocommerce_ptpshopy_settings");
                $api_key = $options["api_key"];
                $ipn_key = $options["ipn_key"];

                if (
                    (empty($api_key) || empty($ipn_key)) &&
                    !(
                        isset($_GET["page"], $_GET["section"]) &&
                        "wc-settings" === $_GET["page"] &&
                        "ptpshopy" === $_GET["section"]
                    )
                ) {
                    $setting_link = $this->get_setting_link();
                    $this->add_admin_notice(
                        "keys",
                        "notice notice-warning",
                        sprintf(
                            esc_html__(
                                'PTPShopy is almost ready. To get started, $%1$s.',
                                "ptpshopy-for-woocommerce"
                            ),
                            '<a href="' .
                                esc_url($setting_link) .
                                '">set your API keys</a>'
                        ),
                        true
                    );
                }
            }

            if (empty($show_ssl_notice)) {
                if (!wc_checkout_is_https()) {
                    $this->add_admin_notice(
                        "ssl",
                        "notice notice-warning",
                        sprintf(
                            esc_html__(
                                'PTPShopy is enabled, but an SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid %2$s',
                                "ptpshopy-for-woocommerce"
                            ),
                            '<a href="' .
                                esc_url(
                                    "https://en.wikipedia.org/wiki/Transport_Layer_Security"
                                ) .
                                '" target="_blank">SSL certificate</a>'
                        ),
                        true
                    );
                }
            }
        }
    }

    public function hide_notices()
    {
        if (
            isset($_GET["wc-ptpshopy-hide-notice"]) &&
            isset($_GET["_WC_Ptpshopy_notice_nonce"])
        ) {
            if (
                !wp_verify_nonce(
                    sanitize_text_field(
                        wp_unslash($_GET["_WC_Ptpshopy_notice_nonce"])
                    ),
                    "WC_Ptpshopy_hide_notices_nonce"
                )
            ) {
                wp_die(
                    esc_html__(
                        "Action failed. Please refresh the page and retry.",
                        "ptpshopy-for-woocommerce"
                    )
                );
            }

            if (!current_user_can("manage_woocommerce")) {
                wp_die(esc_html__("Access denied", "ptpshopy-for-woocommerce"));
            }

            $notice = wc_clean($_GET["wc-ptpshopy-hide-notice"]);

            switch ($notice) {
                case "phpver":
                    update_option("WC_Ptpshopy_show_phpver_notice", "no");
                    break;
                case "wcver":
                    update_option("WC_Ptpshopy_show_wcver_notice", "no");
                    break;
                case "curl":
                    update_option("WC_Ptpshopy_show_curl_notice", "no");
                    break;
                case "keys":
                    update_option("WC_Ptpshopy_show_keys_notice", "no");
                    break;
                case "ssl":
                    update_option("WC_Ptpshopy_show_ssl_notice", "no");
                    break;
            }
        }
    }

    public function get_setting_link()
    {
        $use_id_as_section = function_exists("WC")
            ? version_compare(WC()->version, "2.6", ">=")
            : false;

        $section_slug = $use_id_as_section
            ? "ptpshopy"
            : strtolower("WC_Ptpshopy_Gateway");

        return admin_url(
            "admin.php?page=wc-settings&tab=checkout&section=" . $section_slug
        );
    }
}

new WC_Ptpshopy_Admin_Notices();
