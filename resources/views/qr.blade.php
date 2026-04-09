<!DOCTYPE html>
<html>
<head>
    <title>Barcode - {{ $produit->libelle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #121212;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            background: #1A1A1A;
            border: 1px solid #2A2A2A;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 152, 0, 0.15);
            border: 1px solid rgba(255, 152, 0, 0.4);
            color: #FF9800;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
        }

        .product-name {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .product-price {
            color: #FF9800;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .barcode-wrapper {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px 24px 12px;
            display: inline-block;
            margin-bottom: 12px;
        }

        .barcode-wrapper svg {
            display: block;
        }

        .code-text {
            color: #333;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 3px;
            margin-top: 8px;
            font-family: monospace;
        }

        .divider {
            border: none;
            border-top: 1px solid #2A2A2A;
            margin: 20px 0;
        }

        .meta {
            color: #ffffff38;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .print-btn {
            margin-top: 20px;
            background: #FF9800;
            color: #000;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            letter-spacing: 0.5px;
        }

        .print-btn:hover {
            background: #e68900;
        }

        @media print {
            body { background: white; }
            .card { border: none; background: white; }
            .product-name { color: #000; }
            .meta { color: #999; }
            .print-btn { display: none; }
            .badge { border-color: #FF9800; }
        }
    </style>
</head>
<body>

<div class="card">

    <div class="badge">BANKY POS</div>

    <div class="product-name">{{ $produit->libelle }}</div>

    @if(isset($produit->selling_price))
        <div class="product-price">
            {{ number_format($produit->selling_price, 0, ',', ' ') }} F CFA
        </div>
    @endif

    <div class="barcode-wrapper">
        <?php
            $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
            echo $generator->getBarcode(
                $produit->code,
                $generator::TYPE_CODE_128,
                2,    // largeur barre
                80    // hauteur
            );
        ?>
        <div class="code-text">{{ $produit->code }}</div>
    </div>

    <hr class="divider">

    <div class="meta">
        Scannez ce code avec l'application mobile
    </div>

    <br>
    <button class="print-btn" onclick="window.print()">
        🖨️ Imprimer
    </button>

</div>

</body>
</html>