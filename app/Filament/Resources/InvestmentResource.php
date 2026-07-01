<?php

namespace App\Filament\Resources;

use App\Actions\Investments\ApproveInvestment;
use App\Actions\Investments\RejectInvestment;
use App\Enums\InvestmentStatus;
use App\Exceptions\InvestmentAlreadyProcessedException;
use App\Filament\Resources\InvestmentResource\Pages;
use App\Filament\Resources\InvestmentResource\RelationManagers;
use App\Models\Document;
use App\Models\Investment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المشاركات';

    protected static ?string $modelLabel = 'مشاركة';

    protected static ?string $pluralModelLabel = 'المشاركات';

    protected static ?int $navigationSort = 2;

    /**
     * A record is locked for editing once it has been approved or rejected.
     */
    private static function isLocked(?Investment $record): bool
    {
        return $record !== null && $record->status !== InvestmentStatus::Pending;
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(InvestmentStatus::cases())
            ->mapWithKeys(fn (InvestmentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * Signed download link to the investor's uploaded transfer receipt (the
     * receipt is a private Document, downloadable by admins via its policy).
     */
    private static function receiptLink(Investment $record): HtmlString
    {
        if (blank($record->receipt_path)) {
            return new HtmlString('<span style="color:#9ba3a3;">لا يوجد إيصال مرفوع</span>');
        }

        $document = Document::where('path', $record->receipt_path)->latest()->first();

        if ($document === null) {
            return new HtmlString('<span style="color:#9ba3a3;">الإيصال غير متاح</span>');
        }

        $url = URL::temporarySignedRoute('documents.download', now()->addMinutes(15), ['document' => $document]);

        return new HtmlString('<a href="'.e($url).'" target="_blank" style="color:#15A878;text-decoration:underline;">تحميل إيصال التحويل</a>');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المشاركة')
                ->description('لا يمكن تعديل هذه البيانات بعد اعتماد المشاركة أو رفضها.')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('المستثمر')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Investment $record): bool => self::isLocked($record)),
                    Forms\Components\Select::make('contract_id')
                        ->label('العقد')
                        ->relationship('contract', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Investment $record): bool => self::isLocked($record)),
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('ر.س')
                        ->disabled(fn (?Investment $record): bool => self::isLocked($record)),
                ])
                ->columns(2),

            Forms\Components\Section::make('الحالة')
                ->schema([
                    Forms\Components\Placeholder::make('status')
                        ->label('الحالة')
                        ->content(fn (?Investment $record): string => $record?->status?->label() ?? '—'),
                    Forms\Components\Placeholder::make('approved_at')
                        ->label('تاريخ الاعتماد')
                        ->content(fn (?Investment $record): string => $record?->approved_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('rejected_at')
                        ->label('تاريخ الرفض')
                        ->content(fn (?Investment $record): string => $record?->rejected_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->content(fn (?Investment $record): string => $record?->rejection_reason ?? '—'),
                ])
                ->columns(2)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Section::make('الدفع والحصص')
                ->schema([
                    Forms\Components\Placeholder::make('shares')
                        ->label('عدد الحصص')
                        ->content(fn (?Investment $record): string => (string) ($record?->shares ?? '—')),
                    Forms\Components\Placeholder::make('receipt')
                        ->label('إيصال المستثمر')
                        ->content(fn (?Investment $record): HtmlString => $record ? self::receiptLink($record) : new HtmlString('—')),
                    Forms\Components\Placeholder::make('payment_confirmed_at')
                        ->label('تاريخ تأكيد السداد')
                        ->content(fn (?Investment $record): string => $record?->payment_confirmed_at?->format('Y-m-d H:i') ?? '—'),
                ])
                ->columns(3)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Section::make('التواريخ')
                ->schema([
                    Forms\Components\Placeholder::make('start_date')
                        ->label('تاريخ البدء')
                        ->content(fn (?Investment $record): string => $record?->start_date?->format('Y-m-d') ?? '—'),
                    Forms\Components\Placeholder::make('end_date')
                        ->label('تاريخ الانتهاء')
                        ->content(fn (?Investment $record): string => $record?->end_date?->format('Y-m-d') ?? '—'),
                ])
                ->columns(2)
                ->visibleOn(['edit', 'view']),
        ]);
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
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payouts_count')
                    ->label('التوزيعات')
                    ->counts('payouts')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => $state.' توزيعات'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('تاريخ الاعتماد')
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rejected_at')
                    ->label('تاريخ الرفض')
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('contract')
                    ->label('العقد')
                    ->relationship('contract', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user')
                    ->label('المستثمر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ التقديم')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (Investment $record): bool => in_array($record->status->value, InvestmentStatus::awaitingApproval(), true))
                    ->modalHeading('اعتماد المشاركة وتأكيد السداد')
                    ->modalDescription('راجِع إيصال المستثمر، وأرفق إثبات الدفع إن رغبت. عند الاعتماد يُثبَّت السداد ويُولَّد جدول التوزيعات ويُمنح المستخدم دور «مستثمر». لا يمكن التراجع.')
                    ->modalSubmitActionLabel('اعتماد')
                    ->form([
                        Forms\Components\Placeholder::make('receipt_link')
                            ->label('إيصال المستثمر')
                            ->content(fn (Investment $record): HtmlString => self::receiptLink($record)),
                        Forms\Components\FileUpload::make('payment_proof')
                            ->label('إثبات الدفع (اختياري)')
                            ->disk('local')
                            ->directory('payment-proofs')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120),
                    ])
                    ->action(function (Investment $record, array $data): void {
                        try {
                            app(ApproveInvestment::class)->execute($record, $data['payment_proof'] ?? null);
                            Notification::make()
                                ->title('تم اعتماد المشاركة بنجاح')
                                ->body('تم تأكيد السداد وتوليد جدول التوزيعات.')
                                ->success()
                                ->send();
                        } catch (InvestmentAlreadyProcessedException) {
                            Notification::make()
                                ->title('تعذّر الاعتماد')
                                ->body('هذه المشاركة تمت معالجتها بالفعل.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (Investment $record): bool => ! in_array($record->status, [InvestmentStatus::Approved, InvestmentStatus::Rejected], true))
                    ->modalHeading('رفض المشاركة')
                    ->modalSubmitActionLabel('رفض')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Investment $record, array $data): void {
                        try {
                            app(RejectInvestment::class)->execute($record, $data['rejection_reason']);
                            Notification::make()
                                ->title('تم رفض المشاركة')
                                ->success()
                                ->send();
                        } catch (InvestmentAlreadyProcessedException) {
                            Notification::make()
                                ->title('تعذّر الرفض')
                                ->body('هذه المشاركة تمت معالجتها بالفعل.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Investment $record): bool => $record->status === InvestmentStatus::Pending),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('لا توجد مشاركات بعد')
            ->emptyStateDescription('ستظهر هنا طلبات المشاركة فور تقديمها.');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'view' => Pages\ViewInvestment::route('/{record}'),
            'edit' => Pages\EditInvestment::route('/{record}/edit'),
        ];
    }
}
