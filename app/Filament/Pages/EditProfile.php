<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Form;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                SignaturePad::make('signature')
                    ->label('Official Signature')
                    ->backgroundColor('rgb(255, 255, 255)')
                    ->penColor('rgb(15, 23, 42)')
                    ->hint('This signature will be saved on your profile and can be used for E-Sign approvals with a single click.')
                    ->nullable()
            ]);
    }
}
