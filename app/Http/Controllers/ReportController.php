<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'periode' => ['nullable', 'in:semaine,mois,annee,personnalise'],
            'debut' => ['nullable', 'date', 'required_if:periode,personnalise'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut', 'required_if:periode,personnalise'],
        ]);

        $periode = $data['periode'] ?? 'semaine';

        if ($periode === 'personnalise') {
            $debut = Carbon::parse($data['debut'])->startOfDay();
            $fin = Carbon::parse($data['fin'])->endOfDay();
            $rapport = $this->reportService->rapportPersonnalise($debut, $fin);
        } else {
            $periode = in_array($periode, ['semaine', 'mois', 'annee']) ? $periode : 'semaine';
            $rapport = $this->reportService->rapport($periode);
        }

        return view('reports.index', compact('rapport', 'periode'));
    }
}
