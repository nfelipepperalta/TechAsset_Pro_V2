<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Mis Solicitudes de Soporte</h1>
            <p class="text-muted mb-0">Bienvenido, <?= htmlspecialchars($user['nombre'] ?? '') ?>. Aquí puedes ver el estado de tus tickets.</p>
        </div>
        <a href="/user-dashboard/tickets/create" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Nueva Solicitud
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Diagnóstico preliminar IA</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No has creado ninguna solicitud todavía.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="fw-bold">#<?= $ticket['id'] ?></td>
                                <td><?= htmlspecialchars($ticket['titulo']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($ticket['categoria']) ?></span></td>
                                <td>
                                    <span class="badge <?= $ticket['prioridad'] === 'critica' || $ticket['prioridad'] === 'alta' ? 'bg-danger' : 'bg-info' ?>">
                                        <?= htmlspecialchars($ticket['prioridad']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $ticket['estado'] === 'abierto' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                        <?= htmlspecialchars($ticket['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <em class="text-sm text-body-secondary">
                                        <?php if ($ticket['ia_sugerencia']): ?>
                                            <?php 
                                                // 🚀 CORRECCIÓN VISUAL EN CALIENTE: Limpiamos etiquetas HTML y el letrero duplicado
                                                $textoLimpio = strip_tags((string)$ticket['ia_sugerencia']);
                                                $textoLimpio = str_replace('DIAGNÓSTICO INICIAL AUTOMÁTICO:', '', $textoLimpio);
                                                echo htmlspecialchars(trim($textoLimpio));
                                            ?>
                                        <?php else: ?>
                                            ⏳ Analizando problema...
                                        <?php endif; ?>
                                    </em>
                                </td>
                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                <td>
                                    <button type="button" 
                                    class="btn btn-sm btn-primary fw-bold" 
                                    onclick="abrirChatIA(<?= $ticket['id']; ?>, '<?= htmlspecialchars($ticket['titulo']); ?>', '<?= htmlspecialchars($ticket['descripcion'] ?? ''); ?>', '<?= htmlspecialchars($ticket['ia_sugerencia']); ?>', '<?= htmlspecialchars($ticket['ia_status']); ?>')">
                                    💬 Responder / Ver Chat
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 🚨 MODAL ESTÉTICO DE CHAT INTERACTIVO CON IA -->
<div class="modal fade" id="modalChatIA" tabindex="-1" aria-labelledby="modalChatIALabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:15px; box-shadow: 0 8px 30px rgba(0,0,0,0.3);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold" id="modalChatIALabel">💬 Asistente Técnico Guiado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info py-2" id="chatInfoStatus">Cargando contexto del incidente...</div>
                
                <!-- Caja de Historial del Chat -->
                <div id="historialChat" class="p-3 bg-white mb-3" style="height: 350px; overflow-y: auto; border-radius: 10px; border: 1px solid #dee2e6; display: flex; flex-direction: column; gap: 12px;">
                    <!-- Los mensajes dinámicos se inyectarán aquí -->
                </div>

                <!-- Botones de Feedback Rápido (Si no funcionó) -->
                <div id="zonaFeedback" class="mb-3 text-center" style="display: none;">
                    <span class="text-muted small d-block mb-2 fw-bold">¿Te sirvieron estas dos recomendaciones?</span>
                    <button type="button" class="btn btn-sm btn-success me-2 px-3" onclick="enviarRespuestaIA('¡Excelente! Las soluciones funcionaron perfectamente.')">👍 Sí, funcionó</button>
                    <button type="button" class="btn btn-sm btn-danger px-3" onclick="marcarSolucionNoSirvio()">👎 No sirvió, dar más soluciones</button>
                </div>
            </div>
            <div class="modal-footer bg-white" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                <!-- Barra de entrada con Micrófono -->
                <div class="input-group w-100">
                    <button class="btn btn-outline-secondary" type="button" id="btnMicrofono" title="Dictar por voz" onclick="activarMicrofono()">
                        🎙️
                    </button>
                    <input type="text" id="inputMensajeUsuario" class="form-control" placeholder="Escribe un mensaje o usa el micrófono...">
                    <button class="btn btn-primary fw-bold px-4" type="button" onclick="procesarMensajeManual()">Enviar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🚀 SCRIPT ASÍNCRONO DE INTERACCIÓN, ASISTENCIA Y DICTADO POR VOZ -->
<script>
let currentTicketId = null;
let currentIaStatus = '';
let chatModal = null;

// Inicializador del chat
// Inicializador del chat con reconstrucción automática del hilo histórico

// Inicializador del chat con reconstrucción automática del hilo histórico
function abrirChatIA(ticketId, titulo, descripcion, iaSugerencia, iaStatus) {
    currentTicketId = ticketId;
    currentIaStatus = iaStatus;

    if(!chatModal) {
        chatModal = new bootstrap.Modal(document.getElementById('modalChatIA'));
    }
    chatModal.show();

    const historial = document.getElementById("historialChat");
    const info = document.getElementById("chatInfoStatus");
    const zonaFeedback = document.getElementById("zonaFeedback");
    
    historial.innerHTML = "";
    zonaFeedback.style.display = "none"; // Reset por defecto

    // Restablecemos el estado por defecto de los inputs de entrada
    document.getElementById("inputMensajeUsuario").disabled = false;
    document.getElementById("inputMensajeUsuario").placeholder = "Escribe un mensaje o usa el micrófono...";
    document.getElementById("btnMicrofono").disabled = false;
    document.querySelector("#modalChatIA .modal-footer button.btn-primary").disabled = false;

    // RECONSTRUCCIÓN DEL HILO HISTÓRICO ORIGINAL
    inyectarMensaje('usuario', `<b>REQUERIMIENTO INICIAL:</b><br><b>Título:</b> ${titulo}<br><b>Detalle:</b> ${descripcion}`);

    let sugerenciaLimpia = iaSugerencia.replace(/\[.*?\]\s*/g, '');
    inyectarMensaje('ia_nube', `<b>DIAGNÓSTICO INICIAL AUTOMÁTICO:</b><br>${sugerenciaLimpia}`);

    // 🎛️ ADAPTACIÓN DE BANNERS Y BOTONES ACTIVOS EN TODAS LAS FASES AUTOMÁTICAS
    if (iaStatus === 'analizado_nube') {
        info.innerHTML = `🌟 Hilo conectado con <b>IA en la Nube (Groq)</b> para el caso: <i>"${titulo}"</i>`;
        info.className = "alert alert-success py-2";
        zonaFeedback.style.display = "block"; // 👍 Habilitado para la nube
    } else if (iaStatus === 'analizado_local') {
        info.innerHTML = `🤖 Hilo transferido a <b>IA Local Ollama</b> (Análisis en tiempo real)`;
        info.className = "alert alert-warning py-2";
        zonaFeedback.style.display = "block"; // 👍 Habilitado para la IA Local
    } else if (iaStatus === 'resuelto_local') {
        info.innerHTML = `🔍 Caso resuelto por <b>Autoentrenamiento de Base de Conocimientos</b>`;
        info.className = "alert alert-info py-2";
        zonaFeedback.style.display = "block"; // 👍 Habilitado para la Base de Conocimientos
    } else if (iaStatus === 'escalado_humano') {
        info.innerHTML = `🚨 <b>SOPORTE HUMANO ACTIVADO</b> - Caso derivado a un ingeniero TI`;
        info.className = "alert alert-danger py-2 fw-bold";
        zonaFeedback.style.display = "none"; // ❌ Ocultado para soporte humano
        
        // Bloqueo estricto del chat
        document.getElementById("inputMensajeUsuario").disabled = true;
        document.getElementById("inputMensajeUsuario").placeholder = "Chat cerrado. El soporte técnico humano ha tomado el control.";
        document.getElementById("btnMicrofono").disabled = true;
        document.querySelector("#modalChatIA .modal-footer button.btn-primary").disabled = true;
    }

    // CAPTURA DE TECLA ENTER
    setTimeout(() => {
        const inputTexto = document.getElementById("inputMensajeUsuario");
        if (inputTexto && !inputTexto.dataset.listenerActive) {
            inputTexto.dataset.listenerActive = "true";
            inputTexto.addEventListener("keydown", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    procesarMensajeManual();
                }
            });
        }
    }, 300);
}



// Inyectar globos estéticos estilo chat de WhatsApp/Teams
function inyectarMensaje(remitente, texto) {
    const historial = document.getElementById("historialChat");
    const msgDiv = document.createElement("div");
    
    if(remitente === 'usuario') {
        msgDiv.style.alignSelf = "flex-end";
        msgDiv.style.background = "#dcf8c6";
        msgDiv.style.color = "#000";
        msgDiv.style.padding = "10px 14px";
        msgDiv.style.borderRadius = "12px 12px 0 12px";
        msgDiv.style.maxWidth = "75%";
        msgDiv.innerHTML = `<b>Tú:</b> <br>${texto}`;
    } else {
        msgDiv.style.alignSelf = "flex-start";
        msgDiv.style.background = "#f1f0f0";
        msgDiv.style.color = "#000";
        msgDiv.style.padding = "10px 14px";
        msgDiv.style.borderRadius = "12px 12px 12px 0";
        msgDiv.style.maxWidth = "75%";
        msgDiv.innerHTML = `<b>Asistente IA:</b> <br>${texto}`;
    }
    historial.appendChild(msgDiv);
    historial.scrollTop = historial.scrollHeight; // Auto-scroll hacia abajo
}

// Procesar el botón "No sirvió" de forma guiada
function marcarSolucionNoSirvio() {
    inyectarMensaje('usuario', "No me sirvió, por favor dame otras dos soluciones paso a paso.");
    // Prompt instructivo estricto para forzar a Groq a iterar soluciones no técnicas de a dos en dos
    const instruccionForzada = "El usuario indica que las soluciones anteriores no sirvieron. Como ingeniero de soporte experto, entrégame estrictamente OTRAS DOS nuevas soluciones diferentes, paso a paso, muy detalladas y explicadas de forma sencilla para un usuario normal no técnico. No uses viñetas markdown complejas.";
    consultarNubeGroq(instruccionForzada);
}

function procesarMensajeManual() {
    const input = document.getElementById("inputMensajeUsuario");
    const texto = input.value.trim();
    if(texto === "") return;

    inyectarMensaje('usuario', texto);
    input.value = "";
    consultarNubeGroq(texto);
}

// Envío simplificado directo para botones rápidos
f// Envío simplificado asíncrono para el botón rápido "👍 Sí, funcionó"
function enviarRespuestaIA(textoLog) {
    // Inyectamos el mensaje del usuario en la pantalla de forma visual
    inyectarMensaje('usuario', textoLog);

    const historial = document.getElementById("historialChat");
    const globosMensajes = historial.getElementsByTagName("div");
    let ultimaSolucionIA = "";

    // 🚀 EXTRACCIÓN BLINDADA: Buscamos el último mensaje que NO sea del usuario (es decir, el de la IA)
    for (let i = globosMensajes.length - 1; i >= 0; i--) {
        // Si el globo NO tiene el color de fondo del usuario (#dcf8c6), obligatoriamente es de la IA
        if (globosMensajes[i].style.background !== "rgb(220, 248, 198)" && globosMensajes[i].style.background !== "#dcf8c6") {
            // Extraemos el texto completo y removemos el encabezado "Asistente IA:" de forma segura
            let textoCrudo = globosMensajes[i].innerHTML;
            ultimaSolucionIA = textoCrudo.replace(/<b>Asistente IA:<\/b>\s*<br>/g, '').replace(/<b>Asistente IA:<\/b>/g, '').trim();
            break;
        }
    }

    // Si por alguna razón sigue vacía, usamos un texto de respaldo basado en el diagnóstico inicial para que nunca falle
    if (ultimaSolucionIA === "") {
        ultimaSolucionIA = "Solución técnica recomendada y aprobada por el usuario para este tipo de incidente.";
    }

    // Empaquetamos los parámetros en formato URL-Encoded
    const params = new URLSearchParams();
    params.append('ticket_id', currentTicketId);
    params.append('solucion', ultimaSolucionIA);

    // Disparamos el Fetch asíncrono directo al controlador tolerante de Azure
    fetch("/user-dashboard/tickets/approve-solution", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            inyectarMensaje('ia_nube', "<b>¡Excelente!</b> Me alegra mucho que hayamos resuelto el inconveniente. Tu confirmación ha quedado guardada con éxito y el sistema se ha autoentrenado para el futuro. 🧠✨");
            
            // Ocultamos la zona de feedback para evitar dobles clics
            document.getElementById("zonaFeedback").style.display = "none";
            
            // Recargamos el listado automáticamente después de 2.5 segundos para refrescar los estados en tu tabla
            setTimeout(() => {
                window.location.reload();
            }, 2500);
        } else {
            alert("El orquestador no pudo guardar la solución: " + (data.message || "Error desconocido."));
        }
    })
    .catch(err => {
        console.error("Error al guardar solución aprobada:", err);
        alert("Fallo de red: No se pudo registrar la retroalimentación en tu base de datos de Azure.");
    });
}


