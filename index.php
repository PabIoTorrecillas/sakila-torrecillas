<?php
require_once 'config.php';
requireAuth();

$conn = getConnection();

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM film_actor WHERE actor_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $num_peliculas = $check_result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($num_peliculas > 0) {
        $delete_relations = $conn->prepare("DELETE FROM film_actor WHERE actor_id = ?");
        $delete_relations->bind_param("i", $id);
        $delete_relations->execute();
        $delete_relations->close();
    }
    
    $stmt = $conn->prepare("DELETE FROM actor WHERE actor_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        if ($num_peliculas > 0) {
            $success = "Actor eliminado exitosamente junto con sus $num_peliculas relación(es) de películas";
        } else {
            $success = "Actor eliminado exitosamente";
        }
    } else {
        $error = "Error al eliminar el actor";
    }
    $stmt->close();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$where = '';
if (!empty($search)) {
    $search_param = "%$search%";
    $where = "WHERE first_name LIKE ? OR last_name LIKE ?";
}

$count_sql = "SELECT COUNT(*) as total FROM actor $where";
if (!empty($search)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total / $per_page);

$sql = "SELECT a.actor_id, a.first_name, a.last_name, a.last_update, 
        COUNT(fa.film_id) as num_peliculas 
        FROM actor a 
        LEFT JOIN film_actor fa ON a.actor_id = fa.actor_id 
        $where 
        GROUP BY a.actor_id, a.first_name, a.last_name, a.last_update
        ORDER BY a.actor_id DESC LIMIT ? OFFSET ?";
if (!empty($search)) {
    $where_adjusted = str_replace("WHERE", "WHERE", $where);
    $sql = "SELECT a.actor_id, a.first_name, a.last_name, a.last_update, 
            COUNT(fa.film_id) as num_peliculas 
            FROM actor a 
            LEFT JOIN film_actor fa ON a.actor_id = fa.actor_id 
            $where_adjusted 
            GROUP BY a.actor_id, a.first_name, a.last_name, a.last_update
            ORDER BY a.actor_id DESC LIMIT ? OFFSET ?";
    $sql = str_replace("first_name", "a.first_name", $sql);
    $sql = str_replace("last_name", "a.last_name", $sql);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $search_param, $search_param, $per_page, $offset);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de Actores - Sakila</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #bfbfbfff;
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
            background: rgba(198, 198, 198, 0.2);
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
        
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .btn-edit {
            background: #4299e1;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-edit:hover {
            background: #3182ce;
        }
        
        .btn-delete {
            background: #f56565;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-delete:hover {
            background: #e53e3e;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
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
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>CRUD Sakila - Gestion de Actores</h1>
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <a href="logout.php" class="logout-btn">Cerrar Sesion</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="actions-bar">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Buscar por nombre o apellido..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-primary">Limpiar</a>
                <?php endif; ?>
            </form>
            <a href="crear.php" class="btn btn-success">+ Nuevo Actor</a>
        </div>
        
        <div class="card">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Peliculas</th>
                            <th>Ultima Actualizacion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $tiene_peliculas = $row['num_peliculas'] > 0; ?>
                            <tr>
                                <td><?php echo $row['actor_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                <td>
                                    <?php if ($tiene_peliculas): ?>
                                        <span style="background: #e6f7ff; color: #0066cc; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 500;">
                                             <?php echo $row['num_peliculas']; ?> película<?php echo $row['num_peliculas'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">Sin películas</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['last_update'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="detalle.php?id=<?php echo $row['actor_id']; ?>" 
                                           class="btn btn-edit" 
                                           style="background: #48bb78;"
                                           title="Ver películas del actor">
                                             Ver
                                        </a>
                                        <a href="editar.php?id=<?php echo $row['actor_id']; ?>" 
                                           class="btn btn-edit"> Editar</a>
                                        <a href="?delete=<?php echo $row['actor_id']; ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('<?php 
                                               if ($tiene_peliculas) {
                                                   echo " ADVERTENCIA: Este actor tiene " . $row['num_peliculas'] . " película(s) asociada(s).\\n\\nSe eliminarán:\\n- El actor\\n- Sus " . $row['num_peliculas'] . " relación(es) con películas\\n\\n¿Estás seguro de continuar?";
                                               } else {
                                                   echo "¿Estás seguro de eliminar este actor?";
                                               }
                                           ?>')">
                                             Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No se encontraron actores</h3>
                    <p>Intenta con otros términos de búsqueda o agrega un nuevo actor</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>