<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\Reservation;
use App\Modules\Orders\Services\ReservationService;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    private function owner(): User
    {
        return User::where('is_owner', true)->firstOrFail();
    }

    private function item(): Item
    {
        $item = Item::create(['name' => 'كوبيبة', 'kind' => 'product', 'unit' => 'كجم', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 100]);

        return $item;
    }

    private function payload(Item $item, string $date, float $qty = 2): array
    {
        return [
            'phone' => '01234567890',
            'name' => 'عميل حجز',
            'address' => 'عنوان',
            'area' => 'رشدي',
            'location_id' => 'roushdy',
            'reservation_date' => $date,
            'pay_method' => 'cash',
            'note' => 'بدون بصل',
            'lines' => [['item_id' => $item->id, 'qty' => $qty]],
        ];
    }

    public function test_staff_creates_reservation_and_converts_manually_once(): void
    {
        $item = $this->item();
        $tomorrow = now()->addDay()->toDateString();

        $this->actingAs($this->owner())
            ->post('/reservations', $this->payload($item, $tomorrow))
            ->assertSessionHasNoErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame($tomorrow, $reservation->reservation_date->toDateString());
        $this->assertSame(1, $reservation->lines()->count());

        // Manual "حوّل اوردر" after confirming with the customer.
        $this->actingAs($this->owner())
            ->post("/reservations/{$reservation->id}/convert")
            ->assertSessionHasNoErrors();

        $this->assertSame('converted', $reservation->fresh()->status);
        $this->assertNotNull($reservation->fresh()->converted_order_id);
        $order = OnlineOrder::firstOrFail();
        $this->assertSame('new', $order->status);
        $this->assertSame('roushdy', $order->location_id);

        // Converting again must NOT create a second order.
        $this->actingAs($this->owner())
            ->post("/reservations/{$reservation->id}/convert")
            ->assertSessionHasNoErrors();
        $this->assertSame(1, OnlineOrder::count());
    }

    public function test_scheduler_converts_due_today_reservations(): void
    {
        $item = $this->item();

        $service = $this->app->make(ReservationService::class);
        $reservation = $service->create($this->payload($item, now()->toDateString()), $this->owner()->id);

        $result = $service->convertDueToday();

        $this->assertCount(1, $result['converted']);
        $this->assertSame('converted', $reservation->fresh()->status);
        $this->assertSame(1, OnlineOrder::count());
    }

    public function test_factory_report_aggregates_quantities_and_excludes_cancelled(): void
    {
        $item = $this->item();
        $tomorrow = now()->addDay()->toDateString();
        $service = $this->app->make(ReservationService::class);
        $ownerId = $this->owner()->id;

        $service->create($this->payload($item, $tomorrow, 2), $ownerId);
        $service->create($this->payload($item, $tomorrow, 3), $ownerId);
        $cancelled = $service->create($this->payload($item, $tomorrow, 10), $ownerId);
        $service->cancel($cancelled);

        $report = $service->factoryReport($tomorrow);

        $this->assertCount(1, $report['items']);
        $this->assertSame(5.0, (float) $report['items'][0]['total_qty']);
        $this->assertSame(2, $report['reservations']->count());

        $this->actingAs($this->owner())
            ->get('/reports/factory-reservations?date='.$tomorrow)
            ->assertOk()
            ->assertSee('كوبيبة')
            ->assertSee('بدون بصل');
    }
}
