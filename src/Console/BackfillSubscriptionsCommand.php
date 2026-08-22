<?php

namespace Byl\Laravel\Console;

use Byl\Laravel\Exceptions\BylException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Byl-ийн webhook-д ирээгүй (эсвэл алдагдсан) талбаруудыг API-аас нөхнө.
 * Жишээ нь `lookup_key` нь webhook-д ирдэггүй байсан үеийн мөрүүд.
 */
class BackfillSubscriptionsCommand extends Command
{
    protected $signature = 'byl:backfill-subscriptions
                            {--chunk=100 : Нэг удаад боловсруулах billable-ийн тоо}';

    protected $description = 'Byl дээрх захиалгуудыг татаж локал байгаа мөрүүдийг нөхнө.';

    public function handle(Repository $config): int
    {
        /** @var class-string<Model>|null $model */
        $model = $config->get('byl.billable.model');

        if ($model === null || ! class_exists($model)) {
            $this->components->error('`byl.billable.model` тохируулаагүй байна.');

            return self::FAILURE;
        }

        if (! method_exists($model, 'syncBylSubscriptions')) {
            $this->components->error(sprintf('`%s` модель Billable trait хэрэглээгүй байна.', $model));

            return self::FAILURE;
        }

        $billables = 0;
        $subscriptions = 0;
        $failed = 0;

        $model::query()
            ->whereNotNull('byl_customer_id')
            ->chunkById((int) $this->option('chunk'), function (Collection $chunk) use (&$billables, &$subscriptions, &$failed) {
                foreach ($chunk as $billable) {
                    $billables++;

                    // Нэг харилцагч дээрх алдаа бүх backfill-ийг зогсоох ёсгүй.
                    try {
                        $subscriptions += $billable->syncBylSubscriptions()->count();
                    } catch (BylException $e) {
                        $failed++;

                        $this->components->warn(sprintf('#%s — %s', $billable->getKey(), $e->getMessage()));
                    }
                }
            });

        $this->components->info(sprintf(
            '%d billable шалгаж, %d захиалгыг шинэчлэв.', $billables, $subscriptions
        ));

        if ($failed > 0) {
            $this->components->warn(sprintf('%d billable дээр алдаа гарсан тул дахин ажиллуулна уу.', $failed));
        }

        return self::SUCCESS;
    }
}
