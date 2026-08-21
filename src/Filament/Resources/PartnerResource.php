<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Partneři';

    protected static ?string $modelLabel = 'Partner';

    protected static ?string $pluralModelLabel = 'Partneři';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
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

                Forms\Components\TextInput::make('url')
                    ->label('Odkaz na e-shop')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Nepovinné. Použije se u nadpisu sekce s produkty partnera.'),

                Forms\Components\Repeater::make('products')
                    ->label('Produkty')
                    ->schema([
                        // Bez cropu a resize schválně: produktové fotky přicházejí
                        // od partnera hotové a pevný poměr by je ořízl.
                        // imageEditor() tu být musí — bez něj se ořezávátko
                        // v UI vůbec nezobrazí, i kdyby byl poměr nastavený.
                        Forms\Components\FileUpload::make('image')
                            ->label('Obrázek produktu')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('partners')
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Název produktu')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    // Na webu jsou právě čtyři pozice, víc by se nemělo kam vejít.
                    ->maxItems(4)
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => filled($state['title'] ?? null) ? $state['title'] : null)
                    ->addActionLabel('Přidat produkt')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Zobrazit na webu')
                    ->helperText('Na webu smí být jen jeden partner — zapnutím se příznak zruší u dosud vybraného.'),

                Forms\Components\TextInput::make('order')
                    ->label('Pořadí')
                    ->numeric()
                    ->helperText('Nepovinné. Uplatní se, až bude partnerů víc.'),

                Forms\Components\Select::make('status')
                    ->label('Stav')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('draft')
                    ->required(),

                // Stejně jako u galerií a článků: nový záznam dostane aktuální
                // datum, ať partner nezůstane s prázdným published_at a filtr
                // na veřejném webu ho neskryje, aniž by o tom redaktor věděl.
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Datum publikace')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_count')
                    ->label('Produktů'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Na webu')
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
            // Dvě kritéria, takže closure místo názvu sloupce: ruční pořadí
            // rozhoduje první, datum publikace až uvnitř stejné hodnoty order
            // (výchozí 0 má zatím každý partner).
            ->defaultSort(fn (Builder $query) => $query->orderBy('order')->orderByDesc('published_at'))
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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
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
