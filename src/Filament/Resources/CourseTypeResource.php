<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseTypeResource\Pages;
use App\Models\CourseType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CourseTypeResource extends Resource
{
    protected static ?string $model = CourseType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set, Forms\Get $get) {
                        if ($operation === 'create') {
                            $ageCategory = $get('age_category');
                            $slugBase = $state;
                            if ($ageCategory) {
                                $slugBase .= ' '.$ageCategory;
                            }
                            $set('slug', Str::slug($slugBase));
                        }
                    }),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                        return $rule->whereNull('deleted_at');
                    }),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('hero_image')
                    ->label('Hero fotka')
                    ->image()
                    ->directory('course_types')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('675')
                    ->helperText('Doporučená velikost: 1200×675 px, formát WebP nebo JPG')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_for_children')
                    ->label('Je pro děti?')
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, $state) => ! $state ? $set('age_category', null) : null
                    ),

                Forms\Components\TextInput::make('age_category')
                    ->label('Věková kategorie')
                    ->placeholder('např. 6-10 let')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $name = $get('name');
                        if ($name) {
                            $slugBase = $name;
                            if ($state) {
                                $slugBase .= ' '.$state;
                            }
                            $set('slug', Str::slug($slugBase));
                        }
                    })
                    ->visible(fn (Forms\Get $get) => $get('is_for_children')),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktivní')
                    ->default(true),

                Forms\Components\TextInput::make('order')
                    ->label('Pořadí')
                    ->numeric()
                    ->default(0)
                    ->helperText('Nižší číslo = zobrazí se dříve'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('hero_image')
                    ->label('Hero')
                    ->circular(false)
                    ->height(40),

                Tables\Columns\TextColumn::make('age_category')
                    ->label('Věková kategorie')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_for_children')
                    ->label('Pro děti')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktivní')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Pořadí')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            'index' => Pages\ListCourseTypes::route('/'),
            'create' => Pages\CreateCourseType::route('/create'),
            'edit' => Pages\EditCourseType::route('/{record}/edit'),
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
