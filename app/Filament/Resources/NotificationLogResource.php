<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationLogResource\Pages;
use App\Models\DeliveryAlertLog;
use App\Models\PoSupplier;
use App\Models\SubconMaterialOut;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogResource extends Resource
{
    protected static ?string $model = DeliveryAlertLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Notification Logs';

    protected static ?string $modelLabel = 'Notification Log';

    protected static ?string $pluralModelLabel = 'Notification Logs';

    protected static ?string $slug = 'notification-logs';

    protected static ?int $navigationSort = 99;

    protected static bool $shouldRegisterNavigation = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->state(fn ($record): string => match (true) {
                        $record->alertable_type !== null => 'Delivery Alert',
                        default => 'General Notification',
                    }),

                Tables\Columns\TextColumn::make('alert_level')
                    ->label('Alert Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'warning' => 'warning',
                        'overdue' => 'danger',
                        'escalation' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('alertable_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PoSupplier::class => 'PO Supplier',
                        SubconMaterialOut::class => 'Subcon Material Out',
                        default => class_basename($state),
                    }),

                Tables\Columns\TextColumn::make('alertable')
                    ->label('Document Ref')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHasMorph('alertable', [PoSupplier::class, SubconMaterialOut::class], function (Builder $morphQuery, string $type) use ($search) {
                                if ($type === PoSupplier::class) {
                                    $morphQuery->where('po_number', 'like', "%{$search}%");
                                } elseif ($type === SubconMaterialOut::class) {
                                    $morphQuery->where('delivery_number', 'like', "%{$search}%");
                                }
                            })->orWhere('alertable_id', 'like', "%{$search}%");
                        });
                    })
                    ->formatStateUsing(function ($record): string {
                        $alertable = $record->alertable;
                        if (! $alertable) {
                            return '#'.$record->alertable_id;
                        }
                        if ($alertable instanceof PoSupplier) {
                            return $alertable->po_number ?? '#'.$alertable->id;
                        }
                        if ($alertable instanceof SubconMaterialOut) {
                            return $alertable->delivery_number ?? '#'.$alertable->id;
                        }

                        return '#'.$alertable->id;
                    })
                    ->url(function ($record): ?string {
                        $alertable = $record->alertable;
                        if ($alertable instanceof PoSupplier) {
                            return PoSupplierResource::getUrl('edit', ['record' => $alertable->id]);
                        }
                        if ($alertable instanceof SubconMaterialOut) {
                            return SubconMaterialOutResource::getUrl('edit', ['record' => $alertable->id]);
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : ($state > 0 ? "+{$state} days" : "{$state} days"))
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'delivery_alert' => 'Delivery Alert',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'delivery_alert') {
                            $query->whereNotNull('alertable_type');
                        }
                    }),

                Tables\Filters\SelectFilter::make('alert_level')
                    ->options([
                        'warning' => 'Warning (H-3)',
                        'overdue' => 'Overdue',
                        'escalation' => 'Escalation (>3d)',
                    ]),

                Tables\Filters\SelectFilter::make('alertable_type')
                    ->label('Document Type')
                    ->options([
                        PoSupplier::class => 'PO Supplier',
                        SubconMaterialOut::class => 'Subcon Material Out',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationLogs::route('/'),
        ];
    }
}
