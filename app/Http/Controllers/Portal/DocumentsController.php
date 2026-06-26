<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\DocumentsCenterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    public function index(Request $request, DocumentsCenterService $service): View
    {
        return view('portal.documents.index', $service->data($request->user(), $request));
    }
}
