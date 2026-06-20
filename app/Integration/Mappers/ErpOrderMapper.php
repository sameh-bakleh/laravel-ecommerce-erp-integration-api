<?php

namespace App\Integration\Mappers;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class ErpOrderMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function toInternalAttributes(array $payload): array
    {
        $orderNo = trim((string) Arr::get($payload, 'Auftragsnummer', ''));
        if ($orderNo === '') {
            throw new InvalidArgumentException('Order payload missing Auftragsnummer.');
        }

        $status = $this->mapStatus((string) Arr::get($payload, 'Status', 'UNKNOWN'));

        $placedAt = Arr::get($payload, 'Auftragsdatum');
        if (! is_string($placedAt) || $placedAt === '') {
            throw new InvalidArgumentException('Order payload missing Auftragsdatum.');
        }

        return [
            'erp_order_number' => $orderNo,
            'status' => $status,
            'currency' => strtoupper((string) Arr::get($payload, 'Waehrung', 'EUR')),
            'customer_number' => Arr::get($payload, 'Kundennummer') ? (string) Arr::get($payload, 'Kundennummer') : null,
            'placed_at' => Carbon::parse($placedAt),
            'raw_payload' => $payload,
        ];
    }

    private function mapStatus(string $erpStatus): string
    {
        return match (strtoupper($erpStatus)) {
            'FREIGEGEBEN', 'RELEASED' => 'confirmed',
            'STORNIERT', 'CANCELLED' => 'cancelled',
            default => 'new',
        };
    }
}
