<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of the contract's investments. Approving / managing investments
 * lives in InvestmentResource (with the ApproveInvestment / RejectInvestment
 * actions) — no business logic or mutation here.
 */
class InvestmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'investments';

    protected static ?string $title = 'المشاركات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستثمر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('لا توجد مشاركات على هذا العقد')
            ->emptyStateDescription('ستظهر هنا المشاركات المقدّمة على هذا العقد.');
    }
}
