<?php

namespace App\Services;

use App\Models\Repayment;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Genere les rapports periodiques (semaine, mois, annee, ou plage de dates
 * personnalisee) utilises a la fois par le tableau de bord Admin et par
 * l'espace Directeur, pour garantir que les deux voient exactement les
 * memes chiffres.
 */
class ReportService
{
    public function rapport(string $periode): array
    {
        return match ($periode) {
            'semaine' => $this->rapportParJour(now()->startOfWeek(), now()->endOfWeek()),
            'mois' => $this->rapportParJour(now()->startOfMonth(), now()->endOfMonth()),
            'annee' => $this->rapportParMois(now()->startOfYear(), now()->endOfYear()),
            default => $this->rapportParJour(now()->startOfWeek(), now()->endOfWeek()),
        };
    }

    /** Plage de dates libre choisie par l'utilisateur (recherche). */
    public function rapportPersonnalise(Carbon $debut, Carbon $fin): array
    {
        if ($debut->gt($fin)) {
            [$debut, $fin] = [$fin, $debut];
        }

        // Au-dela de ~2 mois, grouper par mois plutot que par jour (lisibilite).
        return $debut->diffInDays($fin) > 62
            ? $this->rapportParMois($debut, $fin)
            : $this->rapportParJour($debut, $fin);
    }

    protected function rapportParJour(Carbon $debut, Carbon $fin): array
    {
        $transactions = Transaction::whereBetween('effectuee_le', [$debut, $fin])->get();
        $remboursements = Repayment::whereBetween('effectue_le', [$debut, $fin])->get();

        $lignes = [];
        for ($jour = $debut->copy(); $jour->lte($fin); $jour->addDay()) {
            $tJour = $transactions->filter(fn ($t) => $t->effectuee_le->isSameDay($jour));
            $rJour = $remboursements->filter(fn ($r) => $r->effectue_le->isSameDay($jour));

            $lignes[] = [
                'label' => $jour->translatedFormat('l d/m/Y'),
                'depots' => (float) $tJour->where('type', 'depot')->sum('montant'),
                'retraits' => (float) $tJour->where('type', 'retrait')->sum('montant'),
                'remboursements' => (float) $rJour->sum('montant'),
                'nb' => $tJour->count() + $rJour->count(),
                'est_aujourdhui' => $jour->isToday(),
            ];
        }

        return $this->assembler($lignes, 'jour');
    }

    protected function rapportParMois(Carbon $debut, Carbon $fin): array
    {
        $transactions = Transaction::whereBetween('effectuee_le', [$debut, $fin])->get();
        $remboursements = Repayment::whereBetween('effectue_le', [$debut, $fin])->get();

        $lignes = [];
        for ($mois = $debut->copy()->startOfMonth(); $mois->lte($fin); $mois->addMonth()) {
            $tMois = $transactions->filter(fn ($t) => $t->effectuee_le->isSameMonth($mois));
            $rMois = $remboursements->filter(fn ($r) => $r->effectue_le->isSameMonth($mois));

            $lignes[] = [
                'label' => $mois->translatedFormat('F Y'),
                'depots' => (float) $tMois->where('type', 'depot')->sum('montant'),
                'retraits' => (float) $tMois->where('type', 'retrait')->sum('montant'),
                'remboursements' => (float) $rMois->sum('montant'),
                'nb' => $tMois->count() + $rMois->count(),
                'est_aujourdhui' => $mois->isSameMonth(now()),
            ];
        }

        return $this->assembler($lignes, 'mois');
    }

    protected function assembler(array $lignes, string $granularite): array
    {
        return [
            'lignes' => $lignes,
            'granularite' => $granularite,
            'totalDepots' => array_sum(array_column($lignes, 'depots')),
            'totalRetraits' => array_sum(array_column($lignes, 'retraits')),
            'totalRemboursements' => array_sum(array_column($lignes, 'remboursements')),
            'totalOperations' => array_sum(array_column($lignes, 'nb')),
        ];
    }
}
