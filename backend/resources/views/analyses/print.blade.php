<!DOCTYPE html>
<html lang="fr" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $analysis->analysis_number }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="lms-print-body">
        <div class="lms-print-toolbar no-print">
            <button onclick="window.print()">{{ __('messages.print_now') }}</button>
        </div>

        @include('analyses.partials.report', ['analysis' => $analysis, 'groupedResults' => $groupedResults, 'layout' => $layout, 'identity' => $identity])
    </body>
</html>
