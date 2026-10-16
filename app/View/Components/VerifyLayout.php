<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class VerifyLayout extends Component
{
    public function render(): View
    {
        return view('layouts.verify');
    }
}
