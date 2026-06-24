<?php

namespace App\Filament\Resources\Niches;

use App\Filament\Resources\Niches\Pages\CreateNiche;
use App\Filament\Resources\Niches\Pages\EditNiche;
use App\Filament\Resources\Niches\Pages\ListNiches;
use App\Filament\Resources\Niches\Schemas\NicheForm;
use App\Filament\Resources\Niches\Tables\NichesTable;
use App\Models\Niche;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NicheResource extends Resource
{
    protected static ?string $model = Niche::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'Niche';

    protected static ?string $label = 'Nicho';

    protected static ?string $pluralLabel = 'Nichos';

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Niche::query()->active()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return NicheForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NichesTable::configure($table);
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
            'index' => ListNiches::route('/'),
            'create' => CreateNiche::route('/create'),
            'edit' => EditNiche::route('/{record}/edit'),
        ];
    }
}
