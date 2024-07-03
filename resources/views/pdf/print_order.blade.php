<!DOCTYPE html>
<html lang="fr" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>COMMANDE</title>

    <style>
    @page {
            margin: 0.2cm 0.1cm; /* Remove default margins */
        }
        .coord {
            margin-bottom: 2px;
            text-align: left;
        }
        .coord table {
            width: 100%;
            border-collapse: collapse;
        }
        .coord th, .coord td {
            padding: 5px;
            border: 1px solid black;
            text-align: left;
            font-size:0.5rem;
        }
        
        .coord .col1{
             font-weight: normal;
        }
        
        .finance {
            margin-bottom: 2px;
            text-align: left;
        }
         .finance table {
            width: 100%;
            border-collapse: collapse;
        }
        .finance th, .finance td {
            padding: 5px;
            border: 1px solid black;
            text-align: left;
            font-size:0.5rem;
        }
        
        .items {
            margin-bottom: 2px;
            text-align: left;
        }
         .items table {
            width: 100%;
            border-collapse: collapse;
        }
         .items th, .items td {
            padding: 5px;
            border: 1px solid black;
            text-align: left;
            font-size:0.5rem;
        }
        

        @media print {
          table {
                   page-break-before: auto; /* Force page break before each table */
              }
          }

         body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .receipt {
            width: 200px;
            margin: 0 auto;

        }
        .header {
             text-align: center;
            margin-bottom: 2px;
        }
        .header h1 {
            margin: 0;
            font-size: 10px;
        }
        .header p {
            margin: 5px 0;
            font-size: 10px;
        }

    </style>
</head>    
<body>
         <div class="receipt">
                <div class="header">
                    <h1>KEWOUSTORE</h1>
                    <p>YOPOUGON KM17</p>
                    <p>Tel: 01 73 832 778 / 01 02 669 620</p>
                    <p>Le {{ (new \DateTime($resPos[0]->created_at))->format('d/m/Y') }} à {{ (new \DateTime($resPos[0]->created_at))->format('H:i:s') }}</p>
                </div>
         </div>
         <div class="coord">
             <table>
                 <tbody>
                     <tr>
                        <th>CAISSIER(E)</th>
                        <th class="col1">{{ strtoupper($resPos[0]->caissier) }}</th>
                    </tr>
                     <tr>
                        <th>PAIEMENT</th>
                        <th class="col1">{{ $resPos[0]->paid_method }}</th>
                    </tr>
                     <tr>
                        <th>CLIENT</th>
                        <th class="col1">{{ strtoupper($resPos[0]->client) }}</th>
                    </tr>
                    <tr>
                        <th>ID TRANSACTION</th>
                        <th class="col1" >{{ $resPos[0]->transaction_id }}</th>
                    </tr>
                 </tbody>
             </table>
        </div>
         <div class="items">
             <table>
                 <thead style="background-color: #000000;color:white;">
                     <tr>
                        <th>DESIGNATION</th>
                        <th>PU</th>
                        <th>QTE</th>
                        <th>TOTAL</th>
                    </tr>
                 </thead>
                 <tbody>
                     @foreach($resPosItems as $psIt)
                        <tr>
                            <td>{{ ucfirst($psIt->produit) }}</td>
                            <td>{{ number_format($psIt->price, 0, ',', ' ') }}</td>
                            <td>{{ number_format($psIt->qte, 0, ',', ' ') }}</td>
                            <td>{{ number_format($psIt->price_by_qte, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                 </tbody>
             </table>
        </div>
         <div class="finance">
             <table>
                 <tbody>
                     <tr>
                        <td style="width: 20%">REMISE(CFA)</td>
                        <td style="width: 30%" class="col1">{{ number_format($resPos[0]->remise, 0, ',', ' ') }}</td>
                        <td style="width: 20%" >ESPÈCE(CFA)</td>
                        <td style="width: 30%" class="col1">{{ number_format($resPos[0]->espece, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 20%">TVA(%)</td>
                        <td style="width: 30%" class="col1">{{ number_format($resPos[0]->tva, 0, ',', ' ') }}</td>
                        <td style="width: 20%" >MONNAIE(CFA)</td>
                        <td style="width: 30%" class="col1">{{ number_format($resPos[0]->monnaie, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 25%"></td>
                        <td style="width: 25%"></td>
                        <td style="width: 25%" >TOTAL(CFA)</td>
                        <td style="width: 25%;background-color: #000000;color:white;font-weight: bold" class="col1">{{ number_format($resPos[0]->qte_total, 0, ',', ' ') }}</td>
                    </tr>
                    
                 </tbody>
             </table>
        </div>
        <hr/>
        <div class="receipt">
                <div class="header">
                    <h1>Vérifiez votre monnaie avant de sortir.</h1>
                    <p>Merci pour votre fidelité</p>
                </div>
         </div>
</body>
</html>