// Conexión Fetch asíncrona dedicada hacia Groq usando tu endpoint validado
// Conexión Fetch dedicada hacia nuestro Orquestador Centralizado en Azure
function consultarOrquestadorIA(mensajeUsuario) {
    inyectarMensaje('ia_nube', "<i>Analizando su solicitud en los nodos de soporte... ⏳</i>");
    
    // Empaquetamos los parámetros requeridos en formato URL-Encoded para compatibilidad nativa con PHP
    const params = new URLSearchParams();
    params.append('ticket_id', currentTicketId);
    params.append('mensaje', mensajeUsuario);

    fetch("/user-dashboard/tickets/chat-interact", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
    })
    .then(res => {
        if (!res.ok) {
            throw new Error("HTTP error " + res.status);
        }
        return res.json();
    })
    .then(data => {
        // Removemos el globo de "Analizando su solicitud..." de la última posición del chat
        const historial = document.getElementById("historialChat");
        if (historial.lastChild) {
            historial.removeChild(historial.lastChild);
        }

        if (data && data.success) {
            // Inyectamos la respuesta combinada que generó el cerebro de la cascada
            inyectarMensaje('ia_nube', data.respuesta);
            
            // Actualizamos dinámicamente las variables de contexto de la interfaz
            currentIaStatus = data.nuevo_status;
            const info = document.getElementById("chatInfoStatus");
            const zonaFeedback = document.getElementById("zonaFeedback");

            // Adaptamos los banners informativos y mantenemos el feedback activo en flujos automáticos
            if (currentIaStatus === 'analizado_local') {
                info.innerHTML = `🤖 Hilo transferido a <b>IA Local Ollama</b> (Análisis en tiempo real)`;
                info.className = "alert alert-warning py-2";
                zonaFeedback.style.display = "block"; // 🚀 REPARADO: Habilitado para dar feedback también en Ollama
            } else if (currentIaStatus === 'analizado_nube') {
                info.innerHTML = `🌟 Hilo escalado a <b>IA en la Nube (Groq)</b> - Soluciones paso a paso`;
                info.className = "alert alert-success py-2";
                zonaFeedback.style.display = "block"; // 🚀 Se mantiene activo al escalar a la Nube
            } else if (currentIaStatus === 'resuelto_local') {
                info.innerHTML = `🔍 Caso resuelto por <b>Autoentrenamiento de Base de Conocimientos</b>`;
                info.className = "alert alert-info py-2";
                zonaFeedback.style.display = "block"; // 🚀 Se mantiene activo en Base de Conocimientos
            } else if (currentIaStatus === 'escalado_humano') {
                info.innerHTML = `🚨 <b>SOPORTE HUMANO ACTIVADO</b> - Caso derivado a un ingeniero TI`;
                info.className = "alert alert-danger py-2 fw-bold animate__animated animate__pulse";
                zonaFeedback.style.display = "none"; // ❌ Se oculta estrictamente al llegar al soporte humano
                
                // Bloqueamos las entradas de texto del chat para proteger el flujo cerrado
                document.getElementById("inputMensajeUsuario").disabled = true;
                document.getElementById("inputMensajeUsuario").placeholder = "Chat cerrado. El soporte técnico humano ha tomado el control.";
                document.getElementById("btnMicrofono").disabled = true;
                document.querySelector("#modalChatIA .modal-footer button.btn-primary").disabled = true;
            }
        } else {
            inyectarMensaje('ia_nube', "Lo siento, el orquestador central reportó un inconveniente: " + (data.message || "Error desconocido."));
        }
    })
    .catch(err => {
        console.error("Chat Error:", err);
        const historial = document.getElementById("historialChat");
        if (historial.lastChild) {
            historial.removeChild(historial.lastChild);
        }
        inyectarMensaje('ia_nube', "❌ Error de red: No se pudo establecer comunicación con el servidor central de Azure.");
    });
}


