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

                // Vstupní pole, ne sloupec: dehydrated(false) drží 'bulk_photos'
                // mimo model, jediným zdrojem pravdy o fotkách zůstává Repeater
                // pod ním. Disk i adresář musí sedět s Repeaterem, jinak by část
                // fotek jedné galerie skončila mimo veřejné úložiště.
                Forms\Components\FileUpload::make('bulk_photos')
                    ->label('Nahrát fotky hromadně')
                    ->multiple()
                    ->image()
                    ->disk('public')
                    ->directory('galleries')
                    ->dehydrated(false)
                    // Bez názvu galerie by z popisku zbylo holé "5/7", proto se
                    // pole otevře až s ním — 'title' je ->live(), překreslí se samo.
                    ->disabled(fn (Forms\Get $get): bool => blank($get('title')))
                    ->helperText(fn (Forms\Get $get): string => blank($get('title'))
                        ? 'Nejdřív vyplňte název galerie — popisky nahraných fotek se skládají z něj a z pořadí.'
                        : 'Vyberte víc fotek najednou. Přidají se dolů mezi Fotky s předvyplněným popiskem, který tam můžete upravit.')
                    ->afterStateUpdated(function (Forms\Components\FileUpload $component, Forms\Get $get, Forms\Set $set): void {
                        // Livewire drží čerstvě nahrané soubory jako dočasné;
                        // tohle je přesune na disk a nechá ve stavu cesty, se
                        // kterými už umí pracovat Repeater.
                        $component->saveUploadedFiles();

                        $uploaded = array_values(array_filter((array) $component->getState()));

                        if ($uploaded === []) {
                            return;
                        }

                        $photos = (array) $get('photos');

                        // Počítá se jen řádek, který má fotku: Repeater začíná
                        // s jedním prázdným (defaultItems), a ten by jinak první
                        // dávku do nové galerie posunul na "2/4" místo "1/3".
                        $offset = count(array_filter($photos, fn ($photo): bool => filled($photo['image'] ?? null)));
                        $total = $offset + count($uploaded);
                        $title = trim((string) $get('title'));

                        foreach ($uploaded as $index => $path) {
                            // Klíč musí být uuid, jinak Repeater řádek nepozná.
                            // Připisujeme na konec, takže pořadí ani obsah
                            // stávajících řádků zůstává tak, jak si ho redaktor srovnal.
                            $photos[(string) Str::uuid()] = [
                                'image' => $path,
                                'caption' => trim($title.' '.($offset + $index + 1).'/'.$total),
                            ];
                        }

                        // Jmenovatel je počet fotek PO dávce, takže dřívější řádky
                        // zůstanou s nižším ("4/4" vedle "5/7"). Nepřečíslováváme je
                        // schválně: popisky si redaktor ručně upravuje a hromadný
                        // přepis by mu tu práci pokaždé zahodil.
                        $set('photos', $photos);

                        // Bez vyprázdnění by se fotky z téhle dávky připsaly znovu
                        // při každém dalším nahrání.
                        $set('bulk_photos', []);
                    })
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
                    // Bez defaultItems(0) začíná nová galerie s jedním prázdným
                    // řádkem, jehož 'image' je required — po hromadném nahrání
                    // ho redaktor musí ručně smazat, jinak formulář neuloží.
                    ->defaultItems(0)
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
