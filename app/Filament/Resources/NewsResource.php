<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource\Widgets\NewsStats;
use App\Models\NewsUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NewsResource extends Resource
{
    protected static ?string $model = NewsUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'الأخبار';

    protected static ?string $modelLabel = 'خبر';

    protected static ?string $pluralModelLabel = 'الأخبار';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('المحتوى')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('العنوان')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('body')
                        ->label('المحتوى')
                        ->required()
                        ->rows(6),
                ]),

            Forms\Components\Section::make('النشر')
                ->schema([
                    Forms\Components\Toggle::make('is_published')
                        ->label('منشور')
                        ->default(false),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('تاريخ النشر')
                        ->helperText('يُملأ تلقائيًا بوقت الحفظ عند النشر إن تُرك فارغًا.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                Tables\Columns\ViewColumn::make('is_published')
                    ->label('الحالة')
                    ->view('filament.tables.columns.asas-status-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('النشر')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('مسودة'),
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
                Tables\Actions\Action::make('publish')
                    ->label('نشر')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (NewsUpdate $record): bool => ! $record->is_published)
                    ->requiresConfirmation()
                    ->action(function (NewsUpdate $record): void {
                        $record->update([
                            'is_published' => true,
                            'published_at' => $record->published_at ?? now(),
                        ]);
                        Notification::make()->title('تم نشر الخبر')->success()->send();
                    }),
                Tables\Actions\Action::make('unpublish')
                    ->label('إلغاء النشر')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->authorize('update')
                    ->visible(fn (NewsUpdate $record): bool => $record->is_published)
                    ->requiresConfirmation()
                    ->action(function (NewsUpdate $record): void {
                        $record->update(['is_published' => false]);
                        Notification::make()->title('تم إلغاء نشر الخبر')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each(fn (NewsUpdate $record) => $record->update([
                            'is_published' => true,
                            'published_at' => $record->published_at ?? now(),
                        ]))),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('إلغاء النشر')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each(fn (NewsUpdate $record) => $record->update([
                            'is_published' => false,
                        ]))),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateHeading('لا توجد أخبار بعد')
            ->emptyStateDescription('أضف أول خبر ليظهر في «الجديد في أساس» داخل بوابة المستثمر.');
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
            NewsStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'view' => Pages\ViewNews::route('/{record}'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
