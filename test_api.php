<!DOCTYPE html>
<html>
<head>
    <title>Prueba API lista.php</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { background: #e6ffe6; }
        .error { background: #ffe6e6; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Prueba de API lista.php</h1>
    
    <button onclick="testAPI()">🚀 Probar API</button>
    <button onclick="testDirectFetch()">📡 Probar Fetch Directo</button>
    <button onclick="testWithCredentials()">🔐 Probar con Credenciales</button>
    
    <div id="result"></div>
    
    <script>
        function testAPI() {
            console.log('🚀 Iniciando prueba de API...');
            document.getElementById('result').innerHTML = '<div class="result">Cargando...</div>';
            
            fetch('lista.php')
                .then(response => {
                    console.log('📡 Response status:', response.status);
                    console.log('📡 Response headers:', response.headers);
                    console.log('📡 Response type:', response.type);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.text(); // Usar text() primero para ver el contenido crudo
                })
                .then(text => {
                    console.log('📄 Response text:', text);
                    
                    // Intentar parsear como JSON
                    try {
                        const data = JSON.parse(text);
                        console.log('📊 JSON parseado:', data);
                        
                        let html = '<div class="result success">';
                        html += '<h3>✅ API funcionando correctamente</h3>';
                        html += '<p><strong>Success:</strong> ' + data.success + '</p>';
                        html += '<p><strong>Documentos:</strong> ' + (data.documentos ? data.documentos.length : 0) + '</p>';
                        html += '<p><strong>Estadísticas:</strong> ' + JSON.stringify(data.estadisticas) + '</p>';
                        html += '<h4>JSON completo:</h4>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        html += '</div>';
                        
                        document.getElementById('result').innerHTML = html;
                    } catch (e) {
                        console.log('❌ Error parseando JSON:', e);
                        document.getElementById('result').innerHTML = 
                            '<div class="result error"><h3>❌ Error parseando JSON</h3><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    console.error('🔥 Error completo:', error);
                    document.getElementById('result').innerHTML = 
                        '<div class="result error"><h3>❌ Error de conexión</h3><p>' + error.message + '</p></div>';
                });
        }
        
        function testDirectFetch() {
            console.log('📡 Probando fetch directo...');
            
            fetch('lista.php', {
                method: 'GET',
                mode: 'cors',
                cache: 'no-cache'
            })
            .then(response => response.text())
            .then(text => {
                console.log('📄 Texto crudo:', text);
                document.getElementById('result').innerHTML = 
                    '<div class="result"><h3>📄 Respuesta cruda:</h3><pre>' + text + '</pre></div>';
            })
            .catch(error => {
                console.error('🔥 Error:', error);
                document.getElementById('result').innerHTML = 
                    '<div class="result error"><h3>❌ Error:</h3><p>' + error.message + '</p></div>';
            });
        }
        
        function testWithCredentials() {
            console.log('🔐 Probando con credenciales...');
            
            fetch('lista.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('📊 Datos con credenciales:', data);
                document.getElementById('result').innerHTML = 
                    '<div class="result success"><h3>✅ Con credenciales:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
            })
            .catch(error => {
                console.error('🔥 Error con credenciales:', error);
                document.getElementById('result').innerHTML = 
                    '<div class="result error"><h3>❌ Error con credenciales:</h3><p>' + error.message + '</p></div>';
            });
        }
        
        // Auto-ejecutar al cargar
        window.onload = function() {
            console.log('📄 Página cargada, esperando acción del usuario...');
        };
    </script>
</body>
</html>
