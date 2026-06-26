<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of the investor's investments. Approving / managing lives in
 * InvestmentResource (with its actions) — no mutation here.
 */
class InvestmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'investments';

    protected static ?string $title = 'المشاركات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('contract'))
            ->columns([
                Tables\Columns\TextColumn::make('contract.title')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('لا توجد مشاركات')
            ->emptyStateDescription('لم يقدّم هذا المستخدم أي مشاركة بعد.');
    }
}
