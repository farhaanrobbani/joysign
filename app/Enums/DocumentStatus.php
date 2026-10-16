<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case PartiallySigned = 'partially_signed';
    case Signed = 'signed';
    case Revoked = 'revoked';
}
