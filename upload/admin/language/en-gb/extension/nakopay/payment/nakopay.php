<?php
// English language file for NakoPay payment extension

// Heading
$_['heading_title']       = 'NakoPay - Bitcoin & Crypto Payments';

// Text
$_['text_extension']      = 'Extensions';
$_['text_success']        = 'NakoPay settings saved successfully.';
$_['text_edit']           = 'Edit NakoPay Settings';

// Entry
$_['entry_status']        = 'Enabled';
$_['entry_title']         = 'Payment Title';
$_['entry_description']   = 'Description';
$_['entry_api_key']       = 'Secret API Key';
$_['entry_webhook_secret'] = 'Webhook Secret';
$_['entry_coin']          = 'Default Cryptocurrency';
$_['entry_invoice_expiry'] = 'Invoice Expiry (minutes)';
$_['entry_order_status']  = 'Order Status After Payment';
$_['entry_sort_order']    = 'Sort Order';
$_['entry_test_mode']     = 'Test Mode';
$_['entry_api_base_url']  = 'API Base URL (Advanced)';

// Help
$_['help_api_key']        = 'Your NakoPay secret key (sk_live_* or sk_test_*). Get one at nakopay.com/dashboard/api-keys.';
$_['help_webhook_secret'] = 'Your webhook signing secret (whsec_*). Created when you register a webhook.';
$_['help_invoice_expiry'] = 'How long the customer has to pay. Set to 0 for no expiry.';
$_['help_test_mode']      = 'When enabled, uses test mode behavior.';
$_['help_api_base_url']   = 'Leave blank to use the default. Only change if instructed by NakoPay support.';

// Error
$_['error_permission']    = 'You do not have permission to modify NakoPay settings.';
$_['error_api_key']       = 'API Key is required when the extension is enabled.';
