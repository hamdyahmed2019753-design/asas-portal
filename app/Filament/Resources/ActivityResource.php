<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\Widgets\ActivityStats;
use App\Filament\Resources\ActivityResource\Widgets\RecentActivityTimeline;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\NewsUpdate;
use App\Models\Payout;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'سجل النشاط';

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'سجل النشاط';

    protected static ?int $navigationSort = 6;

    /**
     * @var array<class-string, string>
     */
    private const SUBJECTS = [
        Contract::class => 'عقد',
        Investment::class => 'مشاركة',
        Payout::class => 'توزيعة',
        NewsUpdate::class => 'خبر',
        User::class => 'مستخدم',
    ];

    /**
     * @var array<string, string>
     */
    private const EVENTS = [
        'created' => 'إنشاء',
        'updated' => 'تحديث',
        'deleted' => 'حذف',
    ];

    public static function subjectLabel(?string $class): string
    {
        return self::SUBJECTS[$class] ?? 'غير معروف';
    }

    public static function formatProperties(Activity $activity): string
    {
        return json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    // Read-only resource: no create / edit / delete / mutation.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['causer', 'subject']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('المعرّف')
                    ->sortable(),
                Tables\Columns\ViewColumn::make('event')
                    ->label('الحدث')
                    ->view('filament.tables.columns.asas-event-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('الكيان')
                    ->formatStateUsing(fn (?string $state): string => self::subjectLabel($state))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('رقم الكيان')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('المنفّذ')
                    ->placeholder('النظام'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('الحدث')
                    ->options(self::EVENTS),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('الكيان')
                    ->options(self::SUBJECTS),
                Tables\Filters\Filter::make('created_at')
                    ->label('التاريخ')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('التفاصيل')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalHeading('تفاصيل النشاط')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (Activity $record) => view('filament.activity.details', [
                        'activity' => $record,
                        'json' => self::formatProperties($record),
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('لا يوجد نشاط بعد')
            ->emptyStateDescription('يُسجَّل هنا كل نشاط على العقود والمشاركات والتوزيعات.');
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
            ActivityStats::class,
            RecentActivityTimeline::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
