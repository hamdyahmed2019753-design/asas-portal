<?php

namespace App\Filament\Resources;

use App\Actions\ContractInterests\ConvertContractInterest;
use App\Enums\ContractInterestStatus;
use App\Filament\Resources\ContractInterestResource\Pages;
use App\Models\ContractInterest;
use App\Services\Portal\ContractInterestService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractInterestResource extends Resource
{
    protected static ?string $model = ContractInterest::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'طلبات الاهتمام';

    protected static ?string $modelLabel = 'طلب اهتمام';

    protected static ?string $pluralModelLabel = 'طلبات الاهتمام';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        // Interests are created by investors through the portal.
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'contract']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستثمر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.title')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ContractInterestStatus $state): string => $state->label())
                    ->color(fn (ContractInterestStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(ContractInterestStatus::cases())
                        ->mapWithKeys(fn (ContractInterestStatus $s) => [$s->value => $s->label()])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('markContacted')
                    ->label('تم التواصل')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (ContractInterest $record): bool => $record->status === ContractInterestStatus::Pending)
                    ->action(fn (ContractInterest $record) => app(ContractInterestService::class)->markContacted($record)),
                Tables\Actions\Action::make('convert')
                    ->label('تحويل إلى مشاركة')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (ContractInterest $record): bool => in_array(
                        $record->status,
                        [ContractInterestStatus::Pending, ContractInterestStatus::Contacted],
                        true
                    ))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ المشاركة')
                            ->numeric()
                            ->required()
                            ->minValue(fn (ContractInterest $record): float => (float) $record->contract->min_amount)
                            ->helperText(fn (ContractInterest $record): string => 'الحد الأدنى: '.money($record->contract->min_amount)),
                    ])
                    ->action(fn (ContractInterest $record, array $data) => app(ConvertContractInterest::class)
                        ->execute($record, (float) $data['amount'])),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ContractInterest $record): bool => in_array(
                        $record->status,
                        [ContractInterestStatus::Pending, ContractInterestStatus::Contacted],
                        true
                    ))
                    ->action(fn (ContractInterest $record) => app(ContractInterestService::class)->reject($record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-hand-raised')
            ->emptyStateHeading('لا توجد طلبات اهتمام')
            ->emptyStateDescription('ستظهر هنا طلبات اهتمام المستثمرين بالعقود.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractInterests::route('/'),
        ];
    }
}
