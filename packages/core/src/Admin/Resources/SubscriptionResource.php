<?php

declare(strict_types=1);

namespace DayOne\Admin\Resources;

use DayOne\Admin\Resources\SubscriptionResource\Pages;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Subscription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/** @extends Resource<Subscription> */
final class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Portfolio';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('stripe_subscription_id')
                    ->maxLength(255),
                Select::make('status')
                    ->options(
                        collect(SubscriptionStatus::cases())
                            ->mapWithKeys(fn (SubscriptionStatus $status): array => [
                                $status->value => $status->name,
                            ])
                            ->all(),
                    )
                    ->required(),
                DateTimePicker::make('trial_ends_at'),
                KeyValue::make('metadata'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => SubscriptionStatus::Active->value,
                        'info' => SubscriptionStatus::Trialing->value,
                        'warning' => SubscriptionStatus::PastDue->value,
                        'danger' => SubscriptionStatus::Canceled->value,
                        'gray' => SubscriptionStatus::Expired->value,
                    ]),
                Tables\Columns\TextColumn::make('stripe_subscription_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    /** @return array<string, \Filament\Resources\Pages\PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
