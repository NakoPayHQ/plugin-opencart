<?php

namespace Opencart\Admin\Controller\Extension\NakoPay\Payment;

/**
 * NakoPay payment gateway admin controller for OpenCart 4.
 * Handles configuration of API key, webhook secret, coin, and invoice expiry.
 */
class NakoPay extends \Opencart\System\Engine\Controller
{
    private array $error = [];

    public function index(): void
    {
        $this->load->language('extension/nakopay/payment/nakopay');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment'),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/nakopay/payment/nakopay', 'user_token=' . $this->session->data['user_token']),
            ],
        ];

        $data['save'] = $this->url->link('extension/nakopay/payment/nakopay.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

        // Load current settings or POST values
        $fields = [
            'payment_nakopay_status',
            'payment_nakopay_title',
            'payment_nakopay_description',
            'payment_nakopay_api_key',
            'payment_nakopay_webhook_secret',
            'payment_nakopay_coin',
            'payment_nakopay_invoice_expiry',
            'payment_nakopay_order_status_id',
            'payment_nakopay_sort_order',
            'payment_nakopay_test_mode',
            'payment_nakopay_api_base_url',
        ];

        foreach ($fields as $field) {
            $data[$field] = $this->request->post[$field] ?? $this->config->get($field);
        }

        // Defaults
        if (empty($data['payment_nakopay_title'])) {
            $data['payment_nakopay_title'] = 'Pay with Bitcoin & Crypto';
        }
        if (empty($data['payment_nakopay_coin'])) {
            $data['payment_nakopay_coin'] = 'BTC';
        }
        if (empty($data['payment_nakopay_invoice_expiry'])) {
            $data['payment_nakopay_invoice_expiry'] = '60';
        }

        $data['coins'] = [
            ['value' => 'BTC', 'label' => 'Bitcoin (BTC)'],
            ['value' => 'LN', 'label' => 'Lightning (LN)'],
            ['value' => 'LTC', 'label' => 'Litecoin (LTC)'],
            ['value' => 'XMR', 'label' => 'Monero (XMR)'],
        ];

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_api_key'] = $this->error['api_key'] ?? '';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/nakopay/payment/nakopay', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/nakopay/payment/nakopay');

        if (!$this->user->hasPermission('modify', 'extension/nakopay/payment/nakopay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['payment_nakopay_api_key']) && !empty($this->request->post['payment_nakopay_status'])) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (empty($this->error)) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_nakopay', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment'));
        } else {
            $this->index();
        }
    }

    public function install(): void
    {
        // No custom tables needed - NakoPay uses hosted checkout
    }

    public function uninstall(): void
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('payment_nakopay');
    }
}
