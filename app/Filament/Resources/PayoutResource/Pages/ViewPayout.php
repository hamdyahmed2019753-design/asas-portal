<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Enums\PayoutStatus;
use App\Filament\Resources\PayoutResource;
use App\Models\Payout;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (Payout $record): bool => $record->status !== PayoutStatus::Paid),
        ];
    }
}
