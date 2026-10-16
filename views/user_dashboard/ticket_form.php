<div class="container py-4" style="max-width: 800px;">
    <div class="mb-4">
        <a href="/user-dashboard/tickets" class="text-decoration-none text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Volver a mis solicitudes
        </a>
        <h1 class="h3 fw-bold mt-2">Crear Nueva Solicitud de Soporte</h1>
        <p class="text-muted">Describe tu problema técnico detalladamente. Nuestro asistente de IA intentará darte una solución automática inmediata.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-4">
        <form action="/user-dashboard/tickets/create" method="POST">
            <div class="mb-3">
                <label for="titulo" class="form-label fw-semibold">¿Qué problema presentas? (Resumen corto)</label>
                <input type="text" class="form-control <?= isset($errors['titulo']) ? 'is-invalid' : '' ?>" id="titulo" name="titulo" value="<?= htmlspecialchars($old['titulo'] ?? '') ?>" placeholder="Ej: Mi computadora no enciende o no conecta a la red">
                <?php if (isset($errors['titulo'])): ?>
                    <div class="invalid-feedback"><?= $errors['titulo'] ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="categoria" class="form-label fw-semibold">Categoría del Incidente</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="hardware">Hardware (Equipos, Pantallas, Mouse)</option>
                        <option value="software">Software (Programas, Office, Windows)</option>
                        <option value="red">Red e Internet (Sin conexión, Wifi lento)</option>
                        <option value="usuario">Cuenta y Accesos (Contraseñas, Correos)</option>
                        <option value="otro">Otro problema</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prioridad" class="form-label fw-semibold">Prioridad Estimada</label>
                    <select class="form-select" id="prioridad" name="prioridad">
                        <option value="baja">Baja (No urge)</option>
                        <option value="media" selected>Media (Trabajo con dificultad)</option>
                        <option value="alta">Alta (Bloquea mi trabajo)</option>
                        <option value="critica">Crítica (Afecta a todo el área)</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="descripcion" class="form-label fw-semibold">Descripción detallada del problema</label>
                <textarea class="form-control <?= isset($errors['descripcion']) ? 'is-invalid' : '' ?>" id="descripcion" name="descripcion" rows="5" placeholder="Escribe aquí los síntomas del equipo, mensajes de error en pantalla o qué estabas haciendo cuando falló..."><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                <?php if (isset($errors['descripcion'])): ?>
                    <div class="invalid-feedback"><?= $errors['descripcion'] ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="fa-solid fa-paper-plane me-2"></i>Enviar y Consultar con la IA de Soporte
            </button>
        </form>
    </div>
</div>

