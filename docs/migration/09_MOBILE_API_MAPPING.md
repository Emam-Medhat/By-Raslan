# 09 — Mobile API Mapping

The legacy Expo app (`mobile/`) talked to Supabase (Auth OTP + RPC). The Laravel replacement exposes a public JSON API.

## Endpoints
| Method | Path | Purpose | Legacy equivalent |
|---|---|---|---|
| GET | `/api/branches` | branches with fee/min/open | `public_branches()` |
| GET | `/api/menu?location=` | items with app price | `public_menu(loc)` |
| POST | `/api/orders` | place order (phone/name/address/location/lines) | `place_order(...)` |
| GET | `/api/track/{token}` | order status by token | `track_order(token)` |
| POST | `/api/push-token` | register Expo push token | (new) |

## Auth model change
- Legacy: Supabase Auth (customer phone OTP) + `auth.uid()`.
- Laravel: customers are `customers` rows (no login). Staff use Laravel session auth. Mobile customer flow uses the public API + tracking token (no OTP in v1).

## Contract notes
- CSRF excluded for `api/*`.
- Responses are JSON; `placeOrder` returns `{ ok, order:{no,token,est_total} }` or `{ ok:false, error }`.
- Push tokens keyed by `customer_id` (or `user_id` for staff).

## Mobile app re-point (required)
Update `mobile/lib/supabase.ts` / RPC calls to call the Laravel API base URL. This is a client change (outside this repo) and is NOT yet done.