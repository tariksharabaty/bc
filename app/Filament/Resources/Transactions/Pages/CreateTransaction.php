<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\PiggyBank;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }

    /**
     * Ensure user_id is always set to the authenticated user on the Saha panel,
     * even if the hidden/disabled field was somehow omitted from the submitted data.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filament()->getCurrentPanel()?->getId() === 'saha') {
            $data['user_id'] = auth()->id();
        }

        if (empty($data['action_type'])) {
            $data['action_type'] = 'collection';
        }

        if (!empty($data['piggy_bank_id'])) {
            $piggyBank = PiggyBank::find($data['piggy_bank_id']);
            if ($piggyBank) {
                $data['donation_category'] = $piggyBank->donation_category;
                
                $piggyDetails = $piggyBank->category_details ?? [];
                $formDetails = $data['category_details'] ?? [];
                $data['category_details'] = array_merge($piggyDetails, $formDetails);
            }
        }

        return $data;
    }

    /**
     * After creating the transaction, update the piggy bank's current_balance
     * to reflect the new collection amount.
     */
    protected function afterCreate(): void
    {
        $transaction = $this->record;

        if ($transaction->action_type === 'collection') {
            $piggyBank = PiggyBank::find($transaction->piggy_bank_id);
            if ($piggyBank) {
                $piggyBank->increment('current_balance', $transaction->amount);
            }
        } elseif ($transaction->action_type === 'reset') {
            $piggyBank = PiggyBank::find($transaction->piggy_bank_id);
            if ($piggyBank) {
                $piggyBank->update(['current_balance' => 0]);
            }
        }
    }

    /**
     * Redirect field agents back to the Saha dashboard after creating a transaction.
     */
    protected function getRedirectUrl(): string
    {
        if (filament()->getCurrentPanel()?->getId() === 'saha') {
            return '/saha';
        }
        return parent::getRedirectUrl();
    }
}
