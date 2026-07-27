<?php

namespace App\Http\Controllers;

use App\Services\WeeklyReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class WeeklyReportController extends Controller
{
    public function download(Request $request, WeeklyReportService $reports)
    {
        abort_unless(
            $request->user()->hasFullChartAccess(),
            403,
            'Weekly PDF reports require ProgressLab+ or Trainer access.'
        );

        $report = $reports->build($request->user());
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.weekly', compact('report'))->render(), 'UTF-8');
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        $filename = 'progresslab-weekly-report-'.$report['period']['start'].'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
