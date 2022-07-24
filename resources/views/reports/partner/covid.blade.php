<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
          integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style type="text/css">
        .center {
            display: block;
            margin: 5px auto 15px;
            width: 25%;
        }

        .p-20 {
            padding-left: 20%;
            padding-right: 20%;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row" style="background-color: #000059">
    </div>
    <div class="row">
        <div class="col pt-5 p-20 justify-content-center">
            <h5>Greetings,</h5>
            <p>Kindly find the attached Covid report.</p>
            <br>
            <p>Kind Regards,</p>
            <p>The Kenya HMIS Team</p>
        </div>
    </div>
    <div class="row" style="background-color: #000059; padding-top: 5px; padding-bottom: 3px">
        <div class="col p-5 align-content-center">
            <img class="center" style="margin-top: 3px; margin-bottom: 3px" src="{{$message->embed('img.png')}}" alt="logo">
        </div>
        <div class="col p-5  text-white" style="text-align: center">
            <p style="text-align: center; color: white">This is a system generated email. Do not reply.</p>
        </div>
    </div>
</div>
</body>
</html>
