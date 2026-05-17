# Changelog

## [1.0.0] - 2026-05-01

### Added
- Payment extension for OpenCart 4.x
- Admin configuration panel (API key, webhook secret, coin, expiry, test mode)
- Checkout redirect to NakoPay hosted payment page
- HMAC-SHA256 webhook signature verification
- Webhook handler for invoice.paid, invoice.expired, invoice.canceled
- Dual base URL strategy (Supabase primary, api.nakopay.com fallback)
- English language pack
