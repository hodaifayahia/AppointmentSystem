<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket de Rendez-vous</title>
    <style>
        @page {
            margin: 0 ;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', monospace;
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            width: 100%;
            color: #2c3e50;
        }
        .container {
            width: 100%;
            max-width: 80mm;
            margin: 0 ;
            padding: 20px 10px 20px 10px;
        }
        .header-image {
            margin-top: 10px;
            width: 90%;
            max-height: 70px;
            margin-bottom: 10px;
        }
        .ticket-header {
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 12pt;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .ticket-content {
            margin: 1px 0;
            padding: 0;
        }
        .ticket-row {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        .ticket-label {
            font-weight: bold;
            color: #34495e;
        }
        .ticket-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 8pt;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .printed-by {
            font-size: 8pt;
            text-align: center;
            margin-top: 5px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div>
            <img src="{{ storage_path('app/public/ENTETE.png') }}" class="header-image" alt="En-tête">
        </div>

        <div class="ticket-header">
            TICKET DE RENDEZ-VOUS
        </div>

        <div class="ticket-content">
            <div class="ticket-row">
                <span class="ticket-label">Patient :</span> 
                {{ strtoupper($patient_first_name) }} {{ strtoupper($patient_last_name) }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Médecin :</span> 
                Dr. {{ $doctor_name }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Date :</span> 
                {{ $appointment_date }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Heure :</span> 
                {{ $appointment_time }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Phone</span> 
                029 23 99 99 / 05 55 88 99 97
            </div>
        </div>

        <div class="ticket-footer">
            <div>
                Imprimé le : {{ now()->format('d/m/Y à H:i') }}
            </div>
            <div>
                Imprimé par : {{ $user_name }}
            </div>
        </div>
    </div>
</body>
</html>