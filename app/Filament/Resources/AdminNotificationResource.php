<?php

namespace App\Filament\Resources;

use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Filament\Resources\AdminNotificationResource\Pages;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Admin Notification Center — management surface over the per-admin database
 * notifications created by AdminNotification. Each admin only ever sees their
 * own rows (notifiable_id = auth id) filtered to the AdminNotification type,
 * so investor-facing notifications never leak here.
 */
class AdminNotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'مركز الإشعارات';

    protected static ?string $modelLabel = 'إشعار';

    protected static ?string $pluralModelLabel = 'الإشعارات';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    /**
     * Scope to the signed-in admin's own AdminNotification rows.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', auth()->id())
            ->where('type', AdminNotification::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        $categoryOptions = collect(AdminNotificationCategory::cases())
            ->mapWithKeys(fn (AdminNotificationCategory $c) => [$c->value => $c->label()])
            ->all();

        $priorityOptions = collect(AdminNotificationPriority::cases())
            ->mapWithKeys(fn (AdminNotificationPriority $p) => [$p->value => $p->label()])
            ->all();

        return $table
            ->columns([
                Tables\Columns\IconColumn::make('read_state')
                    ->label('')
                    ->icon(fn (DatabaseNotification $record): string => $record->read_at === null
                        ? 'heroicon-o-envelope'
                        : 'heroicon-o-envelope-open')
                    ->color(fn (DatabaseNotification $record): string => $record->read_at === null ? 'primary' : 'gray'),

                Tables\Columns\ViewColumn::make('actor')
                    ->label('المُطلِق / الوقت')
                    ->view('filament.tables.columns.admin-notification-actor'),

                Tables\Columns\TextColumn::make('data.title')
                    ->label('العنوان')
                    ->description(fn (DatabaseNotification $record): ?string => $record->data['body'] ?? null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->orWhere('data->title', 'like', "%{$search}%")),

                Tables\Columns\TextColumn::make('data.body')
                    ->label('النص')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->orWhere('data->body', 'like', "%{$search}%"))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data.actor_name')
                    ->label('المستثمر')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->orWhere('data->actor_name', 'like', "%{$search}%"))
                    ->placeholder('النظام')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data.category')
                    ->label('الفئة')
                    ->badge()
                    ->icon(fn ($state): ?string => AdminNotificationCategory::tryFrom((string) $state)?->icon())
                    ->color(fn ($state): string => AdminNotificationCategory::tryFrom((string) $state)?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state): string => AdminNotificationCategory::tryFrom((string) $state)?->label() ?? 'النظام'),

                Tables\Columns\TextColumn::make('data.priority')
                    ->label('الأولوية')
                    ->badge()
                    ->color(fn ($state): string => AdminNotificationPriority::tryFrom((string) $state)?->color() ?? 'gray')
                    ->icon(fn (DatabaseNotification $record): ?string => ($record->data['priority'] ?? null) === AdminNotificationPriority::Critical->value
                        ? 'heroicon-m-exclamation-triangle'
                        : null)
                    ->formatStateUsing(fn ($state): string => AdminNotificationPriority::tryFrom((string) $state)?->label() ?? 'متوسطة'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('read')
                    ->label('الحالة')
                    ->options(['unread' => 'غير مقروء', 'read' => 'مقروء'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'unread' => $query->whereNull('read_at'),
                        'read' => $query->whereNotNull('read_at'),
                        default => $query,
                    }),

                Tables\Filters\SelectFilter::make('category')
                    ->label('الفئة')
                    ->options($categoryOptions)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $value) => $q->where('data->category', $value))),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options($priorityOptions)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $value) => $q->where('data->priority', $value))),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('فتح السجل')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DatabaseNotification $record): ?string => $record->data['url'] ?? null)
                    ->openUrlInNewTab()
                    ->visible(fn (DatabaseNotification $record): bool => isset($record->data['url']))
                    ->after(fn (DatabaseNotification $record) => $record->read_at === null ? $record->markAsRead() : null),
                Tables\Actions\Action::make('markRead')
                    ->label('تعليم كمقروء')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DatabaseNotification $record): bool => $record->read_at === null)
                    ->action(fn (DatabaseNotification $record) => $record->markAsRead()),
            ])
            ->bulkActions([
                BulkAction::make('markRead')
                    ->label('تعليم المحدد كمقروء')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each(fn (DatabaseNotification $r) => $r->read_at === null ? $r->markAsRead() : null)),
                BulkAction::make('delete')
                    ->label('حذف المحدد')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => $records->each->delete()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('markAllRead')
                    ->label('تعليم الكل كمقروء')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn () => static::getEloquentQuery()->whereNull('read_at')->update(['read_at' => now()])),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->emptyStateHeading('لا توجد إشعارات')
            ->emptyStateDescription('ستظهر هنا إشعارات النظام فور وقوعها.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminNotifications::route('/'),
        ];
    }
}
