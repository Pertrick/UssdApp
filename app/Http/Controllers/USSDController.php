<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use Illuminate\Http\Request;
use Inertia\Inertia;

class USSDController extends Controller
{
    public function create()
    {
        return Inertia::render('USSD/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'pattern' => 'required|string|unique:ussds',
        ]);

        // Create USSD logic here

        return redirect()->route('ussd.configure');
    }

    public function configure()
    {
        return Inertia::render('USSD/Configure');
    }

    public function simulator()
    {
        return Inertia::render('USSD/Simulator');
    }
}
