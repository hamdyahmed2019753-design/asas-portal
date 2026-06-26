<?php

namespace App\Filament\Resources;

use App\Actions\Payouts\MarkPayoutPaid;
use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Exceptions\PayoutAlreadyPaidException;
use App\Exceptions\PayoutAmountMissingException;
use App\Filament\Resources\PayoutResource\Pages;
use App\Filament\Resources\PayoutResource\Widgets\PayoutStats;
use App\Models\Contract;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'التوزيعات';

    protected static ?string $modelLabel = 'توزيعة';

    protected static ?string $pluralModelLabel = 'التوزيعات';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        // Payouts are generated automatically when an investment is approved.
        return false;
    }

    private static function isPaid(?Payout $record): bool
    {
        return $record !== null && $record->status === PayoutStatus::Paid;
    }

    private static function isCapital(?Payout $record): bool
    {
        return $record !== null && $record->type === PayoutType::Capital;
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(PayoutStatus::cases())
            ->mapWithKeys(fn (PayoutStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return collect(PayoutType::cases())
            ->mapWithKeys(fn (PayoutType $type) => [$type->value => $type->label()])
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['investment.user', 'investment.contract']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات التوزيعة')
                ->schema([
                    Forms\Components\Placeholder::make('investor')
                        ->label('المستثمر')
                        ->content(fn (?Payout $record): string => $record?->investment?->user?->name ?? '—'),
                    Forms\Components\Placeholder::make('contract')
                        ->label('العقد')
                        ->content(fn (?Payout $record): string => $record?->investment?->contract?->title ?? '—'),
                    Forms\Components\Placeholder::make('investment')
                        ->label('المشاركة')
                        ->content(fn (?Payout $record): string => $record?->investment
                            ? 'مشاركة #'.$record->investment_id.' — '.money($record->investment->amount)
                            : '—'),
                ])
                ->columns(3)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Section::make('التوزيعة')
                ->schema([
                    Forms\Components\Placeholder::make('type')
                        ->label('النوع')
                        ->content(fn (?Payout $record): string => $record?->type_label ?? '—'),
                    Forms\Components\Placeholder::make('sequence')
                        ->label('التسلسل')
                        ->content(fn (?Payout $record): string => $record?->sequence !== null ? (string) $record->sequence : '—'),
                    Forms\Components\DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->required()
                        ->disabled(fn (?Payout $record): bool => self::isPaid($record)),
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ر.س')
                        ->disabled(fn (?Payout $record): bool => self::isPaid($record) || self::isCapital($record))
                        ->helperText(fn (?Payout $record): ?string => self::isCapital($record)
                            ? 'رأس المال — غير قابل للتعديل.'
                            : null),
                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull()
                        ->disabled(fn (?Payout $record): bool => self::isPaid($record)),
                ])
                ->columns(2),

            Forms\Components\Section::make('الحالة')
                ->schema([
                    Forms\Components\Placeholder::make('status')
                        ->label('الحالة')
                        ->content(fn (?Payout $record): string => $record?->status?->label() ?? '—'),
                    Forms\Components\Placeholder::make('paid_at')
                        ->label('تاريخ الدفع')
                        ->content(fn (?Payout $record): string => $record?->paid_at?->format('Y-m-d H:i') ?? '—'),
                ])
                ->columns(2)
                ->visibleOn(['edit', 'view']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investment.user.name')
                    ->label('المستثمر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('investment.contract.title')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\ViewColumn::make('type')
                    ->label('النوع')
                    ->view('filament.tables.columns.asas-enum-badge'),
                Tables\Columns\TextColumn::make('sequence')
                    ->label('التسلسل')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => filled($state) ? money($state) : '—')
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('تاريخ الدفع')
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(self::typeOptions()),
                Tables\Filters\SelectFilter::make('contract')
                    ->label('العقد')
                    ->options(fn (): array => Contract::orderBy('title')->pluck('title', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $contractId) => $q
                            ->whereHas('investment', fn (Builder $q) => $q->where('contract_id', $contractId)))),
                Tables\Filters\Filter::make('due_only')
                    ->label('المستحقة فقط')
                    ->query(fn (Builder $query): Builder => $query->where('status', PayoutStatus::Due->value)),
                Tables\Filters\Filter::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('تعليم كمدفوع')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (Payout $record): bool => $record->status !== PayoutStatus::Paid)
                    ->requiresConfirmation()
                    ->modalHeading('تعليم التوزيعة كمدفوعة')
                    ->modalDescription('تأكيد يدوي فقط — لا يوجد أي تحويل مالي في النظام.')
                    ->modalSubmitActionLabel('تعليم كمدفوع')
                    ->action(function (Payout $record): void {
                        try {
                            app(MarkPayoutPaid::class)->execute($record);
                            Notification::make()
                                ->title('تم تعليم التوزيعة كمدفوعة')
                                ->success()
                                ->send();
                        } catch (PayoutAmountMissingException) {
                            Notification::make()
                                ->title('تعذّر الدفع')
                                ->body('لا يمكن دفع توزيعة ربح بدون تحديد المبلغ أولًا.')
                                ->danger()
                                ->send();
                        } catch (PayoutAlreadyPaidException) {
                            Notification::make()
                                ->title('تعذّر الدفع')
                                ->body('هذه التوزيعة مدفوعة بالفعل.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Payout $record): bool => $record->status !== PayoutStatus::Paid),
            ])
            ->defaultSort('due_date')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('لا توجد توزيعات بعد')
            ->emptyStateDescription('تُولَّد التوزيعات تلقائيًا عند اعتماد المشاركات.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<class-string>
     */
    public static function getWidgets(): array
    {
        return [
            PayoutStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'view' => Pages\ViewPayout::route('/{record}'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