// 🚀 REPARACIÓN DE LLAMADAS INTERNAS: Vinculamos las entradas manuales y los botones al nuevo orquestador
function marcarSolucionNoSirvio() {
    inyectarMensaje('usuario', "No me sirvió, por favor dame otra alternativa.");
    consultarOrquestadorIA("No me sirvió");
}

function procesarMensajeManual() {
    const input = document.getElementById("inputMensajeUsuario");
    const texto = input.value.trim();
    if (texto === "") return;

    inyectarMensaje('usuario', texto);
    input.value = "";
    consultarOrquestadorIA(texto);
}


// 🎙️ API DE DICTADO POR VOZ NATIVA (SPEECH-TO-TEXT) 100% GRATUITA Y LIBRE
function activarMicrofono() {
    const btn = document.getElementById("btnMicrofono");
    const input = document.getElementById("inputMensajeUsuario");

    // Validamos si el navegador del usuario soporta reconocimiento de voz nativo
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert("Tu navegador web no soporta el reconocimiento de voz nativo. Te recomiendo usar Google Chrome o Microsoft Edge.");
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'es-ES'; // Configuramos el idioma en español nativo
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    btn.innerHTML = "🔴 Escuchando...";
    btn.className = "btn btn-danger text-white";
    recognition.start();

    // Evento gatillado en cuanto el usuario deja de hablar y el navegador procesa la onda de sonido
    recognition.onresult = function(event) {
        const textoDictado = event.results[0][0].transcript;
        input.value = textoDictado; // Inyectamos el texto de voz directamente en el input de texto
    };

    recognition.onspeechend = function() {
        recognition.stop();
        restaurarBotonMicro();
    };

    recognition.onerror = function(event) {
        console.error("Speech error: ", event.error);
        recognition.stop();
        restaurarBotonMicro();
    };

    function restaurarBotonMicro() {
        btn.innerHTML = "🎙️";
        btn.className = "btn btn-outline-secondary";
    }
}
</script>

