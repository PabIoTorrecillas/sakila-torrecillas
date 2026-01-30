<?php

require_once 'config.php';

$mensaje = '';
$tipo = '';

$conn = getConnection();
$result = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE usuario = 'admin'");
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    $password = 'sudoapt';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE usuarios SET password = ?, nombre = ? WHERE usuario = ?");
    $nombre = 'Pablo';
    $usuario = 'pablo';
    $stmt->bind_param("sss", $hash, $nombre, $usuario);
    
    if ($stmt->execute()) {
        $mensaje = "Contrasenna del usuario 'pablo' actualizada exitosamente a 'sudoapt'";
        $tipo = 'success';
    } else {
        $mensaje = "Error al actualizar el usuario: " . $conn->error;
        $tipo = 'error';
    }
    $stmt->close();
} else {
    $usuario = 'pablo';
    $password = 'sudoapt';
    $nombre = 'Pablo';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, nombre) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $usuario, $hash, $nombre);
    
    if ($stmt->execute()) {
        $mensaje = "Usuario 'pablo' creado exitosamente con contraseña 'sudoapt'";
        $tipo = 'success';
    } else {
        $mensaje = "Error al crear el usuario: " . $conn->error;
        $tipo = 'error';
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Pablo - Sakila</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert {
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 16px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border-left: 4px solid #48bb78;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }
        
        .info-box {
            background: #f7fafc;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4299e1;
        }
        
        .info-box h3 {
            color: #2c5282;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #4a5568;
            line-height: 1.6;
        }
        
        .credentials {
            background: #edf2f7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .credentials strong {
            color: #2d3748;
        }
        
        code {
            background: #f7fafc;
            padding: 2px 8px;
            border-radius: 3px;
            color: #667eea;
            font-weight: 500;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px auto;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .warning {
            background: #fef5e7;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #f39c12;
            color: #856404;
            margin: 20px 0;
        }
        
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configuracion Inicial</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($tipo === 'success'): ?>
            <div class="info-box">
                <h3>Configuracion completada</h3>
                <div class="credentials">
                    <strong>Credenciales de acceso:</strong><br>
                    Usuario: <code>pablo</code><br>
                    Contrasenna: <code>sudoapt</code>
                </div>
                <p>Ya puedes iniciar sesión en el sistema.</p>
            </div>
            
            <div class="warning">
                <strong>Importante:</strong> Por razones de seguridad, elimina el archivo 
                <code>crear_usuario.php</code> despues de crear el usuario.
            </div>
            
            <div class="btn-container">
                <a href="login.php" class="btn">Ir al Login →</a>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>❌ Error en la configuración</h3>
                <p>Verifica que:</p>
                <ul style="margin-left: 20px; margin-top: 10px; color: #4a5568;">
                    <li>La base de datos 'sakila' existe</li>
                    <li>Has importado el archivo setup.sql</li>
                    <li>Los datos de conexión en config.php son correctos</li>
                    <li>MySQL está en ejecución</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
