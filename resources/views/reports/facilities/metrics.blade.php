<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $facility->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        Highcharts.chart('upload-history-chart', {
            title: { text: 'Upload History' },
            yAxis: [{ type: 'logarithmic', minorTickInterval: 0.1, title: { text: 'Patient Count' }}],
            xAxis: [{ categories: [], title: { text: 'Months' }, crosshair: true }],
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle'},
            series: [
                { name: 'NDWH', data: [] },
                { name: 'HTS', data: [] },
                { name: 'PKV', data: [] }
            ],
            responsive: { rules: [{
                condition: { maxWidth: 500 },
                chartOptions: { legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }}
            }]}
        });
    </script>
</head>

<body class="w-full min-h-full flex flex-col justify-start inline-flex items-start bg-transparent flex-none">
    <div class="w-screen mx-auto h-screen flex-none">
        <div class="w-full text-center bg-transparent flex-none">
            <h1 class="pt-4 font-sans text-4xl font-semibold tracking-tighter">
                {{ $facility->name }}
            </h1>
            <br>
            <p class="tracking-tighter text-2xl">
                Below is a Data Quality Assurance report for the National Data Warehouse Refresh process
            </p>
            <h2 class="pt-4 pb-4 text-2xl font-semibold">Indicator Metrics</h2>
            <table class="table-auto">
                <thead>
                    <th>Name</th>
                    <th>Description</th>
                    <th>EMR Value</th>
                    <th>EMR Date</th>
                    <th>NDWH Value</th>
                    <th>NDWH Date</th>
                    <th>Difference</th>
                </thead>
                <tbody>
                    @foreach($metrics as $metric)
                    <tr>
                        <td width="20%">{{ $metric->name }}</td>
                        <td width="40%">{{ $descriptions[$metric->name] }}</td>
                        <td align="right">{{ $metric->value }}</td>
                        <td align="right">{{ $metric->metric_date->format('d M Y') }}</td>
                        <td align="right">{{ $metric->dwh_value }}</td>
                        <td align="right">{{ $metric->dwh_metric_date->format('d M Y') }}</td>
                        <td align="right">{{ $metric->dwh_value - $metric->value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <h2 class="pt-4 pb-4 text-2xl font-semibold">Upload History</h2>
            <div id="upload-history-chart"></div>
            <br>
            <p class="tracking-tighter text-2xl">This report can also be accessed from:</p>
            <a class="tracking-tighter text-2xl" href="{{ $url }}/#/stats/showcase/{{ $facility->uid }}">{{ $url }}/#/stats/showcase/{{ $facility->uid }}</a>
        </div>
    </div>
</body>

</html>
