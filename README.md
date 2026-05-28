# NakoPay for OpenCart 4

Accept Bitcoin, Lightning, Litecoin, Monero and more on your OpenCart 4 store with NakoPay.

## Requirements

- OpenCart 4.0.x
- PHP 8.1+
- A NakoPay account ([sign up](https://nakopay.com))

## Installation

1. Download the latest release zip from [GitHub Releases](https://github.com/NakoPayHQ/plugin-opencart/releases)
2. In OpenCart admin, go to **Extensions > Installer**
3. Upload the zip file
4. Go to **Extensions > Extensions > Payments**
5. Find "NakoPay" and click **Install**, then **Edit**

## Configuration

1. Set **Enabled** to Yes
2. Enter your **Secret API Key** (`sk_live_*` or `sk_test_*`)
3. Enter your **Webhook Secret** (`whsec_*`)
4. Choose your default cryptocurrency
5. Set invoice expiry time
6. Save

## Webhook Setup

Register a webhook in your NakoPay dashboard pointing to:

```
https://your-store.com/index.php?route=extension/nakopay/payment/nakopay.webhook
```

Enable events: `invoice.paid`, `invoice.expired`, `invoice.canceled`

All webhooks are verified using HMAC-SHA256 signatures.

## How It Works

1. Customer selects "Pay with Bitcoin & Crypto" at checkout
2. Click "Pay with Crypto" creates a NakoPay invoice and redirects to hosted checkout
3. Customer pays with their chosen cryptocurrency
4. NakoPay sends a webhook to update the order status
5. Customer returns to the success page

## Security

- Webhook payloads verified with HMAC-SHA256 timing-safe comparison
- API keys stored in OpenCart's settings system
- No raw SQL - uses OpenCart's model layer
- Input validation on all admin fields

## Support

- [Documentation](https://nakopay.com/docs/integrations/opencart)
- [GitHub Issues](https://github.com/NakoPayHQ/plugin-opencart/issues)

## About OpenCart

[OpenCart](https://www.opencart.com/) - free open-source e-commerce platform. Visit their website to learn more about the platform and its features.

## License

MIT
