<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentStatus;
use App\Enums\KycState;
use App\Enums\KycStatus;
use App\Filament\Resources\InvestorResource\Pages;
use App\Filament\Resources\InvestorResource\RelationManagers;
use App\Filament\Resources\InvestorResource\Widgets\InvestorStats;
use App\Models\User;
use App\Services\Portal\KycService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class InvestorResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'المستثمرون';

    protected static ?string $modelLabel = 'مستثمر';

    protected static ?string $pluralModelLabel = 'المستثمرون';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, string>
     */
    private const ROLE_LABELS = [
        'admin' => 'مدير',
        'investor' => 'مستثمر',
        'member' => 'عضو',
    ];

    public static function canCreate(): bool
    {
        // Investors register through the portal; the admin only edits / verifies them.
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        // Scope to investors and members (exclude pure admins).
        return parent::getEloquentQuery()->role(['investor', 'member']);
    }

    /**
     * @return array<string, string>
     */
    private static function kycOptions(): array
    {
        return collect(KycStatus::cases())
            ->mapWithKeys(fn (KycStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * Signed (10-minute) download links for the investor's KYC documents.
     */
    private static function documentLinks(?User $record): HtmlString
    {
        if ($record === null) {
            return new HtmlString('—');
        }

        $links = [];
        foreach (KycService::DOCUMENTS as $type => $label) {
            if (filled($record->kycDocumentPath($type))) {
                $url = URL::temporarySignedRoute('kyc.admin.document', now()->addMinutes(10), [
                    'user' => $record->id,
                    'type' => $type,
                ]);
                $links[] = '<a href="'.$url.'" target="_blank" class="fi-link text-primary-600 underline">'.e($label).'</a>';
            }
        }

        return new HtmlString($links === [] ? 'لا توجد مستندات' : implode(' · ', $links));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')
                        ->label('الهاتف')
                        ->tel()
                        ->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('التحقق (KYC)')
                ->description('تُدار حالة التحقق من خلال إجراءات «بدء المراجعة / اعتماد / رفض».')
                ->schema([
                    Forms\Components\Placeholder::make('kyc_state_label')
                        ->label('حالة التحقق')
                        ->content(fn (?User $record): string => $record?->kyc_state?->label() ?? 'لم تُرفع المستندات بعد'),
                    Forms\Components\Placeholder::make('kyc_submitted_at')
                        ->label('تاريخ رفع المستندات')
                        ->content(fn (?User $record): string => $record?->kyc_submitted_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('kyc_reviewed_at')
                        ->label('تاريخ المراجعة')
                        ->content(fn (?User $record): string => $record?->kyc_reviewed_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('kyc_rejection_reason')
                        ->label('سبب الرفض')
                        ->content(fn (?User $record): string => $record?->kyc_rejection_reason ?? '—'),
                    Forms\Components\Placeholder::make('kyc_documents')
                        ->label('المستندات')
                        ->columnSpanFull()
                        ->content(fn (?User $record): HtmlString => self::documentLinks($record)),
                ])
                ->columns(2)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Section::make('الحساب البنكي')
                ->description('حساب المستثمر لاستلام الأرباح والمبالغ المسحوبة.')
                ->schema([
                    Forms\Components\Placeholder::make('bank_name')
                        ->label('البنك')
                        ->content(fn (?User $record): string => $record?->bank_name ?? '—'),
                    Forms\Components\Placeholder::make('bank_account_name')
                        ->label('اسم الحساب')
                        ->content(fn (?User $record): string => $record?->bank_account_name ?? '—'),
                    Forms\Components\Placeholder::make('bank_iban')
                        ->label('الآيبان (IBAN)')
                        ->content(fn (?User $record): string => $record?->bank_iban ?? '—'),
                ])
                ->columns(3)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Section::make('معلومات إضافية')
                ->schema([
                    Forms\Components\Placeholder::make('roles')
                        ->label('الأدوار')
                        ->content(fn (?User $record): string => $record
                            ? $record->roles->pluck('name')->map(fn ($r) => self::ROLE_LABELS[$r] ?? $r)->implode('، ')
                            : '—'),
                    Forms\Components\Placeholder::make('investments_count')
                        ->label('عدد المشاركات')
                        ->content(fn (?User $record): string => (string) ($record?->investments()->count() ?? 0)),
                    Forms\Components\Placeholder::make('total_invested')
                        ->label('إجمالي الاستثمارات')
                        ->content(fn (?User $record): string => money($record?->investments()->approved()->sum('amount') ?? 0)),
                ])
                ->columns(3)
                ->visibleOn(['edit', 'view']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('investments')
                ->withSum(
                    ['investments as total_invested' => fn (Builder $q) => $q->where('status', InvestmentStatus::Approved->value)],
                    'amount'
                ))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\ViewColumn::make('kyc_status')
                    ->label('التحقق')
                    ->view('filament.tables.columns.asas-enum-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => self::ROLE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('investments_count')
                    ->label('المشاركات')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total_invested')
                    ->label('إجمالي الاستثمار')
                    ->formatStateUsing(fn ($state): string => money($state ?? 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label('حالة التحقق')
                    ->options(self::kycOptions()),
                Tables\Filters\SelectFilter::make('role')
                    ->label('الدور')
                    ->options([
                        'investor' => 'مستثمر',
                        'member' => 'عضو',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $role) => $q->role($role))),
                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ التسجيل')
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
                Tables\Actions\Action::make('startKycReview')
                    ->label('بدء المراجعة')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->kyc_state === KycState::DocumentsUploaded
                        && auth()->user()->can('reviewKyc', $record))
                    ->action(fn (User $record) => app(KycService::class)->startReview($record)),
                Tables\Actions\Action::make('approveKyc')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => in_array($record->kyc_state, [KycState::DocumentsUploaded, KycState::UnderReview], true)
                        && auth()->user()->can('reviewKyc', $record))
                    ->action(fn (User $record) => app(KycService::class)->approve($record)),
                Tables\Actions\Action::make('rejectKyc')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => in_array($record->kyc_state, [KycState::DocumentsUploaded, KycState::UnderReview], true)
                        && auth()->user()->can('reviewKyc', $record))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (User $record, array $data) => app(KycService::class)->reject($record, $data['reason'])),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('لا يوجد مستثمرون بعد')
            ->emptyStateDescription('سيظهر هنا المستثمرون والأعضاء بعد التسجيل.');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvestmentsRelationManager::class,
            RelationManagers\ContractInterestsRelationManager::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    public static function getWidgets(): array
    {
        return [
            InvestorStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestors::route('/'),
            'view' => Pages\ViewInvestor::route('/{record}'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
        ];
    }
}
