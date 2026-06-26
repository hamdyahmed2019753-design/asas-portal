<?php

namespace App\Filament\Resources;

use App\Enums\ContractStatus;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'العقود';

    protected static ?string $modelLabel = 'عقد';

    protected static ?string $pluralModelLabel = 'العقود';

    protected static ?int $navigationSort = 1;

    /**
     * Status value => Arabic label, sourced from the enum (single source of truth).
     *
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(ContractStatus::cases())
            ->mapWithKeys(fn (ContractStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات العقد')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('اسم العقد')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('activity_type')
                        ->label('نوع النشاط')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options(self::statusOptions())
                        ->default(ContractStatus::Upcoming->value)
                        ->required()
                        ->live(),
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('المبالغ والمدة')
                ->schema([
                    Forms\Components\TextInput::make('target_amount')
                        ->label('النصاب المستهدف')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('ر.س'),
                    Forms\Components\TextInput::make('min_amount')
                        ->label('أقل مشاركة')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('ر.س'),
                    Forms\Components\TextInput::make('max_amount')
                        ->label('أقصى مشاركة')
                        ->numeric()
                        ->minValue(0)
                        ->gte('min_amount')
                        ->suffix('ر.س'),
                    Forms\Components\TextInput::make('expected_return')
                        ->label('نسبة العائد المتوقعة')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('للعرض فقط — لا تدخل في أي حساب.'),
                    Forms\Components\TextInput::make('duration_months')
                        ->label('مدة العقد (بالشهور)')
                        ->integer()
                        ->required()
                        ->minValue(1),
                    Forms\Components\TextInput::make('payouts_count')
                        ->label('عدد التوزيعات')
                        ->integer()
                        ->required()
                        ->default(4)
                        ->minValue(1),
                ])
                ->columns(2),

            Forms\Components\Section::make('التوقيت')
                ->schema([
                    Forms\Components\DateTimePicker::make('opens_at')
                        ->label('موعد الفتح')
                        ->helperText('للعدّاد التنازلي للعقود القادمة.'),
                ])
                ->visible(fn (Forms\Get $get): bool => $get('status') === ContractStatus::Upcoming->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('اسم العقد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activity_type')
                    ->label('نوع النشاط')
                    ->searchable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_amount')
                    ->label('النصاب')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('أقل مشاركة')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->label('أقصى مشاركة')
                    ->formatStateUsing(fn ($state): string => filled($state) ? money($state) : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration_months')
                    ->label('المدة')
                    ->suffix(' شهر')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payouts_count')
                    ->label('التوزيعات')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('investments_count')
                    ->label('المشاركات')
                    ->counts('investments')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('نوع النشاط')
                    ->options(fn (): array => Contract::query()
                        ->whereNotNull('activity_type')
                        ->distinct()
                        ->orderBy('activity_type')
                        ->pluck('activity_type', 'activity_type')
                        ->all()),
                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('لا توجد عقود بعد')
            ->emptyStateDescription('ابدأ بطرح أول عقد ليظهر هنا.');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvestmentsRelationManager::class,
            RelationManagers\ContractInterestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
