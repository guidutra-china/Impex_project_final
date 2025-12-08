<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),

                TextColumn::make('name')
                    ->label(__('fields.name'))
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label(__('fields.email'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope')
                    ->color('gray'),

                TextColumn::make('phone')
                    ->label(__('fields.phone'))
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($record): string => $record->is_admin ? 'Administrator' : 'Regular User'),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn ($record): string => $record->email_verified_at 
                        ? 'Verified on ' . $record->email_verified_at->format('M d, Y')
                        : 'Not verified'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('Never')
                    ->tooltip(fn ($record): ?string => $record->last_login_at 
                        ? $record->last_login_at->diffForHumans()
                        : null)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('fields.updated_at'))
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Account Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->multiple()
                    ->native(false),

                SelectFilter::make('roles')
                    ->label('User Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->native(false),

                Filter::make('is_admin')
                    ->label('Administrators Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_admin', true))
                    ->toggle(),

                Filter::make('verified')
                    ->label('Email Verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->toggle(),

                Filter::make('unverified')
                    ->label('Email Not Verified')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->toggle(),

                Filter::make('has_phone')
                    ->label('Has Phone Number')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('phone'))
                    ->toggle(),

                Filter::make('recent_login')
                    ->label('Logged in Last 30 Days')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('last_login_at', '>=', now()->subDays(30))
                    )
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