<!-- 🚨 MODAL ESTÉTICO DE PROGRESO DE IA UNIFICADO Y CORREGIDO (UN SOLO BLOQUE INDEPENDIENTE) -->
<div class="modal fade" id="modalProgresoIA" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">🧠 Procesando Solicitud Híbrida</h5>
            </div>
            <div class="modal-body text-center py-4">
                <!-- Spinner Animado -->
                <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                
                <!-- Contenedor dinámico de Pasos en Orden Estricto y Numeración Reparada -->
                <div class="text-start mx-auto" style="max-width: 400px; font-size: 15px;">
                    <div id="paso0" class="mb-2 text-muted fw-bold">⏳ 1. Validando y registrando ticket en Azure SQL...</div>
                    <div id="paso1" class="mb-2 text-muted-custom" style="color: #6c757d; font-weight: bold; display:none;">⏳ 2. Buscando similitudes en Base de Conocimientos...</div>
                    <div id="paso2" class="mb-2 text-muted-custom" style="color: #6c757d; font-weight: bold; display:none;">⏳ 3. Consultando con Nodo de IA Local Ollama...</div>
                    <div id="paso3" class="mb-0 text-muted-custom" style="color: #6c757d; font-weight: bold; display:none;">⏳ 4. Escalando requerimiento a Nodo de IA en la Nube (Groq)...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🚀 SCRIPT CON SINCRONIZACIÓN DE TEXTOS Y COINCIDENCIA DE PARÁMETROS NATIVOS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form");
    if (!form) return;

    form.addEventListener("submit", function(e) {
        e.preventDefault(); // Frenamos la recarga nativa

        // Empaquetamos los datos del formulario de forma correcta en formato URL-Encoded
        const formData = new FormData(form);
        const searchParams = new URLSearchParams();
        
        for (const pair of formData) {
            searchParams.append(pair[0], pair[1]); // 🚀 Captura exacta por índices de tu formulario
        }

        // Enviamos la petición asíncrona simulando el comportamiento POST nativo
        fetch("/user-dashboard/tickets/create", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: searchParams.toString()
        })
        .then(response => response.json())
        .then(data => {
            // 🚨 ADMISIÓN PREVIA ESTRICTA: Validamos antes de abrir el cuadro de IA
            if (data && data.success) {
                
                // 🔔 PRIMERO: Alerta instantánea en el navegador confirmando la base de datos
                alert("🎉 ¡Solicitud creada exitosamente!");

                // SEGUNDO: Una vez el usuario le da "Aceptar", abrimos el cuadro de la cascada
                const modalElement = new bootstrap.Modal(document.getElementById('modalProgresoIA'));
                modalElement.show();

                const p0 = document.getElementById("paso0");
                const p1 = document.getElementById("paso1");
                const p2 = document.getElementById("paso2");
                const p3 = document.getElementById("paso3");

                // Se pinta el aviso de éxito en el paso 1 dentro del modal
                p0.innerHTML = "✅ 1. ¡Solicitud creada exitosamente en Azure SQL Database!";
                p0.className = "mb-2 text-success fw-bold";
                
                // Iniciamos la simulación progresiva del motor de IA en cascada
                setTimeout(() => {
                    p1.style.display = "block";
                    
                    if (data.ia_status === 'resuelto_local') {
                        p1.innerHTML = "✅ 2. ¡Solución encontrada en Base de Conocimientos Local!";
                        p1.className = "mb-2 text-success fw-bold";
                        finalizarRegistro();
                    } else {
                        p1.innerHTML = "❌ 2. Solución no encontrada en Base de Conocimientos.";
                        p1.className = "mb-2 text-danger fw-bold";
                        
                        setTimeout(() => {
                            p2.style.display = "block";
                            
                            if (data.ia_status === 'analizado_local') {
                                p2.innerHTML = "✅ 3. ¡Solución generada por IA Local Ollama con éxito!";
                                p2.className = "mb-2 text-success fw-bold";
                                finalizarRegistro();
                            } else {
                                p2.innerHTML = "❌ 3. Solución no encontrada por IA Local (Nodo apagado/inactivo).";
                                p2.className = "mb-2 text-danger fw-bold";
                                
                                setTimeout(() => {
                                    p3.style.display = "block";
                                    
                                    if (data.ia_status === 'analizado_nube') {
                                        p3.innerHTML = "☁️ 4. ¡Enlace exitoso: Solución diagnosticada por IA en la Nube (Groq)!";
                                        p3.className = "mb-0 text-info fw-bold";
                                    }
                                    finalizarRegistro();
                                }, 1500);
                            }
                        }, 1500);
                    }
                }, 1500);
                        } else {
                // 🔔 Alerta instantánea en caso de fallo físico de base de datos
                alert("❌ No se pudo crear su solicitud. Intente nuevamente.");
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            alert("❌ No se pudo crear su solicitud. Error de red con el servidor.");
        });

        function finalizarRegistro() {
            // Mantenemos visible el modal 3.5 segundos para alcanzar a notar los checks antes de redirigir
            setTimeout(() => {
                window.location.href = "/user-dashboard/tickets";
            }, 3500);
        }
    });
});
</script>
