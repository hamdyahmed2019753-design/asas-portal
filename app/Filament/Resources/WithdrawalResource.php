<?php

namespace App\Filament\Resources;

use App\Enums\WithdrawalStatus;
use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Services\Portal\WithdrawalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'طلبات السحب';

    protected static ?string $modelLabel = 'طلب سحب';

    protected static ?string $pluralModelLabel = 'طلبات السحب';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', WithdrawalStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('طلب السحب')
                ->schema([
                    Forms\Components\Placeholder::make('user')
                        ->label('المستثمر')
                        ->content(fn (?Withdrawal $record): string => $record?->user?->name ?? '—'),
                    Forms\Components\Placeholder::make('amount')
                        ->label('المبلغ')
                        ->content(fn (?Withdrawal $record): string => $record ? money($record->amount) : '—'),
                    Forms\Components\Placeholder::make('status')
                        ->label('الحالة')
                        ->content(fn (?Withdrawal $record): string => $record?->status?->label() ?? '—'),
                    Forms\Components\Placeholder::make('processed_at')
                        ->label('تاريخ المعالجة')
                        ->content(fn (?Withdrawal $record): string => $record?->processed_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->content(fn (?Withdrawal $record): string => $record?->rejection_reason ?? '—'),
                ])
                ->columns(2),

            Forms\Components\Section::make('الحساب البنكي (لقطة عند الطلب)')
                ->schema([
                    Forms\Components\Placeholder::make('bank_name')
                        ->label('البنك')
                        ->content(fn (?Withdrawal $record): string => $record?->bank_name ?? '—'),
                    Forms\Components\Placeholder::make('bank_account_name')
                        ->label('اسم الحساب')
                        ->content(fn (?Withdrawal $record): string => $record?->bank_account_name ?? '—'),
                    Forms\Components\Placeholder::make('bank_iban')
                        ->label('الآيبان')
                        ->content(fn (?Withdrawal $record): string => $record?->bank_iban ?? '—'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('المستثمر')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => money($state))->sortable(),
                Tables\Columns\ViewColumn::make('status')->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge')->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->label('البنك')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(WithdrawalStatus::cases())
                        ->mapWithKeys(fn (WithdrawalStatus $s) => [$s->value => $s->label()])->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('markPaid')
                    ->label('اعتماد وتحويل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('viewAny')
                    ->visible(fn (Withdrawal $record): bool => $record->status === WithdrawalStatus::Pending)
                    ->modalHeading('اعتماد طلب السحب')
                    ->modalDescription('حوّل المبلغ إلى حساب المستثمر البنكي وأرفق إيصال التحويل.')
                    ->modalSubmitActionLabel('تأكيد الدفع')
                    ->form([
                        Forms\Components\FileUpload::make('receipt')
                            ->label('إيصال التحويل')
                            ->disk('local')
                            ->directory('withdrawal-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        app(WithdrawalService::class)->markPaid($record, $data['receipt'] ?? null);
                        Notification::make()->title('تم اعتماد السحب')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('viewAny')
                    ->visible(fn (Withdrawal $record): bool => $record->status === WithdrawalStatus::Pending)
                    ->modalHeading('رفض طلب السحب')
                    ->modalDescription('سيُعاد المبلغ إلى محفظة المستثمر.')
                    ->modalSubmitActionLabel('رفض')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('سبب الرفض')->required()->rows(3),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        app(WithdrawalService::class)->reject($record, $data['reason']);
                        Notification::make()->title('تم رفض السحب وإعادة المبلغ للمحفظة')->success()->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->emptyStateHeading('لا توجد طلبات سحب')
            ->emptyStateDescription('ستظهر هنا طلبات السحب فور تقديمها.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
