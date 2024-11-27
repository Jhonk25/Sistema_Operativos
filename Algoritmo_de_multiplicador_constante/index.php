<?php
class MultiplicadorConstante {
    private $semilla;
    private $constante;
    private $digitos;
    private $pasos = [];

    public function __construct($semilla, $constante, $digitos = 4) {
        if ($digitos <= 3) {
            throw new Exception("Los dígitos deben ser mayores a 3");
        }

        $this->semilla = $semilla;
        $this->constante = $constante;
        $this->digitos = $digitos;
    }

    public function generarNumerosAleatorios($cantidad = 100) {
        $this->pasos = [];
        $numeros = [];
        $numeros_unicos = [];
        $valores_repetidos = []; // Nuevo array para tracking único de repetidos
        $x = $this->semilla;
        $max_iteraciones = $cantidad * 10;
        $iteraciones_realizadas = 0;

        $this->pasos[] = [
            'paso' => 0,
            'x' => $x,
            'y' => null,
            'x_siguiente' => null,
            'r' => null
        ];

        for ($i = 0; $i < $max_iteraciones; $i++) {
            $iteraciones_realizadas++;
            
            $y = $this->constante * $x;
            $y_str = str_pad($y, $this->digitos * 2, '0', STR_PAD_LEFT);
            $inicio = floor((strlen($y_str) - $this->digitos) / 2);
            $x_siguiente = intval(substr($y_str, $inicio, $this->digitos));
            $r = $x_siguiente / pow(10, $this->digitos);
            $r_redondeado = round($r, 4);

            $this->pasos[] = [
                'paso' => $i + 1,
                'x' => $x,
                'y' => $y,
                'x_siguiente' => $x_siguiente,
                'r' => $r
            ];

            if (!in_array($r_redondeado, $numeros_unicos)) {
                $numeros_unicos[] = $r_redondeado;
                $numeros[] = $r;
            } else {
                // Solo registrar el valor repetido una vez si no está ya en valores_repetidos
                if (!isset($valores_repetidos[$r_redondeado])) {
                    $valores_repetidos[$r_redondeado] = [
                        'valor' => $r_redondeado,
                        'indice_original' => array_search($r_redondeado, $numeros_unicos),
                        'nuevo_indice' => count($numeros_unicos)
                    ];
                }
            }

            if (count($numeros) >= $cantidad || $iteraciones_realizadas >= $max_iteraciones) {
                break;
            }

            $x = $x_siguiente;
        }

        $total_generados = count($numeros);
        $hay_repetidos = !empty($valores_repetidos);
        $objetivo_alcanzado = $total_generados >= $cantidad;

        return [
            'numeros' => $numeros,
            'numeros_repetidos' => array_values($valores_repetidos), // Convertir a array indexado
            'total_generados' => $total_generados,
            'hay_repetidos' => $hay_repetidos,
            'objetivo_alcanzado' => $objetivo_alcanzado,
            'cantidad_solicitada' => $cantidad
        ];
    }


    public function generarReporte($resultado = null) {
        $reporte = "GENERACIÓN DE NÚMEROS PSEUDOALEATORIOS\n";
        $reporte .= "Método de Multiplicador Constante\n";
        $reporte .= "Semilla inicial: {$this->semilla}\n";
        $reporte .= "Constante: {$this->constante}\n";
        
        if (!$resultado) {
            $resultado = $this->generarNumerosAleatorios();
        }

        $reporte .= "\nResultados de Generación:\n";
        $reporte .= "Total de números generados: {$resultado['total_generados']}\n";
        $reporte .= "Hay números repetidos: " . ($resultado['hay_repetidos'] ? "Sí" : "No") . "\n\n";

        if ($resultado['hay_repetidos']) {
            $reporte .= "Detalles de Números Repetidos:\n";
            foreach ($resultado['numeros_repetidos'] as $repetido) {
                $reporte .= "Número repetido: " . $repetido['valor'] . 
                             " (índices: " . $repetido['indice_original'] . 
                             ", " . $repetido['nuevo_indice'] . ")\n";
            }
            $reporte .= "\n";
        }

        $reporte .= "Números Generados:\n";
        foreach ($resultado['numeros'] as $i => $numero) {
            $reporte .= number_format($numero, 4) . ", ";
            if (($i + 1) % 10 == 0) $reporte .= "\n";
        }

        return $reporte;
    }

    public function guardarEnArchivo($numeros, $reporte, $prefijo = '') {
        $carpeta = 'reportes/';
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $tiempo = time();
        $archivo_numeros = $carpeta . $prefijo . 'numeros_' . $tiempo . '.txt';
        $archivo_reporte = $carpeta . $prefijo . 'reporte_' . $tiempo . '.txt';

        file_put_contents($archivo_numeros, implode("\n", $numeros));
        file_put_contents($archivo_reporte, $reporte);

        return [
            'numeros' => $archivo_numeros, 
            'reporte' => $archivo_reporte
        ];
    }
}

