<?php

namespace App\Filament\Blocks;

use App\Models\Course;
use App\Models\Trainer;
use Filament\Forms;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Database\Eloquent\Model;

/**
 * Sdílená definice bloků redakčního obsahu pro Filament Builder.
 *
 * Jeden zdroj pravdy pro ArticleResource i CourseResource — bloky se
 * nesmí duplikovat v resources, jinak se schémata rozejdou a renderer
 * (per-web, resources/views/public/blocks/) přestane sedět na data.
 *
 * Názvy bloků a jejich polí jsou KONTRAKT mezi jádrem a per-web views,
 * viz core/blocks-contract.txt. Přejmenování bloku nebo pole je breaking
 * change pro každý web, který jádro konzumuje, a pro AI agenty, kteří
 * podle kontraktu obsah generují.
 *
 * Schéma je záměrně PLOCHÉ — žádné vnořené Buildery ani Repeatery uvnitř
 * bloků. Blok je vždy jen sada skalárních polí, aby byl předvídatelně
 * generovatelný (role redaktor, včetně AI agentů) a serializovatelný.
 *
 * VÝJIMKA: icon_list používá Filament Repeater, protože počet položek
 * seznamu je proměnný a pevný strop polí by byl v adminu nepřirozený.
 * Je to vědomé rozhodnutí, ne nedopatření — viz poznámka u icon_list
 * v core/blocks-contract.txt pro dopad na AI agenty generující obsah.
 *
 * DATOVÉ BLOKY (course_info, trainer) nenesou vlastní obsah, jen ID
 * odkazovaného modelu — obsah se čte z DB až při renderu. Díky tomu se
 * termíny, ceny a kapacita aktualizují samy (u kurzů i syncem), aniž by
 * je redaktor přepisoval v článku.
 */
class ContentBlocks
{
    /**
     * @param  string|null  $ownerModel  Třída modelu, jehož formulář bloky
     *                                   používá. Předává se EXPLICITNĚ
     *                                   z resource (CourseResource posílá
     *                                   Course::class), ne odvozením z
     *                                   $livewire — hádání volajícího přes
     *                                   instanceof je křehké a rozbije se
     *                                   při každé změně stránek resource.
     * @return array<int, Block>
     */
    public static function all(?string $ownerModel = null): array
    {
        return [
            self::heading(),
            self::richText(),
            self::textImage(),
            self::imageText(),
            self::divider(),
            self::buttonSingle(),
            self::buttonDouble(),
            self::iconList(),
            self::courseInfo($ownerModel),
            self::trainer(),
        ];
    }

