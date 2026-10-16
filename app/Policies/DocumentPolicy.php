<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            || $user->role === UserRole::Admin
            || $this->isCompanyOperatorOf($user, $document)
            || $this->isAssignedSigner($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    public function sign(User $user, Document $document): bool
    {
        return $document->assignments()->where('signer_id', $user->id)->exists();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id || $this->isCompanyOperatorOf($user, $document);
    }

    public function revoke(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            || $user->role === UserRole::Admin
            || $this->isCompanyOperatorOf($user, $document);
    }

    private function isCompanyOperatorOf(User $user, Document $document): bool
    {
        return $document->company_id !== null
            && $user->company_id === $document->company_id
            && $user->isCompanyOperator();
    }

    private function isAssignedSigner(User $user, Document $document): bool
    {
        return $document->assignments()->where('signer_id', $user->id)->exists();
    }
}
