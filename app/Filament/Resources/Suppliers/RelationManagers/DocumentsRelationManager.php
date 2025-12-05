<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use BackedEnum;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('mime_type', 'not like', 'image/%'))
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('document_number')
                    ->label('Doc #')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title)
                    ->weight('bold'),

                BadgeColumn::make('document_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'contract',
                        'success' => 'certificate_of_origin',
                        'warning' => 'quality_certificate',
                        'info' => 'other',
                    ]),

                TextColumn::make('description')
                    ->limit(40)
                    ->placeholder('No description')
                    ->wrap(),

                TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date()
                    ->sortable()
                    ->placeholder('N/A'),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'valid',
                        'warning' => 'draft',
                        'danger' => 'expired',
                        'secondary' => 'cancelled',
                    ]),

                TextColumn::make('file_size_formatted')
                    ->label('Size')
                    ->getStateUsing(fn ($record) => number_format($record->file_size / 1024, 2) . ' KB'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Document')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['document_number'] = 'SUP-DOC-' . strtoupper(Str::random(8));
                        $data['related_type'] = 'App\\Models\\Supplier';
                        $data['related_id'] = $livewire->getOwnerRecord()->id;
                        
                        if (isset($data['file_path']) && !isset($data['file_name'])) {
                            $data['file_name'] = basename($data['file_path']);
                            $data['safe_filename'] = Str::slug(pathinfo($data['file_name'], PATHINFO_FILENAME)) . '_' . Str::random(8);
                        }
                        
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),

                EditAction::make(),

                DeleteAction::make()
                    ->after(function ($record) {
                        if ($record->file_path) {
                            Storage::disk('public')->delete($record->file_path);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                if ($record->file_path) {
                                    Storage::disk('public')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('public')
                    ->directory('suppliers/documents')
                    ->required()
                    ->maxSize(10240) // 10MB
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->preserveFilenames()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                            $filename = $state->getClientOriginalName();
                            $set('file_name', $filename);
                            $set('title', pathinfo($filename, PATHINFO_FILENAME));
                            $set('file_size', $state->getSize());
                            $set('mime_type', $state->getMimeType());
                            $set('safe_filename', Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '_' . Str::random(8));
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Document Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('document_type')
                    ->label('Document Type')
                    ->required()
                    ->options([
                        'contract' => 'Contract',
                        'certificate_of_origin' => 'Certificate of Origin',
                        'quality_certificate' => 'Quality Certificate',
                        'insurance_certificate' => 'Insurance Certificate',
                        'other' => 'Other',
                    ])
                    ->default('other'),

                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'valid' => 'Valid',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('valid'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('issue_date')
                    ->label('Issue Date')
                    ->required()
                    ->default(now())
                    ->native(false),

                DatePicker::make('expiry_date')
                    ->label('Expiry Date')
                    ->native(false)
                    ->after('issue_date'),

                Hidden::make('file_name'),
                Hidden::make('file_size'),
                Hidden::make('mime_type'),
                Hidden::make('safe_filename'),
            ])->columns(2);
    }
}
