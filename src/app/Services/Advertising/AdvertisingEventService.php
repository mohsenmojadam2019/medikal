<?php
namespace App\Services\Advertising;
use App\Models\AdvertisingEvent;
class AdvertisingEventService { public function record(array $data): AdvertisingEvent { return AdvertisingEvent::create($data); } }
