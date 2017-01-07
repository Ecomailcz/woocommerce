<?php
/**
 * Plugin Name: Ecomail
 * Version: 1.0
 * Author: Ecomail.cz s.r.o.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/vendor/autoload.php';

if (!class_exists('LNCPluginBaseV1')) {
    require_once __DIR__ . '/lib/pluginBase.php';
}

class EcomailPlugin extends LNCPluginBaseV1
{

    const PLUGIN_ID = 'ecomail';
    const PLUGIN_NAME = 'Ecomail';

    /**
     * @var Goutte\Client;
     */
    protected $client;

    public function __construct()
    {

        parent::__construct();

        add_filter(
            'plugin_action_links_' . plugin_basename(__FILE__),
            array(
                $this,
                'hookPluginActionLinks'
            )
        );

        add_filter(
            'admin_footer',
            array(
                $this,
                'hookAdminFooter'
            )
        );

        add_filter(
            'wp_footer',
            array(
                $this,
                'hookWPFooter'
            )
        );

        add_action(
            'wp_enqueue_scripts',
            array(
                $this,
                'hookWPEnqueueScripts'
            )
        );

        add_action(
            'woocommerce_checkout_order_processed',
            array(
                $this,
                'hookWCCheckoutOrderProcessed'
            )
        );

        add_action(
            'woocommerce_add_to_cart',
            array(
                $this,
                'hookWCAddToCart'
            ),
            10,
            3
        );

    }

    public function hookWPEnqueueScripts()
    {

        $helper = $this->factoryHelper();
        $appId = $helper->getConfigValue('app_id');

        if ($appId) {

            $path = '/' . static::PLUGIN_ID . '/js/front.js';

            $k = sprintf(
                '%s_js',
                static::PLUGIN_ID
            );
            wp_register_script(
                $k,
                plugins_url(
                    $path
                ),
                array('jquery'),
                '1'
            );
            wp_enqueue_script($k);

        }

    }

    public function hookWPFooter()
    {

        $output2 = '';

        $helper = $this->factoryHelper();
        $appId = $helper->getConfigValue('app_id');

        if ($appId) {

            $basePath = $this->getBasePath();

            $html = <<<HTML
<script type="text/javascript">
    EcomailFront.init({1});
</script>
HTML;

            $html = strtr(
                $html,
                array(
                    '{1}' => json_encode(
                        array(
                            'basePath' => $basePath,
                            'cookieNameTrackStructEvent' => $helper->getCookieNameTrackStructEvent()
                        )
                    )
                )
            );

            $output2 .= $html;

            $html = <<<HTML
                
<!-- Ecomail starts -->
<script type="text/javascript">
;(function(p,l,o,w,i,n,g){if(!p[i]){p.GlobalSnowplowNamespace=p.GlobalSnowplowNamespace||[];
p.GlobalSnowplowNamespace.push(i);p[i]=function(){(p[i].q=p[i].q||[]).push(arguments)
};p[i].q=p[i].q||[];n=l.createElement(o);g=l.getElementsByTagName(o)[0];n.async=1;
n.src=w;g.parentNode.insertBefore(n,g)}}(window,document,"script","//d1fc8wv8zag5ca.cloudfront.net/2.4.2/sp.js","ecotrack"));
window.ecotrack('newTracker', 'cf', 'd2dpiwfhf3tz0r.cloudfront.net', {1});
window.ecotrack('setUserIdFromLocation', 'ecmid');
window.ecotrack('trackPageView');
</script>
<!-- Ecomail stops -->
HTML;

            $html = strtr(
                $html,
                array(
                    '{1}' => json_encode(
                        array(
                            'appId' => $appId
                        )
                    )
                )
            );

            $output2 .= $html;
        }

        echo $output2;

    }

    public function hookWCCheckoutOrderProcessed($order_id)
    {

        $helper = $this->factoryHelper();

        $order = new WC_Order($order_id);

        $orderProducts = $order->get_items();

        $tax = $order->get_total_tax();
        $shipping = $order->get_total_shipping();

        $arr = array();
        foreach ($orderProducts as $orderProduct) {
            $product = $order->get_product_from_item($orderProduct);

            $categories = get_the_terms($product->get_id(), 'product_cat');

            $category_info = null;
            if (count($categories)) {
                $category_info = $categories[0];
            }

            if (empty($orderProduct['line_total'])) {
                continue;
            }

            $arr[] = array(
                'code' => $product->get_sku(),
                'title' => $orderProduct['name'],
                'category' => $category_info
                    ? $category_info->name
                    : null,
                'price' => round(
                    ($orderProduct['line_total']) / max(
                        1,
                        $orderProduct['qty']
                    ),
                    2
                ),
                'amount' => $orderProduct['qty'],
                'timestamp' => strtotime($order->order_date)
            );
        }

        $shopUrl = get_permalink(woocommerce_get_page_id('shop'));
        if (!$shopUrl) {
            $shopUrl = get_site_url();
        }

        $data = array(
            'transaction' => array(
                'order_id' => $order->id,
                'email' => $order->billing_email,
                'shop' => $shopUrl,
                'amount' => $order->get_total() - $tax,
                'tax' => $tax,
                'shipping' => $shipping,
                'city' => $order->shipping_city,
                'country' => $order->shipping_country,
                'timestamp' => strtotime($order->order_date)
            ),
            'transaction_items' => $arr
        );

        $r = $helper->getApi()
            ->createTransaction($data);
    }

    public function hookWCAddToCart($cart_item_key, $product_id, $quantity)
    {
        
        $helper = $this->factoryHelper();
        $appId = $helper->getConfigValue('app_id');
        if ($appId) {

            $product_info = wc_get_product($product_id);

            if ($product_info) {

                $basePath = $this->getBasePath();

                setcookie(
                    $helper->getCookieNameTrackStructEvent(),
                    json_encode(
                        array(
                            'category' => 'Product',
                            'action' => 'AddToCart',
                            'tag' => implode(
                                '|',
                                array(
                                    $product_id
                                )
                            ),
                            'property' => 'quantity',
                            'value' => $quantity
                        )
                    ),
                    null,
                    $basePath
                );

            }
        }
    }

    public function hookPluginActionLinks($links)
    {
        $settings_link = '<a href="' . admin_url(
                'admin.php?page=' . self::PLUGIN_ID . '-admin-page-settings'
            ) . '" title="' . esc_attr(
                __(
                    'Settings',
                    self::PLUGIN_ID
                )
            ) . '">' . __(
                'Settings',
                self::PLUGIN_ID
            ) . '</a>';
        array_push(
            $links,
            $settings_link
        );

        return $links;
    }

    public function hookAdminMenu()
    {

        add_submenu_page(
            null,
            __(
                self::PLUGIN_NAME,
                self::PLUGIN_ID
            ),
            __(
                self::PLUGIN_NAME,
                self::PLUGIN_ID
            ),
            'manage_options',
            self::PLUGIN_ID . '-admin-page-settings',
            array(
                $this,
                'buildAdminPageSettings'
            )
        );

    }

    public function hookAdminEnqueueScripts()
    {

        parent::hookAdminEnqueueScripts();

    }

    public function hookAdminInit()
    {

        parent::hookAdminInit();

        add_settings_section(
            $this->pluginSettingsKey,
            '',
            false,
            self::PLUGIN_ID . '-admin-page-settings'
        );

        $fields = array();

        $optionsModel = new \Ecomail\AdminModelOptionsListId();
        $optionsModel->setHelper($this->factoryHelper());

        $options = array();
        foreach ($optionsModel->getOptions() as $option) {
            $options[$option['value']] = $option['label'];
        }

        $fields[] = array(
            'uid' => 'api_key',
            'label' => __(
                'Vložte Váš API klíč',
                self::PLUGIN_ID
            ),
            'section' => $this->pluginSettingsKey,
            'type' => 'text',
            'options' => false,
            'placeholder' => '',
            'helper' => '',
            'supplemental' => ''
        );

        $fields[] = array(
            'uid' => 'list_id',
            'label' => __(
                'Vyberte list',
                self::PLUGIN_ID
            ),
            'section' => $this->pluginSettingsKey,
            'type' => 'select',
            'options' => $options,
            'placeholder' => '',
            'helper' => '',
            'supplemental' => __('Vyberte list do kterého budou zapsáni noví zákazníci',
                self::PLUGIN_ID
            )
        );

        $fields[] = array(
            'uid' => 'app_id',
            'label' => __(
                'Vložte Vaše appId',
                self::PLUGIN_ID
            ),
            'section' => $this->pluginSettingsKey,
            'type' => 'textarea',
            'options' => false,
            'placeholder' => '',
            'helper' => '',
            'supplemental' => __('Tento údaj slouží pro aktivaci funkce Trackovací kód',
                self::PLUGIN_ID
            )
        );


        foreach ($fields as $field) {
            add_settings_field(
                $this->pluginSettingsKey . '_' . $field['uid'],
                $field['label'],
                array($this, 'field_callback'),
                self::PLUGIN_ID . '-admin-page-settings',
                $field['section'],
                $field
            );
        }

        if (!empty($_REQUEST['action'])) {

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower(
                    $_SERVER['HTTP_X_REQUESTED_WITH']
                ) == 'xmlhttprequest';

            if ($isAjax) {

                if ($_REQUEST['action'] == 'ecomail/autocomplete') {

                    $helper = $this->factoryHelper();

                    $result = array();

                    $cmd = $_REQUEST['cmd'];
                    if ($cmd == 'getLists') {

                        $APIKey = $_REQUEST['APIKey'];
                        if ($APIKey) {
                            $listsCollection = $helper->getAPI()
                                ->setAPIKey($APIKey)
                                ->getListsCollection();
                            if ($listsCollection) {
                                foreach ($listsCollection as $list) {
                                    $result[] = array(
                                        'id' => $list->id,
                                        'name' => $list->name
                                    );
                                }
                            }
                        }

                    }

                    header('Content-Type', 'application/json');

                    echo json_encode($result);

                    exit();
                }
            }
        }
    }

    public function hookAdminFooter()
    {

        $html = <<<HTML
<script type="text/javascript">
    EcomailBackOffice.init({1});
</script>
HTML;

        $html = strtr(
            $html,
            array(
                '{1}' => json_encode(
                    array_merge(
                        array(
                            'formFieldAPIKey' => 'input-ecomail_api_key',
                            'formFieldList' => 'input-ecomail_list_id',
                            'ajaxUrl' => '?action=ecomail/autocomplete',
                            'templates' => array(
                                'connect' => null
                            )
                        ),
                        $this->getBackOfficeJSOptions()
                    )
                )
            )
        );

        echo $html;

    }

    public function buildAdminPageSettings()
    {

        ?>
        <img
            src="https://p3.zdassets.com/hc/settings_assets/941038/200151565/iq8kx9OWmQsNgFST8oYppw-ecomail-logo-black.png">
        <div class="clear"></div>
        <fieldset style="padding: 25px;border: 2px solid #efefef; margin-bottom: 15px;">
            <div
                style="float: right; width: 340px; height: 205px; border: dashed 1px #666; padding: 8px; margin-left: 12px; margin-top:-15px;">
                <h2 style="color:#aad03d;">Kontaktujte Ecomail</h2>

                <div style="clear: both;"></div>
                <p> Email : <a href="mailto:support@ecomail.cz" style="color:#aad03d;">support@ecomail.cz</a><br>Phone
                    :
                    +420
                    777 139 129</p>

                <p style="padding-top:20px;"><b>Pro více informací nás navštivte na:</b><br><a
                        href="http://www.ecomail.cz"
                        target="_blank"
                        style="color:#aad03d;">http://www.ecomail.cz</a>
                </p>
            </div>
            <p>Ecomail plugin Vám pomůže synchronizovat Vaše Wordpress kontakty s vybraným seznamem kontaktů ve
                Vašem
                ecomail.cz účtu</p>
            <b>Proč si vybrat Ecomail.cz ?</b>
            <ul class="listt">
                <li> Zaručíme Vám vyšší doručitelnost</li>
                <li> Nejlepší ceny na trhu - lepší nenajdete</li>
                <li> Rychlá a vstřícná podpora</li>
            </ul>
            <b>Co připravujeme k tomuto pluginu do dalších verzí?</b>
            <ul class="listt">
                <li> Užší integraci s akcemi ve Vašem obchodě</li>
                <li> Rozesílání newsletterů přímo z Wordpress</li>
                <li> Napište nám na <a href="mailto:support@ecomail.cz"
                                       style="color:#aad03d;">support@ecomail.cz</a> a
                    my Vám
                    plugin přizpůsobíme!
                </li>
            </ul>
            <div style="clear:both;">&nbsp;</div>
        </fieldset>


        <form action='options.php' method='post'>


            <div class="wrap" style="max-width: 580px">

                <?php settings_errors(); ?>

                <?php

                settings_fields($this->pluginSettingsKey);
                do_settings_sections(self::PLUGIN_ID . '-admin-page-settings');

                ?>
                <p>
                    <?php
                    submit_button(
                        'Save',
                        'secondary alignright',
                        'submit',
                        false
                    );
                    ?>
                </p>

            </div>

        </form>
        <?php

    }

    protected function factoryHelper()
    {
        $helper = new \Ecomail\Helper();
        $helper->setPlugin($this);

        return $helper;
    }

    protected function getBackOfficeJSOptions()
    {
        return array(
            'formFieldAPIKey' => 'EcomailSettings_api_key',
            'formFieldList' => 'EcomailSettings_list_id',
            'formFieldRowSelector' => 'tr',
            'templates' => array(
                'connect' => <<<HTML
<tr>
  <th scope="row"></th>
  <td style="padding-top: 0">
    <input type="submit" value="Připojit" id="{BUTTON_CONNECT}" class="btn">
  </td>
</tr>
HTML

            )
        );
    }
}

$EcomailPlugin = new EcomailPlugin();