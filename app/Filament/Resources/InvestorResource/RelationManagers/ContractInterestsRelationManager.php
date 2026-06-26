<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of contracts this user flagged interest in. Internal only.
 */
class ContractInterestsRelationManager extends RelationManager
{
    protected static string $relationship = 'contractInterests';

    protected static ?string $title = 'العقود المهتم بها';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('contract'))
            ->columns([
                Tables\Columns\TextColumn::make('contract.title')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الاهتمام')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-bell')
            ->emptyStateHeading('لا توجد عقود مهتم بها')
            ->emptyStateDescription('لم يُبدِ هذا المستخدم اهتمامًا بأي عقد بعد.');
    }
}
