<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only list of the investment's payouts (profit rows + capital row).
 * Managing payouts (amounts / marking paid) lives in PayoutResource with the
 * MarkPayoutPaid action — no mutation here.
 */
class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'التوزيعات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state, $record): string => $record->type_label),
                Tables\Columns\TextColumn::make('sequence')
                    ->label('التسلسل')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => filled($state) ? money($state) : '—'),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('تاريخ الدفع')
                    ->dateTime('Y-m-d')
                    ->placeholder('—'),
            ])
            ->defaultSort('due_date')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('لا توجد توزيعات')
            ->emptyStateDescription('تُولَّد التوزيعات تلقائيًا عند اعتماد المشاركة.');
    }
}
