<?php

namespace Opencart\Catalog\Model\Extension\NakoPay\Payment;

/**
 * NakoPay payment model for OpenCart 4 catalog.
 */
class NakoPay extends \Opencart\System\Engine\Model
{
    /**
     * Returns the payment method details if available.
     */
    public function getMethods(array $address = []): array
    {
        $this->load->language('extension/nakopay/payment/nakopay');

        if (!$this->config->get('payment_nakopay_status')) {
            return [];
        }

        if (!$this->config->get('payment_nakopay_api_key')) {
            return [];
        }

        $title = $this->config->get('payment_nakopay_title') ?: $this->language->get('text_title');

        return [
            'code' => 'nakopay.nakopay',
            'name' => $title,
            'option' => [
                'nakopay' => [
                    'code' => 'nakopay.nakopay.nakopay',
                    'name' => $title,
                ],
            ],
            'sort_order' => (int) $this->config->get('payment_nakopay_sort_order'),
        ];
    }
}
