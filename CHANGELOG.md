# Changelog

Бүх томоохон өөрчлөлтийг энд бүртгэнэ.

## v0.1.3

- **Банкны шилжүүлгийн (`bank_transfer`) дэмжлэг.** Byl дээр банкны дансаар
  төлөх боломж нэмэгдсэнтэй холбоотойгоор SDK шинэ `pending` төлөв болон
  хоёр webhook event-ийг таньдаг болов.
- `Enums\CheckoutStatus::Pending` нэмэгдэв — харилцагч шилжүүлгээ хийснээ
  мэдэгдсэн ч merchant баталгаажуулаагүй checkout. Өмнө нь энэ утга
  танигдахгүй `null` болж байсан. `CheckoutStatus::isPending()` ба
  `Data\Checkout::isPending()` helper-ууд нэмэгдэв. **Эрх/бараагаа зөвхөн
  `isComplete()` дээр олгоно** — `pending` нь төлөгдсөн гэсэн үг биш.
- **`Enums\PaymentStatus`** (шинэ) — `pending`, `paid`, `failed`, `refunded`
  болон `isPaid()`, `isPending()`.
- **`Data\Payment`** (шинэ) — нэг төлбөрийн оролдлогын DTO: `reference`
  (шилжүүлгийн лавлагаа; бусад driver дээр `transaction_id`-аас уншина),
  `bankName`, `accountNumber`, `claimedAt`, `expiresAt`, `amount`,
  `isTest` зэрэг талбарууд, төлбөр хамаарах checkout/нэхэмжлэхийн
  `payableType()` / `payableId()` / `payableUrl()` / `payableStatus()` /
  `payableNumber()` accessor-ууд.
- **Шинэ event класс** `Events\PaymentAwaitingVerification`
  (`payment.awaiting_verification`) — харилцагч шилжүүлэг хийснээ
  мэдэгдсэн, merchant баталгаажуулахыг хүлээж байна; ба
  `Events\PaymentVerificationDue` (`payment.verification_due`) —
  баталгаажуулах 3 хоногийн хугацаа дуусахаас 24 цагийн өмнөх сануулга.
- `Webhooks\WebhookEvent::payment()` нэмэгдэж, `resource()` нь `payment`
  объектын хувьд `Data\Payment` буцаадаг болов.
- **`Testing\PaymentFactory`** (шинэ) — бодит payload-ийн бүтэцтэй
  `make()`, `awaitingVerification()`, `paid()`, `forInvoice()`.

## v0.1.2

- **Захиалгын `lookup_key` хоосон үлдэх асуудал зассан.** Byl-ийн webhook нь
  `price.lookup_key`-г явуулдаггүй байсан тул webhook-ээр үүссэн бүх мөрийн
  `lookup_key` `null` болж, `subscribed('starter_monthly')` төрлийн шалгалт
  ажиллахгүй байв. Одоо lookup key нь үнийн объект, дээд түвшний `lookup_key`
  болон `price` талбарын string хэлбэр гурвуулангаас уншигдана
  (`Data\Subscription::$lookupKey`). Constructor-т `lookupKey` параметр
  `priceId`-ийн дараа нэмэгдсэн — нэрлэсэн аргументаар үүсгэдэг бол
  өөрчлөлт шаардахгүй.
- **`php artisan byl:backfill-subscriptions`** — `byl_customer_id` бүхий
  billable бүрийн захиалгыг API-аас татаж локал хүснэгтийг нөхнө. Хоосон
  үлдсэн `lookup_key`-г засахад хэрэглэнэ.
- Үнэ солигдоход хуучин `lookup_key` үлдэхээ болив — хариунд өөр `price_id`
  ирвэл хосолсон утга зөрөхгүйн тулд `lookup_key` цэвэрлэгдэнэ.
- **Webhook payload-ийн бүтцийн зөрүү зассан** — дараах талбарууд бодит
  payload дээр үргэлж `null` буцаж байсан: `Data\CheckoutItem::$priceId`
  (`price.id`), `Data\CheckoutItem::$productName` (`product.name`),
  `Data\Checkout::$customerId` / `$customerEmail` (`customer.id`,
  `customer.email`).
- `Testing\SubscriptionFactory`, `Testing\CheckoutFactory` нь Byl-ээс ирдэг
  бодит payload-ийн бүтцийг хуулбарладаг болов (дээд түвшний `price_id`,
  `product_id`, `customer_id` талбарууд байхгүй). Хуучин хэлбэр дээр
  найдсан тест байвал шинэчилнэ. `SubscriptionFactory::withoutLookupKey()`
  нэмэгдэв.

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
