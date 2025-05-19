<!DOCTYPE html>
<html>
<head>
    <title>Appointments List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
            padding-bottom: 30px;
        }
        .header {
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 5px 0;
        }
        .filter-summary {
            margin-bottom: 8px;
            color: #666;
            font-style: italic;
            font-size: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            padding: 13px;
            text-align: left;
            font-weight: bold;
            font-size: 15px;
        }
        td {
            padding: 10px 4px;
            line-height: 1.2;
            font-size: 15px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .doctor-info {
            margin-bottom: 10px;
        }
        .doctor-info h2 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
        }
        @page {
            size: A4;
            margin: 0;
        }
        @media print {
            body {
                margin: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Appointments Report</h1>
        @if(count($filterSummary) > 0)
            <div class="filter-summary">
                {{ implode(' | ', $filterSummary) }}
            </div>
        @endif
    </div>


    <table>
        <thead>
            <tr>
                <th>#</th>
                <!-- <th>Appointment Time</th> -->
                <th>Patient Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($appointments as $index => $appointment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <!-- <td>{{ date('H:i', strtotime($appointment->appointment_time)) }}</td> -->
                    <td>{{ $appointment->patient->Firstname }} {{ $appointment->patient->Lastname }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">z
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>