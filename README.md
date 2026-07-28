# Byl Laravel SDK

[Byl.mn](https://byl.mn) төлбөрийн системийн албан ёсны Laravel SDK. Нэхэмжлэх, checkout, харилцагч, захиалга (subscription), billing portal болон webhook-ийг Laravel-д зохицсон байдлаар ашиглана.

```php
use Byl\Laravel\Facades\Byl;

$checkout = Byl::checkouts()->builder()
    ->successUrl(route('purchase.success'))
    ->addPrice('starter_monthly')
    ->customer($customer->byl_customer_id)
    ->create();

return redirect()->away($checkout->url);
```

- API гарын авлага: <https://byl.mn/docs/api/>
- Дэмжигдэх хувилбар: PHP 8.2+, Laravel 11 / 12 / 13

## Агуулга

- [Суулгах](#суулгах)
- [Тохиргоо](#тохиргоо)
- [Нэхэмжлэх](#нэхэмжлэх)
- [Checkout](#checkout)
- [Харилцагч](#харилцагч)
- [Захиалга (Subscription)](#захиалга-subscription)
- [Billable trait](#billable-trait)
- [Billing portal](#billing-portal)
- [Webhook](#webhook)
- [Алдааны боловсруулалт](#алдааны-боловсруулалт)
- [Хэд хэдэн төсөл](#хэд-хэдэн-төсөл)
- [Тест бичих](#тест-бичих)

## Суулгах

```bash
composer require kitelab-dev/byl-laravel
```

Тохиргооны файлыг (шаардлагатай бол) хуулна:

```bash
php artisan vendor:publish --tag=byl-config
```

## Тохиргоо

`.env` файлд:

```dotenv
BYL_TOKEN=таны-api-token
BYL_PROJECT_ID=1
BYL_WEBHOOK_SECRET=таны-webhook-secret
```

- **`BYL_TOKEN`** — [API токен](https://byl.mn/user/api-tokens) хуудсанд үүсгэнэ. Токеныг зөвхөн нэг удаа харуулдаг.
- **`BYL_PROJECT_ID`** — төслийн тохиргоо цэснээс харна. Бүх endpoint төсөлд хамаарна.
- **`BYL_WEBHOOK_SECRET`** — webhook-ийн дэлгэрэнгүй хуудсанд байдаг гарын үсгийн түлхүүр (багийн бүх төсөлд ижил).

Нэмэлт тохиргоо (`config/byl.php`): `base_url` (жш: `https://byl.test`), `timeout`, `retry`, `webhook.route.*`.

> **Test горим.** Test горимын төсөлд гүйлгээ 50 төгрөгөөр хязгаарлагдана — код тал нь ижил, өөр токен/төсөл ашиглана.

## Нэхэмжлэх

```php
use Byl\Laravel\Facades\Byl;

$invoice = Byl::invoices()->create([
    'amount' => 25000,
    'description' => 'Гишүүнчлэлийн төлбөр',
    'auto_advance' => true,      // draft-аас шууд open болгоно
]);

$invoice->id;          // 5708
$invoice->number;      // "DEMO-0011"
$invoice->status;      // Byl\Laravel\Enums\InvoiceStatus::Open
$invoice->url;         // төлбөрийн хуудасны хаяг
$invoice->dueDate;     // Carbon
$invoice->isPaid();    // false

// Хураангуй хэлбэр
$invoice = Byl::invoices()->createFor(25000, 'Гишүүнчлэлийн төлбөр');

Byl::invoices()->find(5708);
Byl::invoices()->void(5708);      // төлөгдөөгүй нэхэмжлэхийг хүчингүй болгох
Byl::invoices()->delete(5708);
```

Нэхэмжлэх, checkout, portal session объектуудыг controller-оос шууд буцаахад тухайн хуудас руу redirect хийнэ:

```php
public function pay(Order $order)
{
    return Byl::invoices()->createFor($order->total, "Захиалга #{$order->id}");
}
```

## Checkout

Хэрэглэгчийн худалдан авалтыг Byl-ийн hosted checkout хуудсаар зохицуулна — төлбөр, купон, хаяг, баримт зэргийг Byl хийнэ.

```php
$checkout = Byl::checkouts()->builder()
    ->successUrl(route('purchase.success'))
    ->cancelUrl(route('cart'))
    ->addPriceData(15000, 'Гутал', quantity: 2)       // шууд үнэ
    ->addPriceId(3)                                   // Byl дээрх үнийн ID
    ->addPrice('starter_monthly')                     // lookup key
    ->collectPhoneNumber()
    ->collectDeliveryAddress()
    ->allowPromotionCodes()                           // хөнгөлөлтийн кодын талбар
    ->discount(5400, 'Хямдрал')                       // нийт дүнгээс хөнгөлөх
    ->clientReferenceId("order_{$order->id}")
    ->create();

return redirect()->away($checkout->url);
```

Массив хэлбэрээр ч дамжуулж болно:

```php
$checkout = Byl::checkouts()->create([
    'success_url' => route('purchase.success'),
    'items' => [
        ['price' => 'starter_monthly', 'quantity' => 1],
    ],
]);
```

Лавлах:

```php
$checkout = Byl::checkouts()->find(13338);

$checkout->status;              // CheckoutStatus::Open|Complete|Expired
$checkout->amountTotal;
$checkout->items;               // Collection<CheckoutItem>
$checkout->couponCodes;         // Collection<CouponCode>
$checkout->discountTotal();
```

> **Хөнгөлөлтийн код.** Бүтээгдэхүүний хямдралтай код ашиглах бол бүх item-д `price_id` (`addPriceId()`) хэрэглэнэ. Захиалгын нийт дүнгээс хөнгөлөх кодод `price_data` ч болно.

## Харилцагч

`client_reference_id`-д өөрийн хэрэглэгчийн ID-г дамжуулбал endpoint нь **upsert** шиг ажиллана — давхардал үүсгэхгүй тул checkout үүсгэхийн өмнө бүр удаа дуудаж болно.

```php
$customer = Byl::customers()->upsert([
    'email' => $user->email,
    'name' => $user->name,
    'client_reference_id' => (string) $user->id,
]);

$customer->id;                  // checkout-д дамжуулах customer_id

// Лавлах — хариунд эрхтэй захиалгууд хамт ирнэ
$customer = Byl::customers()->find(12);
$customer = Byl::customers()->findByClientReferenceId((string) $user->id);
$customer = Byl::customers()->findByClientReferenceIdOrNull((string) $user->id); // олдоогүй бол null

$customer->isSubscribed();                              // эрхтэй захиалга байгаа эсэх
$customer->subscriptionForLookupKey('starter_monthly');
$customer->subscriptionForProduct(7);
```

## Захиалга (Subscription)

Recurring үнэтэй checkout төлөгдөхөд subscription автоматаар үүснэ (`customer_id` заавал, ганц item):

```php
Byl::checkouts()->builder()
    ->customer($customer->id)
    ->addPrice('starter_monthly')
    ->successUrl(route('subscribe.success'))
    ->create();
```

Сунгалт / багц солилт (одоогийн subscription-ийг дамжуулна):

```php
Byl::checkouts()->builder()
    ->customer($customer->id)
    ->subscription($subscriptionId)
    ->addPrice('growth_monthly')   // өөр үнэ → багц солилт, ижил үнэ → сунгалт
    ->create();
```

Бусад үйлдлүүд:

```php
$subscription = Byl::subscriptions()->find(4);

$subscription->status;                  // SubscriptionStatus::Trialing|Active|PastDue|Canceled
$subscription->isEntitled();            // canceled-аас бусад бүх төлөв → true
$subscription->currentPeriodEnd;        // эрхийн дуусах хугацаа (Carbon)
$subscription->daysUntilPeriodEnd();
$subscription->cancelRequested();       // цуцлалт хүссэн ч мөчлөг дуусаагүй

// Жагсаалт (хуудаслалттай)
$page = Byl::subscriptions()->list(['status' => 'active']);
$page = Byl::subscriptions()->forCustomer(12, SubscriptionStatus::Active);

foreach ($page as $subscription) { /* ... */ }
$page->hasMorePages();

// Бүх хуудсыг lazy байдлаар
Byl::subscriptions()->all(['status' => 'active'])->each(fn ($subscription) => /* ... */);

// Туршилт (төлбөргүй) — 1–365 хоног
Byl::subscriptions()->startTrial(customerId: 12, priceId: 3, trialDays: 14);

// Цуцлах / буцаах (мөчлөгийн төгсгөлд хэрэгжинэ)
Byl::subscriptions()->cancel(4);
Byl::subscriptions()->resume(4);
```

> Хэрэглэгчийн эрхийг **`subscription.canceled`** webhook ирэх хүртэл нээлттэй байлга — `trialing`, `active`, `past_due` бүгд эрхтэйд тооцогдоно (`isEntitled()`).

## Billable trait

Laravel Cashier шиг модель дээрээ шууд ажиллах хувилбар. Захиалга нь локал `byl_subscriptions` хүснэгтэд тусах бөгөөд webhook ирэх бүрд автоматаар шинэчлэгддэг тул `$user->subscribed()` шалгалт **сүлжээнд гарахгүй**.

```bash
php artisan vendor:publish --tag=byl-migrations
php artisan migrate
```

```dotenv
BYL_BILLABLE_MODEL="App\Models\User"
```

```php
use Byl\Laravel\Billing\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### Эрхийн шалгалт (локал)

```php
$user->subscribed();                      // эрхтэй эсэх (trialing/active/past_due)
$user->subscribed('starter_monthly');     // тодорхой багц дээр
$user->subscribedToPrice(3);
$user->subscribedToProduct(7);
$user->onTrial();
$user->onGracePeriod();                   // цуцлалт хүссэн ч мөчлөг дуусаагүй
$user->pastDue();
$user->subscriptionEnded();               // эрх нь дууссан

$subscription = $user->bylSubscription(); // Eloquent модель
$subscription->current_period_end;        // Carbon
$subscription->daysUntilPeriodEnd();
$user->bylSubscriptions;                  // бүх захиалга (relation)
```

Route хамгаалах:

```php
Route::get('/dashboard', ...)->middleware('byl.subscribed');
Route::get('/pro', ...)->middleware('byl.subscribed:growth_monthly');
```

`config('byl.billable.redirect_to')` тохируулбал эрхгүй хэрэглэгчийг тэр хаяг руу чиглүүлнэ, эс бөгөөс 403 буцаана.

### Захиалга эхлүүлэх

```php
// Recurring checkout — төлөгдмөгц subscription үүсч webhook ирнэ
return $user->newSubscription('starter_monthly')
    ->successUrl(route('subscribe.success'))
    ->allowPromotionCodes()
    ->checkout();                 // CreatedCheckout (шууд return хийвэл redirect)

// Хэд хэдэн мөчлөгийг нэг дор
$user->newSubscription('starter_monthly')->cycles(3)->checkout();

// Төлбөргүй туршилт — үнийн ID шаардлагатай (lookup key биш)
$user->newSubscription(priceId: 3)->startTrial(14);
```

Харилцагч Byl дээр байхгүй бол `checkout()` / `startTrial()` нь `client_reference_id`-аар upsert хийж `byl_customer_id`-г автоматаар хадгална.

### Сунгах, багц солих, цуцлах

```php
$subscription = $user->bylSubscription();

return $subscription->renewCheckout(3);                  // 3 мөчлөгөөр сунгах
return $subscription->swapCheckout('growth_monthly');    // багц солих (кредит хасагдана)

$subscription->cancel();     // мөчлөгийн төгсгөлд, локал төлөв шууд шинэчлэгдэнэ
$subscription->resume();
$subscription->syncFromByl(); // Byl дээрх төлөвөөр дахин таарах
```

> **Cashier-аас ялгаатай тал:** Монголд автомат суутгал байдаггүй тул `swap()`, `renew()` нь шууд төлбөр авахын оронд **checkout буцаадаг** (`swapCheckout()`, `renewCheckout()`) — хэрэглэгч төлбөрөө өөрөө төлнө.

| Cashier | Byl SDK |
| --- | --- |
| `$user->subscribed('default')` | `$user->subscribed('starter_monthly')` |
| `$user->subscription()` | `$user->bylSubscription()` |
| `$user->newSubscription(...)->checkout()` | `$user->newSubscription('starter_monthly')->checkout()` |
| `$user->newSubscription(...)->trialDays(14)->create()` | `$user->newSubscription($priceId)->startTrial(14)` |
| `$subscription->swap($price)` | `$subscription->swapCheckout($price)` |
| — | `$subscription->renewCheckout($cycles)` |
| `$user->redirectToBillingPortal()` | `$user->redirectToBillingPortal()` |
| `$user->createAsStripeCustomer()` | `$user->createOrGetBylCustomer()` |

### Байгаа өгөгдлөө нөхөх / өөр модель холбох

```php
$user->syncBylSubscriptions();     // Byl дээрх захиалгуудыг локал хүснэгтэд татах
$user->asBylCustomer();            // API-аас харилцагчийн мэдээлэл (эрхтэй захиалгын хамт)
$user->syncBylCustomerDetails();   // нэр/и-мэйл/утсаа Byl дээр шинэчлэх
```

`client_reference_id` нь өгөгдмөлөөр primary key. Өөрөөр холбох бол:

```php
// config/byl.php
'billable' => ['client_reference_column' => 'uuid'],

// эсвэл бүрэн өөрийн логик (AppServiceProvider::boot)
Byl::resolveBillableUsing(fn (string $reference) => Team::where('slug', $reference)->first());
```

Модель дээр `bylCustomerEmail()`, `bylCustomerName()`, `bylCustomerPhone()`, `bylClientReferenceId()` method-уудыг дарж бичиж болно.

## Billing portal

Хэрэглэгч захиалгаа өөрөө удирдах self-service хуудас. Session нь 30 минут хүчинтэй тул орох бүрд шинээр үүсгэнэ:

```php
Route::get('/billing', function () {
    $customer = Byl::customers()->findByClientReferenceId((string) auth()->id());

    return Byl::billingPortal()->createSession($customer->id);   // portal руу redirect
});
```

Эсвэл URL-ыг өөрөө хэрэглэнэ:

```php
$session = Byl::billingPortal()->createSession($customer->id);

$session->url;
$session->expiresAt;
```

## Webhook

Package нь `POST /byl/webhook` endpoint-ийг автоматаар бүртгэж, `Byl-Signature` гарын үсгийг шалгаад Laravel event болгон илгээнэ. Byl веб хуудсанд webhook нэмэхдээ энэ хаягийг бүртгэнэ.

```php
// app/Providers/AppServiceProvider.php
use Byl\Laravel\Events\CheckoutCompleted;
use Byl\Laravel\Events\SubscriptionCanceled;
use Illuminate\Support\Facades\Event;

Event::listen(function (CheckoutCompleted $event) {
    $checkout = $event->checkout;

    Order::where('id', $checkout->clientReferenceId)->update(['paid_at' => now()]);
});

Event::listen(function (SubscriptionCanceled $event) {
    User::where('id', $event->subscription->customer?->clientReferenceId)
        ->update(['plan' => null]);      // эрхийг энэ үед хаана
});
```

Event классууд (`Byl\Laravel\Events\`):

| Event класс | Byl-ийн event |
| --- | --- |
| `InvoicePaid` | `invoice.paid` |
| `InvoiceVoided` | `invoice.void` |
| `CheckoutCompleted` | `checkout.completed` |
| `CheckoutExpired` | `checkout.expired` |
| `SubscriptionCreated` | `subscription.created` |
| `SubscriptionRenewed` | `subscription.renewed` |
| `SubscriptionUpdated` | `subscription.updated` |
| `SubscriptionRenewalDue` | `subscription.renewal_due` |
| `SubscriptionPastDue` | `subscription.past_due` |
| `SubscriptionCanceled` | `subscription.canceled` |
| `WebhookReceived` | бүх event (танигдаагүй төрөл ч) |

Хаяг эсвэл middleware-г тохируулах:

```php
// config/byl.php
'webhook' => [
    'route' => [
        'enabled' => true,
        'path' => 'integrations/byl/webhook',
        'middleware' => [],
    ],
],
```

Өөрийн controller хэрэглэх бол route-г хааж, `byl-signature` middleware-ийг залгана:

```php
Route::post('/my/byl-webhook', MyWebhookController::class)->middleware('byl-signature');
```

Byl амжилтгүй хүсэлтийг 3 хүртэл удаа дахин илгээдэг тул listener-ээ idempotent бичээрэй. Эсвэл `BYL_WEBHOOK_PREVENT_DUPLICATES=true` болгож ижил event ID-г хаана (sync listener-тэй үед найдвартай).

## Алдааны боловсруулалт

Бүх алдаа `Byl\Laravel\Exceptions\BylException` interface-ийг хэрэгжүүлдэг:

```php
use Byl\Laravel\Exceptions\BylException;
use Byl\Laravel\Exceptions\ValidationException;

try {
    Byl::invoices()->create(['amount' => 5]);
} catch (ValidationException $exception) {
    $exception->status();               // 422
    $exception->errorFor('amount');     // "The amount must be at least 10."
} catch (BylException $exception) {
    report($exception);
}
```

| Exception | Статус |
| --- | --- |
| `AuthenticationException` | 401 — токен буруу/хүчингүй |
| `AuthorizationException` | 403 — эрхгүй |
| `NotFoundException` | 404 — олдсонгүй |
| `ConflictException` | 409 — жш: цуцлагдсан захиалгыг дахин цуцлах |
| `ValidationException` | 422 — параметер буруу |
| `RateLimitException` | 429 — `retryAfter()` |
| `ServerException` | 5xx |
| `ApiException` | бусад HTTP алдаа |
| `ConnectionException` | сүлжээ/timeout |
| `ConfigurationException` | токен/төслийн ID дутуу |

Холболтын алдаа, 429 болон 5xx хариу дээр SDK автоматаар (default 2 удаа) дахин хүсэлт илгээнэ — `config('byl.retry')`.

## Хэд хэдэн төсөл

```php
Byl::project(7)->invoices()->createFor(1000);              // өөр төсөл, ижил токен
Byl::project(7, 'other-token')->invoices()->find(1);       // өөр төсөл, өөр токен
Byl::withToken('temp-token')->customers()->find(12);
```

## Тест бичих

`Byl::fake()` нь сүлжээнд гарахгүйгээр бодит бүтэцтэй хариу буцааж, хүсэлтүүдийг шалгах боломж өгнө:

```php
use Byl\Laravel\Facades\Byl;

it('захиалга үүсгэхэд checkout үүснэ', function () {
    $byl = Byl::fake();

    $this->post('/orders', ['product' => 'starter'])->assertRedirect();

    $byl->assertCheckoutCreated(fn (array $payload) => $payload['items'][0]['price'] === 'starter_monthly');
});
```

Хариуг өөрөө тодорхойлох, алдаа симуляц хийх:

```php
$byl = Byl::fake()
    ->respond('GET', 'customers/*', ['id' => 12, 'email' => 'a@b.mn'])
    ->respondWithError('POST', 'invoices', 422, [
        'message' => 'The amount field is required.',
        'errors' => ['amount' => ['The amount field is required.']],
    ]);

$byl->assertSent('GET', 'customers/12');
$byl->assertNotSent('POST', 'checkouts');
$byl->assertSentCount(1);
$byl->assertSentForProject(7, 'POST', 'invoices');
$byl->recorded('POST', 'invoices');        // Collection
```

Webhook-ийг гарын үсэгтэйгээр туршина:

```php
use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Events\SubscriptionCanceled;
use Byl\Laravel\Testing\FakeWebhook;

it('захиалга цуцлагдахад эрх хаагдана', function () {
    $webhook = FakeWebhook::subscription(WebhookEventType::SubscriptionCanceled, [
        'id' => 4,
        'status' => 'canceled',
    ]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    expect($user->fresh()->plan)->toBeNull();
});
```

`FakeWebhook::invoicePaid()`, `FakeWebhook::checkoutCompleted()`, `FakeWebhook::make($type, $object, $data)` мөн бэлэн байна. Payload-ыг `InvoiceFactory`, `CheckoutFactory`, `CustomerFactory`, `SubscriptionFactory` (`Byl\Laravel\Testing\`) хэлбэрээр өөрчилнө.

## Хөгжүүлэлт

```bash
composer install
composer test        # vendor/bin/pest
composer lint        # vendor/bin/pint
```

## Лиценз

MIT — [LICENSE.md](LICENSE.md).