    protected static function heading(): Block
    {
        return Block::make('heading')
            ->label('Nadpis')
            ->icon('heroicon-o-bars-3-bottom-left')
            ->schema([
                Forms\Components\TextInput::make('text')
                    ->label('Text nadpisu')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    protected static function richText(): Block
    {
        return Block::make('rich_text')
            ->label('Text')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->label('Text')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    protected static function textImage(): Block
    {
        return Block::make('text_image')
            ->label('Text + obrázek')
            ->icon('heroicon-o-view-columns')
            ->schema(self::textWithImageSchema());
    }

    protected static function imageText(): Block
    {
        return Block::make('image_text')
            ->label('Obrázek + text')
            ->icon('heroicon-o-view-columns')
            ->schema(self::textWithImageSchema());
    }

    protected static function divider(): Block
    {
        return Block::make('divider')
            ->label('Oddělovač')
            ->icon('heroicon-o-minus');
    }

    protected static function buttonSingle(): Block
    {
        return Block::make('button_single')
            ->label('Tlačítko')
            ->icon('heroicon-o-cursor-arrow-rays')
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->label('Text tlačítka')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('url')
                    ->label('Odkaz')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Absolutní URL, relativní cesta (/kontakt) nebo mailto: — bez validace formátu.'),
            ]);
    }

    protected static function buttonDouble(): Block
    {
        return Block::make('button_double')
            ->label('Dvě tlačítka')
            ->icon('heroicon-o-cursor-arrow-rays')
            ->schema([
                Forms\Components\TextInput::make('label_1')
                    ->label('Text prvního tlačítka')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('url_1')
                    ->label('Odkaz prvního tlačítka')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Absolutní URL, relativní cesta (/kontakt) nebo mailto: — bez validace formátu.'),

                Forms\Components\TextInput::make('label_2')
                    ->label('Text druhého tlačítka')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('url_2')
                    ->label('Odkaz druhého tlačítka')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Absolutní URL, relativní cesta (/kontakt) nebo mailto: — bez validace formátu.'),
            ]);
    }

    protected static function iconList(): Block
    {
        return Block::make('icon_list')
            ->label('Seznam s ikonami')
            ->icon('heroicon-o-list-bullet')
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->label('Položky')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('Ikona')
                            ->helperText('Volný text — emoji nebo krátký text, žádný výběr z předdefinované sady.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('text')
                            ->label('Text')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->itemLabel(fn (array $state): ?string => trim(($state['icon'] ?? '').' '.($state['text'] ?? '')) ?: null)
                    ->defaultItems(3)
                    ->addActionLabel('Přidat položku')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Info o kurzu — čte termín, cenu a kapacitu přímo z Course.
     *
     * Pole course_id se chová podle toho, čí formulář blok hostí:
     *
     * - článek (výchozí): Select s aktivními kurzy. Popisky jdou přímo
     *   z uloženého sloupce courses.auto_title, ne z generateAutoTitle()
     *   — sloupec v DB existuje a je plnohodnotný (ověřeno: shoduje se
     *   s výstupem metody), takže pluck() zvládne jeden SQL dotaz bez
     *   hydratace modelů a bez N+1 na courseType/trainer, které by
     *   generateAutoTitle() za běhu potřeboval.
     *
     * - kurz (ownerModel === Course::class): Hidden předvyplněné ID
     *   editovaného kurzu. Kromě default() se hodnota přepisuje i při
     *   každé hydrataci formuláře — v tomhle kontextu blok VŽDY znamená
     *   "tenhle kurz", takže je správně ji držet v synchronizaci, ne ji
     *   nechat zamrznout na tom, co se uložilo poprvé.
     *
     * ZAKLÁDÁNÍ NOVÉHO KURZU: na CreateCourse ještě record neexistuje,
     * takže se uloží course_id = null a renderer blok přeskočí. Blok se
     * z pickeru schovat NEDÁ — Builder::getBlockPickerBlocks() filtruje
     * jen podle maxItems, visible() na Block ignoruje (ověřeno ve
     * vendoru). Self-healing řeší afterStateHydrated(): jakmile kurz
     * jednou otevřeš k editaci, ID se doplní a uloží.
     */
    protected static function courseInfo(?string $ownerModel = null): Block
    {
        return Block::make('course_info')
            ->label('Info o kurzu')
            ->icon('heroicon-o-information-circle')
            ->schema([
                $ownerModel === Course::class
                    ? Forms\Components\Hidden::make('course_id')
                        ->default(fn (?Model $record): mixed => $record?->getKey())
                        ->afterStateHydrated(function (Forms\Set $set, ?Model $record): void {
                            if (filled($record?->getKey())) {
                                $set('course_id', $record->getKey());
                            }
                        })
                    : Forms\Components\Select::make('course_id')
                        ->label('Kurz')
                        ->options(fn (): array => Course::query()
                            ->where('status', 'active')
                            ->orderBy('auto_title')
                            ->pluck('auto_title', 'id')
                            ->all())
                        ->required()
                        ->searchable()
                        ->helperText('Termín, cena a kapacita se načtou z kurzu při zobrazení stránky.'),
            ]);
    }

    /**
     * Trenér — čte foto, jméno, specializaci a bio přímo z Trainer.
     *
     * Popisky voleb se plní z accessoru full_name, který není sloupec,
     * takže pluck() musí proběhnout až nad kolekcí (get()->pluck()), ne
     * v SQL. Seznam trenérů je krátký, takže je to levnější než
     * relationship() + getOptionLabelFromRecordUsing() — a hlavně tu
     * žádný Eloquent vztah, o který by se dalo opřít, neexistuje.
     */
    protected static function trainer(): Block
    {
        return Block::make('trainer')
            ->label('Trenér')
            ->icon('heroicon-o-user-circle')
            ->schema([
                Forms\Components\Select::make('trainer_id')
                    ->label('Trenér')
                    ->options(fn (): array => Trainer::query()
                        ->where('is_active', true)
                        ->orderBy('last_name')
                        ->get()
                        ->pluck('full_name', 'id')
                        ->all())
                    ->required()
                    ->searchable()
                    ->helperText('Foto, jméno, specializace a bio se načtou z profilu trenéra.'),
            ]);
    }

    /**
     * text_image a image_text mají ZÁMĚRNĚ identické schéma — liší se
     * jen pořadím sloupců v rendereru, ne daty. Díky tomu jde blok
     * v šabloně přepnout z jedné varianty na druhou bez migrace obsahu.
     *
     * Obrázek není povinný: agent generující obsah fotku nenahraje a
     * renderer v takovém případě vysází jen text přes plnou šířku.
     *
     * @return array<int, Forms\Components\Component>
     */
    protected static function textWithImageSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('content')
                ->label('Text')
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('image')
                ->label('Obrázek')
                ->image()
                ->disk('public')
                ->directory('blocks')
                ->preserveFilenames()
                ->helperText('Nepovinné. Bez obrázku se text vysází přes plnou šířku.')
                ->columnSpanFull(),
        ];
    }
}
