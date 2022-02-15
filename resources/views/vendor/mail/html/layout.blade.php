<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
</head>
<body>
<style>
    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 25%;
    }

    .p-20 {
        padding-left: 20%;
        padding-right: 20%;
        font-size: 18px;
    }

    table {
        width: 100%;
        border-spacing: 0;
    }

    th {
        text-align: left;
    }

    tr {
        background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;
        background-size: 5px 4px;
    }

    .table1 td:first-child {
        font-weight: bold;
        color: blue;
    }

    td {
        padding: 15px;
    }
</style>


<!-- Body content -->
{{ Illuminate\Mail\Markdown::parse($slot) }}

{{ $subcopy ?? '' }}



</body>
</html>
