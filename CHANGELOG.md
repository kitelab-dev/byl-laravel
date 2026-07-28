# Changelog

Бүх томоохон өөрчлөлтийг энд бүртгэнэ.

## v0.1.1

- Туршилт эхлүүлэхэд үнийн **lookup key** дэмжигдэв —
  `Byl::subscriptions()->startTrial(12, 'starter_monthly', 14)` болон
  `$user->newSubscription('starter_monthly')->startTrial(14)`. Өмнө нь
  зөвхөн үнийн ID зөвшөөрөгдөж, lookup key дамжуулбал `LogicException`
  шидэгддэг байсан — тэр хязгаарлалт болон онцгой тохиолдол хоёулаа
  устав.
- Захиалгын жагсаалтад `price` (lookup key) filter нэмэгдэв:
  `Byl::subscriptions()->list(['price' => 'starter_monthly'])`.
- `Endpoints\Subscriptions::startTrial()`-ийн хоёрдугаар параметр
  `$priceId` → `$price` болов (нэрлэсэн аргументаар дуудаж байсан бол
  шинэчилнэ).

## v0.1.0

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
