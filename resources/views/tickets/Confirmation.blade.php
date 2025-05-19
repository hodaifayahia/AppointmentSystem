<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmation De Rendez-Vous</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 30px;
            color: #2c3e50;
            background-color: #ffffff;
        }
        .header-image {
            width: 100%;
            max-height: 120px;
            margin-bottom: 30px;
            object-fit: contain;
        }
        .header {
            text-align: right;
            margin-bottom: 40px;
            font-size: 16px;
            color: #34495e;
        }
        .subject {
            font-weight: bold;
            font-size: 18px;
            margin: 25px 0;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            width: fit-content;
        }
        .content {
            margin: 25px 0;
            font-size: 15px;
        }
        .service-info {
            margin: 20px 40px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            font-weight: 500;
        }
        .appointment-details {
            margin: 25px 0;
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
        }
        .contact-info {
            margin: 30px 0;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 5px;
            font-size: 14px;
        }
        .salutation {
            margin: 30px 0;
            font-style: italic;
            color: #34495e;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            color: #34495e;
            font-size: 14px;
        }
        .printed-by {
            margin-top: 40px;
            font-size: 12px;
            color: #6c757d;
            text-align: left;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div>
            <img src="{{ storage_path('app/public/ENTETE.png') }}" class="header-image" alt="En-tête">
        </div>

        <div class="header">
            A M/Mme. {{ strtoupper($patient_first_name) }} {{ strtoupper($patient_last_name) }}
        </div>

        <div class="subject">
            Objet : Confirmation De Rendez-Vous
        </div>

        <div class="content">
            Monsieur/Madame,
            <br><br>
            Nous vous confirmons votre rendez-vous au niveau du :
            <div class="service-info">
                Service : {{ strtoupper($specialization_name) }} (Dr. {{ strtoupper($doctor_name) }})
            </div>
        </div>

        <div class="appointment-details">
            Date : Le {{ $appointment_date }} à {{ $appointment_time }}
        </div>

        <div class="contact-info">
            <strong>Important :</strong> En cas d'empêchement, merci de nous contacter rapidement au
            <br>
            N° 029 23 99 99
        </div>

        <div class="salutation">
            Veuillez agréer Monsieur/Madame, l'expression de nos salutations distinguées.
        </div>

        <div class="footer">
            <p>Salutations</p>
        </div>

       
    </div>
</body>
</html>