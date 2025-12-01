<?php

namespace App\Filament\Resources\Shield;

use App\Filament\Resources\Shield\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 1;

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('can_see_all')
                                    ->label('Can See All Clients')
                                    ->helperText('Users with this role can see all clients and related data regardless of ownership')
                                    ->default(false)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('select_all')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(__('filament-shield::filament-shield.field.select_all.message'))
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $permissions = Utils::getPermissions();
                                        $set('permissions', $state ? $permissions->pluck('name')->toArray() : []);
                                    })
                                    ->dehydrated(fn ($state): bool => $state),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),

                        Forms\Components\Tabs::make('Permissions')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.section'))
                                    ->visible(fn (): bool => (bool) Utils::isResourceEntityEnabled())
                                    ->reactive()
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->options(fn (): array => Utils::getResourcePermissionOptions())
                                            ->searchable()
                                            ->afterStateHydrated(function ($component, $state) {
                                                $component->state(
                                                    collect($state)->filter(fn ($permission) => Str::startsWith($permission, Utils::getResourcePermissionPrefixes()))
                                                        ->values()
                                                        ->toArray()
                                                );
                                            })
                                            ->dehydrated(fn ($state) => collect($state)->isNotEmpty())
                                            ->bulkToggleable()
                                            ->gridDirection('row')
                                            ->columns([
                                                'sm' => 2,
                                                'lg' => 3,
                                            ]),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label(__('filament-shield::filament-shield.column.guard_name'))
                    ->toggleable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('can_see_all')
                    ->label('See All')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_shield::role');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view_shield::role');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_shield::role');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_shield::role');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_shield::role');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_any_shield::role');
    }
}
