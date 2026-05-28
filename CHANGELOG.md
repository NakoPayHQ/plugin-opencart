# Changelog
## 1.1.0 - 2026-05-17

### Changed
- Default API base URL is now https://api.nakopay.com/v1 (branded primary). Supabase functions URL kept as fallback constant.

## [1.0.0] - 2026-05-01

### Added
- Payment extension for OpenCart 4.x
- Admin configuration panel (API key, webhook secret, coin, expiry, test mode)
- Checkout redirect to NakoPay hosted payment page
- HMAC-SHA256 webhook signature verification
- Webhook handler for invoice.paid, invoice.expired, invoice.canceled
- Dual base URL strategy (Supabase primary, api.nakopay.com fallback)
- English language pack
