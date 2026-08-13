<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditProfile extends BaseEditProfile
{
    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->label('Current Password')
            ->placeholder('Required if changing password')
            ->visible(true)
            ->required(fn (Get $get): bool => filled($get('password')));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Confirm Password')
            ->visible(true)
            ->required(fn (Get $get): bool => filled($get('password')));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->schema([
                Section::make('Profile Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                $this->getNameFormComponent(),
                                $this->getEmailFormComponent(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                $this->getCurrentPasswordFormComponent(),
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                            ]),
                        SignaturePad::make('signature')
                            ->label('Official Signature')
                            ->backgroundColor('rgb(248, 250, 252)')
                            ->exportBackgroundColor('rgb(255, 255, 255)')
                            ->penColor('rgb(15, 23, 42)')
                            ->helperText('This signature will be saved on your profile and can be used for E-Sign approvals with a single click.')
                            ->nullable(),
                    ]),
            ]);
    }
}
