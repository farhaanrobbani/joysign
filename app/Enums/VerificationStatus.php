<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationStatus: string
{
    case Valid = 'valid';
    case Tampered = 'tampered';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case UnsignedOriginal = 'unsigned_original';
    case NotFound = 'not_found';
}
