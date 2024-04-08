<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function myFiles() {
        return Inertia::render('MyFiles');
    }
}