$resultado = null;
$archivos = null;
$error = null;
$semilla_inicial = 1234;
$constante_inicial = 9991;
$digitos_iniciales = 4;
$cantidad_inicial = 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $semilla = isset($_POST['semilla']) ? intval($_POST['semilla']) : $semilla_inicial;
        $constante = isset($_POST['constante']) ? intval($_POST['constante']) : $constante_inicial;
        $digitos = isset($_POST['digitos']) ? intval($_POST['digitos']) : $digitos_iniciales;
        $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : $cantidad_inicial;

        $generador = new MultiplicadorConstante($semilla, $constante, $digitos);
        $resultado = $generador->generarNumerosAleatorios($cantidad);
        $reporte = $generador->generarReporte($resultado);
        $archivos = $generador->guardarEnArchivo($resultado['numeros'], $reporte);

        $semilla_inicial = $semilla;
        $constante_inicial = $constante;
        $digitos_iniciales = $digitos;
        $cantidad_inicial = $cantidad;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Números Pseudoaleatorios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        form label {
            display: block;
            margin-top: 10px;
        }
        form input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        form input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 15px;
        }
        form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        #resultados {
            background-color: white;
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .numeros-aleatorios {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        .fila-numeros {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .fila-numeros span {
            flex: 1;
            text-align: right;
            padding: 0 5px;
            border-right: 1px solid #dee2e6;
        }
        .fila-numeros span:last-child {
            border-right: none;
        }
        .alerta {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alerta.exito {
            background-color: #d4edda;
            color: #155724;
        }
        .alerta.advertencia {
            background-color: #fff3cd;
            color: #856404;
        }
        .archivos-generados {
            margin-top: 15px;
        }
        .tabla-repetidos {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .tabla-repetidos th, .tabla-repetidos td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Generador de Números Pseudoaleatorios</h1>
    
    <?php if ($error): ?>
        <div class="alerta advertencia">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Semilla (X0):</label>
        <input type="number" name="semilla" required value="<?php echo $semilla_inicial; ?>">
        
        <label>Constante (a):</label>
        <input type="number" name="constante" required value="<?php echo $constante_inicial; ?>">
        
        <label>Número de Dígitos:</label>
        <input type="number" name="digitos" required value="<?php echo $digitos_iniciales; ?>" min="4">
        
        <label>Cantidad de Números:</label>
        <input type="number" name="cantidad" required value="<?php echo $cantidad_inicial; ?>" min="1">
        
        <input type="submit" value="Generar y Descargar">
    </form>

    <?php if ($resultado): ?>
    <div id="resultados">
        <h2>Resultados Generados</h2>
        
        <div class="alerta <?php 
            if ($resultado['objetivo_alcanzado'] && !$resultado['hay_repetidos']) {
                echo 'exito';
            } else {
                echo 'advertencia';
            }
        ?>">
            <?php
            if ($resultado['objetivo_alcanzado'] && !$resultado['hay_repetidos']) {
                // Caso 1: Éxito total
                echo "<p>✅ Se generaron exitosamente {$resultado['cantidad_solicitada']} números pseudoaleatorios únicos.</p>";
            
            } elseif ($resultado['objetivo_alcanzado'] && $resultado['hay_repetidos']) {
                // Caso 2: Se generó la cantidad pero hay repetidos
                echo "<p>⚠️ Se generaron {$resultado['cantidad_solicitada']} números, pero se encontraron repeticiones:</p>";
                echo "<table class='tabla-repetidos'>
                        <thead><tr><th>Valor Repetido</th><th>Índice Original</th><th>Nuevo Índice</th></tr></thead>
                        <tbody>";
                foreach ($resultado['numeros_repetidos'] as $repetido) {
                    echo "<tr>
                            <td>{$repetido['valor']}</td>
                            <td>{$repetido['indice_original']}</td>
                            <td>{$repetido['nuevo_indice']}</td>
                          </tr>";
                }
                echo "</tbody></table>";
            
            } elseif (!$resultado['objetivo_alcanzado'] && !$resultado['hay_repetidos']) {
                // Caso 3: No se generó la cantidad pero son únicos
                echo "<p>⚠️ Solo se pudieron generar {$resultado['total_generados']} números únicos de los {$resultado['cantidad_solicitada']} solicitados.</p>";
            
            } else {
                // Caso 4: No se generó la cantidad y hay repetidos
                echo "<p>⚠️ Solo se pudieron generar {$resultado['total_generados']} números de los {$resultado['cantidad_solicitada']} solicitados, y se encontraron repeticiones:</p>";
                echo "<table class='tabla-repetidos'>
                        <thead><tr><th>Valor Repetido</th><th>Índice Original</th><th>Nuevo Índice</th></tr></thead>
                        <tbody>";
                foreach ($resultado['numeros_repetidos'] as $repetido) {
                    echo "<tr>
                            <td>{$repetido['valor']}</td>
                            <td>{$repetido['indice_original']}</td>
                            <td>{$repetido['nuevo_indice']}</td>
                          </tr>";
                }
                echo "</tbody></table>";
            }
            ?>
        </div>

        <h3>Números Aleatorios</h3>
        <div class="numeros-aleatorios">
            <?php 
            foreach (array_chunk($resultado['numeros'], 10) as $fila) {
                echo '<div class="fila-numeros">';
                foreach ($fila as $numero) {
                    echo '<span>' . number_format($numero, 4) . '</span>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <div class="archivos-generados">
            <h3>Archivos Generados</h3>
            <p>Números: <a href="<?php echo $archivos['numeros']; ?>" download>Descargar números</a></p>
            <p>Reporte: <a href="<?php echo $archivos['reporte']; ?>" download>Descargar reporte</a></p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>