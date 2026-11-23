# 08 — Events / Jobs / Notifications Mapping

## Queue
- `QUEUE_CONNECTION=database`; Horizon-ready (Redis). `SendExpoPushJob` implements `ShouldQueue` with `$tries = 3`.

## Notifications module
- `push_tokens` (user_id / customer_id / token / platform).
- `NotificationService::push()` → dispatches `SendExpoPushJob` (Expo `https://exp.host/--/api/v2/push/send`).
- `NotificationService::orderStatus()` → pushes status updates to the customer's tokens.
- In-app notifications: Laravel `notifications` table (uuid morph) + `/notifications` page.

## Legacy → Laravel
| Legacy | Laravel |
|---|---|
| `pg_net` HTTP POST from trigger `order_push` | `SendExpoPushJob` dispatched from `OrderController@status` |
| `push_tokens` + Expo | `push_tokens` + `SendExpoPushJob` |
| Audit triggers (`audit()`) | `AuditService` (login, expense wired; extend via model observers) |
| `rate_limit_orders_trg` / rate_limits | Laravel `throttle` middleware + `RateLimiter` on login |

## Not yet implemented
- Scheduled commands (none in legacy ERP besides push).
- Webhooks / external API integrations (none in source).
- Email/SMS notifications (only Expo push present in legacy).

## Extension points
- Model observers can be added to auto-audit writes.
- Events (SaleCreated, OrderStatusChanged) can be introduced to decouple notifications.