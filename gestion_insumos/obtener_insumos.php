<?php
require 'db.php';

if (isset($_GET['sector_id'])) {
    $sector_id = $_GET['sector_id'];

    // Obtener los insumos del sector seleccionado
    $stmt = $pdo->prepare("SELECT * FROM Insumos WHERE sector_id = ?");
    $stmt->execute([$sector_id]);
    $insumos = $stmt->fetchAll();

    // Construir las opciones para el select de insumos
    foreach ($insumos as $insumo) {
        echo '<option value="' . $insumo['id'] . '">' . $insumo['nombre'] . '</option>';
    }
    echo '<option value="otro">Otro (Agregar nuevo insumo)</option>';
}
?>