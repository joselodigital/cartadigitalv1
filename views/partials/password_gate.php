<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Privado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f3f4f6;
            margin: 0;
        }
        .gate-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        h2 { margin-top: 0; color: #1f2937; }
        p { color: #6b7280; margin-bottom: 24px; }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 1rem;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus { border-color: #2563eb; }
        button {
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background: #1d4ed8; }
        .error {
            color: #dc2626;
            background: #fee2e2;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="gate-card">
        <h2>🔒 Catálogo Privado</h2>
        <p>Este catálogo está protegido. Por favor ingresa la contraseña para continuar.</p>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="catalog_password" placeholder="Contraseña del catálogo" required autofocus>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>