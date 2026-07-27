<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ProgressLab Weekly Report</title>
  <style>
    @page { margin: 22px 30px 34px; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      color: #172033;
      font-family: "DejaVu Sans", sans-serif;
      font-size: 10px;
      line-height: 1.45;
    }
    .header {
      padding: 16px 20px;
      border-radius: 12px;
      background: #081426;
      color: #fff;
    }
    .brand { color: #42c6ff; font-size: 11px; font-weight: bold; letter-spacing: 1.3px; text-transform: uppercase; }
    h1 { margin: 3px 0 2px; font-size: 23px; line-height: 1.2; }
    .period { color: #b9c8dc; font-size: 11px; }
    .member { margin-top: 8px; padding-top: 7px; border-top: 1px solid #26364c; color: #dce8f7; }
    .summary { width: 100%; margin: 10px 0 12px; border-spacing: 7px 0; }
    .summary td { width: 25%; padding: 9px 11px; border: 1px solid #d9e2ef; border-radius: 8px; background: #f4f8fc; }
    .summary span { display: block; color: #64748b; font-size: 8px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
    .summary strong { display: block; margin-top: 3px; color: #0c4fa8; font-size: 16px; }
    .section { margin-top: 12px; }
    .section--weight { page-break-inside: avoid; }
    .section-title { margin: 0 0 6px; padding-bottom: 5px; border-bottom: 2px solid #249ee9; color: #10233d; font-size: 14px; page-break-after: avoid; }
    table.data { width: 100%; border-collapse: collapse; }
    .data th { padding: 5px 8px; background: #eaf4fc; color: #31516f; font-size: 8px; letter-spacing: .45px; text-align: left; text-transform: uppercase; }
    .data td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .data tr:nth-child(even) td { background: #f8fafc; }
    .number { text-align: right; }
    .muted { color: #64748b; }
    .empty { padding: 14px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; color: #64748b; text-align: center; }
    .measurements { width: 100%; border-spacing: 7px; margin: -7px; page-break-inside: avoid; }
    .measurements td { width: 33.33%; padding: 10px; border: 1px solid #dbe4ee; border-radius: 7px; }
    .measurements span { display: block; color: #64748b; font-size: 8px; text-transform: uppercase; }
    .measurements strong { display: block; margin-top: 3px; color: #172033; font-size: 14px; }
    .note { margin-top: 16px; padding: 10px 12px; border-left: 3px solid #249ee9; background: #f1f8fe; color: #52667d; font-size: 9px; }
    .footer { position: fixed; right: 0; bottom: -25px; left: 0; color: #7b8ba0; font-size: 8px; text-align: center; }
    .footer .page:after { content: counter(page); }
  </style>
</head>
<body>
  @php
    $format = fn ($value, $decimals = 0) => $value === null ? '-' : number_format((float) $value, $decimals);
    $visibility = $visibility ?? ['nutrition' => true, 'training' => true, 'weight' => true];
  @endphp

  <div class="footer">ProgressLab - Private weekly fitness report - Page <span class="page"></span></div>

  <header class="header">
    <div class="brand">ProgressLab</div>
    <h1>Weekly Progress Report</h1>
    <div class="period">{{ $report['period']['label'] }}</div>
    <div class="member">
      <strong>{{ $report['user']['name'] }}</strong>
      <span> | {{ $report['user']['email'] }} | Generated {{ $report['period']['generated_at'] }}</span>
    </div>
  </header>

  <table class="summary">
    <tr>
      <td><span>Nutrition days</span><strong>{{ $visibility['nutrition'] ? $report['nutrition_days_logged'].'/7' : '-' }}</strong></td>
      <td><span>Workouts</span><strong>{{ $visibility['training'] ? $report['training']['workouts'] : '-' }}</strong></td>
      <td><span>Total sets</span><strong>{{ $visibility['training'] ? $report['training']['sets'] : '-' }}</strong></td>
      <td><span>Training volume</span><strong>{{ $visibility['training'] ? $format($report['training']['volume_kg']).' kg' : '-' }}</strong></td>
    </tr>
  </table>

  @if($visibility['nutrition'])
  <section class="section">
    <h2 class="section-title">Nutrition overview</h2>
    <table class="data">
      <thead>
        <tr>
          <th>Metric</th>
          <th class="number">Weekly total</th>
          <th class="number">Daily average</th>
          <th class="number">Daily target</th>
          <th class="number">Target reached</th>
        </tr>
      </thead>
      <tbody>
        @foreach($report['nutrition'] as $macro)
          <tr>
            <td><strong>{{ $macro['label'] }}</strong></td>
            <td class="number">{{ $format($macro['total'], 1) }} {{ $macro['unit'] }}</td>
            <td class="number">{{ $format($macro['average'], 1) }} {{ $macro['unit'] }}</td>
            <td class="number">{{ $format($macro['target'], 1) }} {{ $macro['unit'] }}</td>
            <td class="number">{{ $macro['target_percent'] === null ? '-' : $macro['target_percent'].'%' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <p class="muted">Daily averages use days with a recorded nutrition entry.</p>
  </section>
  @endif

  @if($visibility['training'])
  <section class="section">
    <h2 class="section-title">Training summary</h2>
    <table class="data">
      <tbody>
        <tr>
          <td><strong>Exercises logged</strong></td>
          <td class="number">{{ $report['training']['exercises'] }}</td>
          <td><strong>Total repetitions</strong></td>
          <td class="number">{{ $report['training']['reps'] }}</td>
        </tr>
        <tr>
          <td><strong>Highest weight used</strong></td>
          <td class="number">{{ $format($report['training']['max_weight_kg'], 1) }} kg</td>
          <td><strong>Total training volume</strong></td>
          <td class="number">{{ $format($report['training']['volume_kg'], 1) }} kg</td>
        </tr>
      </tbody>
    </table>

    @if($report['workouts']->isNotEmpty())
      <table class="data" style="margin-top: 10px;">
        <thead>
          <tr>
            <th>Date</th>
            <th>Workout</th>
            <th class="number">Exercises</th>
            <th class="number">Sets</th>
            <th class="number">Reps</th>
            <th class="number">Volume</th>
          </tr>
        </thead>
        <tbody>
          @foreach($report['workouts'] as $workout)
            <tr>
              <td>{{ $workout['date'] }}</td>
              <td><strong>{{ $workout['name'] }}</strong></td>
              <td class="number">{{ $workout['exercises'] }}</td>
              <td class="number">{{ $workout['sets'] }}</td>
              <td class="number">{{ $workout['reps'] }}</td>
              <td class="number">{{ $format($workout['volume_kg'], 1) }} kg</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="empty" style="margin-top: 10px;">No completed workouts were recorded this week.</div>
    @endif
  </section>
  @endif

  @if($visibility['weight'])
  <section class="section section--weight">
    <h2 class="section-title">Weight and body measurements</h2>
    <table class="data">
      <tbody>
        <tr>
          <td><strong>Current weight</strong></td>
          <td class="number">{{ $format($report['weight']['current'], 1) }} kg</td>
          <td><strong>Weekly weight entries</strong></td>
          <td class="number">{{ $report['weight']['entries'] }}</td>
        </tr>
        <tr>
          <td><strong>Start to end</strong></td>
          <td class="number">{{ $format($report['weight']['start'], 1) }} kg to {{ $format($report['weight']['end'], 1) }} kg</td>
          <td><strong>Weekly change</strong></td>
          <td class="number">{{ $report['weight']['change'] !== null && $report['weight']['change'] > 0 ? '+' : '' }}{{ $format($report['weight']['change'], 2) }} kg</td>
        </tr>
      </tbody>
    </table>

    @if($report['latest_body'])
      <p class="muted">Latest saved body measurements: {{ $report['latest_body']['date'] }}</p>
      <table class="measurements">
        <tr>
          <td><span>Waist</span><strong>{{ $format($report['latest_body']['waist_cm'], 1) }} cm</strong></td>
          <td><span>Arms</span><strong>{{ $format($report['latest_body']['arms_cm'], 1) }} cm</strong></td>
          <td><span>Thighs</span><strong>{{ $format($report['latest_body']['thighs_cm'], 1) }} cm</strong></td>
        </tr>
        <tr>
          <td><span>Hips</span><strong>{{ $format($report['latest_body']['hips_cm'], 1) }} cm</strong></td>
          <td><span>Glutes / seat</span><strong>{{ $format($report['latest_body']['glutes_cm'], 1) }} cm</strong></td>
          <td><span>Check-ins this week</span><strong>{{ $report['body_checkins'] }}</strong></td>
        </tr>
      </table>
    @else
      <div class="empty" style="margin-top: 10px;">No body measurement check-in is available yet.</div>
    @endif
  </section>
  @endif

  <div class="note">
    This report summarizes values entered in ProgressLab. It is a personal tracking document and is not medical advice.
  </div>
</body>
</html>
