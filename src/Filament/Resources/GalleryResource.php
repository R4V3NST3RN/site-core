<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryResource\Pages;
use App\Models\Gallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Galerie';

    protected static ?string $modelLabel = 'Galerie';

    protected static ?string $pluralModelLabel = 'Galerie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Název')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    ),

                Forms\Components\TextInput::make('slug')
                    ->label('URL slug')
                    ->required()
                    ->maxLength(255)
                    ->readOnly()
                    ->dehydrated()
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('description')
                    ->label('Popis')
                    ->rows(3)
                    ->columnSpanFull(),

                // disk('public') je tu EXPLICITNĚ: výchozí disk je 'local'
                // (config/filesystems.php) a spoléhat na FILESYSTEM_DISK
                // v .env je křehké — na jiném webu může chybět.
                Forms\Components\FileUpload::make('cover_image')
                    ->label('Náhledový obrázek')
                    ->image()
                    ->disk('public')
                    ->directory('galleries')
                    ->imageEditor()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('4:3')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('900')
                    ->helperText('Náhled do výpisu galerií. Ořízne se na poměr 4:3 — u fotek na výšku si vyberte výřez.')
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('photos')
                    ->label('Fotky')
                    ->schema([
                        // Bez cropu a resize schválně: fotky se zobrazují tak,
                        // jak je klient nafotil, ořez patří jen náhledu výše.
                        Forms\Components\FileUpload::make('image')
                            ->label('Fotka')
                            ->image()
                            ->disk('public')
                            ->directory('galleries')
                            ->required(),

                        Forms\Components\TextInput::make('caption')
                            ->label('Popisek')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => filled($state['caption'] ?? null) ? $state['caption'] : null)
                    ->addActionLabel('Přidat fotku')
                    ->columnSpanFull(),

                Forms\Components\Select::make('courseTypes')
                    ->label('Typy kurzů')
                    ->relationship('courseTypes', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Nepovinné. Galerie se zobrazí i na stránce vybraných typů kurzů.'),

                Forms\Components\Toggle::make('show_on_homepage')
                    ->label('Zobrazit na hlavní straně')
                    ->helperText('Na hlavní straně smí být jen jedna galerie — zapnutím se příznak zruší u dosud vybrané.'),

                Forms\Components\Select::make('status')
                    ->label('Stav')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('draft')
                    ->required(),

                // Stejně jako u článků a kurzů: nový záznam dostane aktuální
                // datum, ať galerie nezůstane s prázdným published_at a filtr
                // na veřejném webu ji neskryje, aniž by o tom redaktor věděl.
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Datum publikace')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Náhled')
                    ->disk('public')
                    ->circular(false)
                    ->height(40),

                Tables\Columns\TextColumn::make('title')
                    ->label('Název')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('photo_count')
                    ->label('Fotek'),

                Tables\Columns\IconColumn::make('show_on_homepage')
                    ->label('Na hlavní straně')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stav')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Datum publikace')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
