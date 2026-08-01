<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Základní informace')
                    ->schema([
                        Forms\Components\Select::make('course_type_id')
                            ->label('Typ kurzu')
                            ->relationship('courseType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),

                        Forms\Components\Select::make('trainer_id')
                            ->label('Trenér')
                            ->relationship('trainer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Koncept',
                                'active' => 'Aktivní',
                                'completed' => 'Ukončený',
                                'cancelled' => 'Zrušený',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Zvýrazněný kurz'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Termín kurzu')
                    ->schema([
                        Forms\Components\TextInput::make('day_of_week')
                            ->label('Den v týdnu')
                            ->placeholder('např. úterý')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),

                        Forms\Components\Select::make('time_of_day')
                            ->label('Denní doba')
                            ->options([
                                'ráno' => 'Ráno',
                                'dopoledne' => 'Dopoledne',
                                'odpoledne' => 'Odpoledne',
                                'večer' => 'Večer',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Začátek')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Konec')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TextInput::make('months')
                            ->label('Měsíce')
                            ->placeholder('např. listopad - únor')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),

                        Forms\Components\TextInput::make('year')
                            ->label('Rok ukončení')
                            ->numeric()
                            ->required()
                            ->default(date('Y'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                self::updateSlug($set, $get);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Popis a ceny')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Popis kurzu')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('total_lessons')
                            ->label('Celkový počet lekcí')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('remaining_lessons')
                            ->label('Zbývající lekce')
                            ->numeric(),

                        Forms\Components\TextInput::make('price_per_course')
                            ->label('Cena za celý kurz')
                            ->numeric()
                            ->prefix('Kč')
                            ->step(0.01),

                        Forms\Components\TextInput::make('price_per_lesson')
                            ->label('Cena za jednu lekci')
                            ->numeric()
                            ->prefix('Kč')
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kapacita')
                    ->schema([
                        Forms\Components\TextInput::make('total_spots')
                            ->label('Celková kapacita')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('available_spots')
                            ->label('Volná místa')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SEO a URL')
                    ->schema([
                        Forms\Components\Placeholder::make('auto_title_preview')
                            ->label('Náhled auto-generovaného názvu')
                            ->content(function ($get) {
                                // Simulace generování názvu
                                $parts = [];

                                if ($courseTypeId = $get('course_type_id')) {
                                    $courseType = \App\Models\CourseType::find($courseTypeId);
                                    if ($courseType) {
                                        $parts[] = $courseType->full_name;
                                    }
                                }

                                if ($trainerId = $get('trainer_id')) {
                                    $trainer = \App\Models\Trainer::find($trainerId);
                                    if ($trainer) {
                                        $parts[] = "s {$trainer->full_name}";
                                    }
                                }

                                if ($day = $get('day_of_week')) {
                                    if ($time = $get('time_of_day')) {
                                        $parts[] = "{$day} {$time}";
                                    }
                                }

                                if ($months = $get('months')) {
                                    if ($year = $get('year')) {
                                        $parts[] = "{$months} {$year}";
                                    }
                                }

                                return implode(', ', $parts) ?: 'Vyplňte všechna pole pro náhled';
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL slug')
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->whereNull('deleted_at');
                            })
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Automaticky generováno z ostatních polí'),
                        Forms\Components\Hidden::make('auto_title'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Externí synchronizace')
                    ->schema([
                        Forms\Components\TextInput::make('external_sync_id')
                            ->label('Externí ID')
                            ->helperText('ID kurzu v externím rezervačním systému. Prázdné = kurz se nesynchronizuje.')
                            ->maxLength(255),

                        Forms\Components\Placeholder::make('last_synced_at')
                            ->label('Poslední synchronizace')
                            ->content(fn ($record) => $record?->last_synced_at?->format('d.m.Y H:i') ?? 'Nikdy'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('courseType.name')
                    ->label('Typ kurzu')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trainer.full_name')
                    ->label('Trenér')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Den')
                    ->searchable(),

                Tables\Columns\TextColumn::make('time_of_day')
                    ->label('Denní doba'),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Čas')
                    ->time('H:i'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stav')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('available_spots')
                    ->label('Volná místa')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Zvýrazněný')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Koncept',
                        'active' => 'Aktivní',
                        'completed' => 'Ukončený',
                        'cancelled' => 'Zrušený',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label('Synchronizovat')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function ($record) {
                        $result = app(\App\Contracts\CourseSyncProvider::class)->sync($record);

                        if ($result->succeeded) {
                            \Filament\Notifications\Notification::make()
                                ->title('Synchronizace úspěšná!')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Synchronizace selhala!')
                                ->body($result->message ?? 'Zkontrolujte externí ID kurzu a nastavení synchronizace.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => filled($record->external_sync_id)
                        && app(\App\Contracts\CourseSyncProvider::class)->enabled()),
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function updateSlug(Forms\Set $set, Forms\Get $get): void
    {
        $parts = [];

        if ($courseTypeId = $get('course_type_id')) {
            $courseType = \App\Models\CourseType::find($courseTypeId);
            if ($courseType) {
                $parts[] = $courseType->full_name;
            }
        }

        if ($trainerId = $get('trainer_id')) {
            $trainer = \App\Models\Trainer::find($trainerId);
            if ($trainer) {
                $parts[] = "s {$trainer->full_name}";
            }
        }

        if ($day = $get('day_of_week')) {
            if ($time = $get('time_of_day')) {
                $parts[] = "{$day} {$time}";
            }
        }

        if ($months = $get('months')) {
            if ($year = $get('year')) {
                $parts[] = "{$months} {$year}";
            }
        }

        if (count($parts) > 0) {
            $autoTitle = implode(', ', $parts);
            $set('slug', Str::slug($autoTitle));
            $set('auto_title', $autoTitle);  // ← NOVÉ! Ukládáme auto_title
        }
    }
}
