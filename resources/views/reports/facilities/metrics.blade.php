<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $facility->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col pt-5 justify-content-center">
                <h3 class="mt-4">{{ $facility->name }}</h3>
                <h4 class="mt-4 mb-3">Indicator Metrics</h4>
                <table class="table table-bordered table-sm">
                    <thead>
                        <th>Name</th>
                        <th>Description</th>
                        <th>EMR Value</th>
                        <th>EMR Date</th>
                        <th>DWH Value</th>
                        <th>DWH Date</th>
                        <th>Diff</th>
                    </thead>
                    <tbody>
                        @foreach($metrics as $metric)
                        <tr>
                            <td width="20%">{{ $metric->name }}</td>
                            <td width="30%">{{ $descriptions[$metric->name] }}</td>
                            <td align="right">{{ $metric->value }}</td>
                            <td align="right">{{ $metric->metric_date->format('d M Y') }}</td>
                            <td align="right">{{ $metric->dwh_value }}</td>
                            <td align="right">{{ $metric->dwh_metric_date->format('d M Y') }}</td>
                            <td align="right">{{ $metric->dwh_value - $metric->value }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <h4 class="mt-4 mb-3">Upload History</h4>
                <div id="upload-history-chart"></div>
                <a href="{{ $url }}" style="color: #0000ee;" class="mt-4">{{ $url }}</a>
            </div>
        </div>
    </div>
    <script>
        const months = @json($months);
        const data = @json($data);
        Highcharts.chart('upload-history-chart', {
            title: { text: '' },
            yAxis: [{ type: 'logarithmic', minorTickInterval: 0.1, title: { text: 'Patient Count' }}],
            xAxis: [{ categories: months, title: { text: 'Months' }, crosshair: true }],
            legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom'},
            series: [
                { name: 'NDWH', data: data['NDWH'] },
                { name: 'HTS', data: data['HTS'] },
                { name: 'PKV', data: data['MPI'] }
            ]
        });
    </script>
</body>
</html>
