# Changelog

Бүх томоохон өөрчлөлтийг энд бүртгэнэ.

## v0.1.0 — гараагүй

Эхний хувилбар.

- Нэхэмжлэх: үүсгэх, лавлах, хүчингүй болгох, устгах
- Checkout: үүсгэх (fluent builder-тэй), лавлах, хямдрал ба хөнгөлөлтийн код
- Харилцагч: upsert, ID / `client_reference_id`-ээр лавлах
- Захиалга: жагсаалт, лавлах, туршилт эхлүүлэх, цуцлах/буцаах
- Billing portal: session үүсгэх
- Webhook: гарын үсгийн шалгалт, автомат route, event класс бүрээр
- `Billable` trait: локал `byl_subscriptions` mirror, webhook sync,
  `subscribed()` / `onTrial()` / `onGracePeriod()` шалгалтууд,
  `newSubscription()->checkout()`, `startTrial()`, сунгалт/багц солилт,
  billing portal helper, `byl.subscribed` middleware
- `Byl::fake()` болон `FakeWebhook` тестийн helper-ууд
