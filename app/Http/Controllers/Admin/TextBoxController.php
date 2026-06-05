<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TextBox;
use Illuminate\Http\Request;

class TextBoxController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $textBox = new TextBox;
        $textBox->description = $request->description;
        $textBox->col_size = $request->col_size ?? 1;
        $textBox->save();

        return $textBox;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TextBox $textBox)
    {
        $textBox->description = $request->description;
        $textBox->col_size = $request->col_size ?? $textBox->col_size;
        $textBox->save();

        return $textBox;
    }
}
