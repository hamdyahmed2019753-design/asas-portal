<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of users who flagged interest in this (upcoming) contract.
 * Internal only — no sending, no mutation.
 */
class ContractInterestsRelationManager extends RelationManager
{
    protected static string $relationship = 'contractInterests';

    protected static ?string $title = 'المهتمون';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الاهتمام')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-bell')
            ->emptyStateHeading('لا يوجد مهتمون بعد')
            ->emptyStateDescription('سيظهر هنا من ضغط «مهتم» على هذا العقد.');
    }
}
