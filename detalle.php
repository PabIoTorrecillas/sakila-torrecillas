<?php
require_once 'config.php';
requireAuth();

$actor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($actor_id <= 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT actor_id, first_name, last_name, last_update FROM actor WHERE actor_id = ?");
$stmt->bind_param("i", $actor_id);
$stmt->execute();
$result = $stmt->get_result();
$actor = $result->fetch_assoc();
$stmt->close();

if (!$actor) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT f.film_id, f.title, f.release_year, f.rating, f.length, c.name as category
    FROM film f
    INNER JOIN film_actor fa ON f.film_id = fa.film_id
    LEFT JOIN film_category fc ON f.film_id = fc.film_id
    LEFT JOIN category c ON fc.category_id = c.category_id
    WHERE fa.actor_id = ?
    ORDER BY f.title
");
$stmt->bind_param("i", $actor_id);
$stmt->execute();
$peliculas = $stmt->get_result();
$num_peliculas = $peliculas->num_rows;
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Actor - <?php echo htmlspecialchars($actor['first_name'] . ' ' . $actor['last_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #515151ff 0%, #000000ff 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .actor-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .actor-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .actor-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .info-item .value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .card-header h3 {
            color: #333;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            background: #f8f9fa;
        }
        
        td {
            padding: 15px;
            border-top: 1px solid #e1e8ed;
            font-size: 14px;
            color: #555;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-pg {
            background: #c6f6d5;
            color: #276749;
        }
        
        .badge-g {
            background: #bee3f8;
            color: #2c5282;
        }
        
        .badge-pg13 {
            background: #feebc8;
            color: #7c2d12;
        }
        
        .badge-r {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-nc17 {
            background: #e9d8fd;
            color: #44337a;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>CRUD Sakila - Detalle de Actor</h1>
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <a href="logout.php" class="logout-btn">Cerrar Sesion</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">← Volver al listado</a>
        </div>
        
        <div class="actor-header">
            <h2><?php echo htmlspecialchars($actor['first_name'] . ' ' . $actor['last_name']); ?></h2>
            
            <div class="actor-info">
                <div class="info-item">
                    <label>ID del Actor</label>
                    <div class="value">#<?php echo $actor['actor_id']; ?></div>
                </div>
                <div class="info-item">
                    <label>Número de Películas</label>
                    <div class="value"><?php echo $num_peliculas; ?> película<?php echo $num_peliculas != 1 ? 's' : ''; ?></div>
                </div>
                <div class="info-item">
                    <label>Última Actualización</label>
                    <div class="value"><?php echo date('d/m/Y H:i', strtotime($actor['last_update'])); ?></div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="editar.php?id=<?php echo $actor_id; ?>" class="btn btn-primary">Editar Actor</a>
                <?php if ($num_peliculas > 0): ?>
                    <a href="index.php?delete=<?php echo $actor_id; ?>" 
                       class="btn btn-danger"
                       onclick="return confirm(' ADVERTENCIA: Este actor tiene <?php echo $num_peliculas; ?> película(s) asociada(s).\n\nSe eliminarán:\n- El actor\n- Sus <?php echo $num_peliculas; ?> relación(es) con películas\n\n¿Estás seguro de continuar?')">
                         Eliminar Actor y Relaciones
                    </a>
                <?php else: ?>
                    <a href="index.php?delete=<?php echo $actor_id; ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('¿Estás seguro de eliminar este actor?')">
                         Eliminar Actor
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3> Películas en las que participa</h3>
            </div>
            
            <?php if ($num_peliculas > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Titulo</th>
                            <th>Categoria</th>
                            <th>Año</th>
                            <th>Duracion</th>
                            <th>Clasificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pelicula = $peliculas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pelicula['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pelicula['category'] ?: 'Sin categoría'); ?></td>
                                <td><?php echo $pelicula['release_year']; ?></td>
                                <td><?php echo $pelicula['length']; ?> min</td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace('-', '', $pelicula['rating'])); ?>">
                                        <?php echo htmlspecialchars($pelicula['rating']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>Este actor no tiene peliculas asociadas</h3>
                    <p>Puedes eliminarlo sin problemas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
