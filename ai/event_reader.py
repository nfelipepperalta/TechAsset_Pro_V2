#!/usr/bin/env python3
# ============================================================
#  TECHASSET PRO — Lector de Eventos con Análisis IA
#  Archivo: /var/www/TechAsset/ai/event_reader.py
#
#  Soporta:
#    - Windows: Event Log remoto via impacket (WMI/RPC)
#    - Linux: Eventos via SSH + journalctl/syslog
#
#  Uso:
#    python3 event_reader.py --ip 192.168.0.101 --os windows --user admin --pass Password
#    python3 event_reader.py --ip 192.168.0.50  --os linux   --user root  --pass Password
#    python3 event_reader.py --ip 192.168.0.50  --os linux   --user admin --key /path/to/key
# ============================================================

import sys
import json
import argparse
import warnings
from datetime import datetime

warnings.filterwarnings('ignore')

# ============================================================
# BASE DE CONOCIMIENTO DE EVENTOS — IA
# ============================================================

CONOCIMIENTO_EVENTOS = {
    # Windows Event IDs
    "win_1074":  {"nivel": "medio",   "titulo": "Reinicio/apagado del sistema",
                  "causa": "El sistema fue reiniciado o apagado por un usuario o proceso.",
                  "solucion": "Verificar si el reinicio fue programado o inesperado. Revisar logs de aplicaciones para identificar la causa si fue inesperado."},
    "win_6008":  {"nivel": "critico", "titulo": "Apagado inesperado del sistema",
                  "causa": "El sistema anterior no se apagó correctamente — posible fallo de energía, kernel panic o BSOD.",
                  "solucion": "1) Verificar estabilidad de la fuente de poder. 2) Revisar temperatura del sistema. 3) Ejecutar 'sfc /scannow' para verificar archivos del sistema. 4) Revisar dump de memoria en C:\\Windows\\Minidump."},
    "win_41":    {"nivel": "critico", "titulo": "Kernel Power — reinicio sin apagado limpio",
                  "causa": "El sistema se reinició sin completar el proceso de apagado. Posible fallo de hardware o BSOD.",
                  "solucion": "1) Verificar fuente de poder y conexiones. 2) Revisar temperatura CPU/GPU. 3) Ejecutar diagnóstico de memoria RAM (mdsched.exe). 4) Actualizar drivers de chipset y tarjeta de video."},
    "win_4625":  {"nivel": "critico", "titulo": "Inicio de sesión fallido",
                  "causa": "Intento de autenticación fallido. Puede indicar ataque de fuerza bruta.",
                  "solucion": "1) Verificar si hay múltiples intentos desde la misma IP (posible ataque). 2) Implementar bloqueo de cuenta después de N intentos. 3) Revisar política de contraseñas. 4) Considerar implementar MFA."},
    "win_4648":  {"nivel": "medio",   "titulo": "Inicio de sesión con credenciales explícitas",
                  "causa": "Un proceso intentó iniciar sesión usando credenciales explícitas.",
                  "solucion": "Verificar si el proceso que realizó el login es legítimo. Puede indicar movimiento lateral en un ataque."},
    "win_4720":  {"nivel": "medio",   "titulo": "Cuenta de usuario creada",
                  "causa": "Se creó una nueva cuenta de usuario en el sistema.",
                  "solucion": "Verificar que la creación fue autorizada. Si es inesperada, podría indicar compromiso del sistema."},
    "win_4726":  {"nivel": "medio",   "titulo": "Cuenta de usuario eliminada",
                  "causa": "Una cuenta de usuario fue eliminada.",
                  "solucion": "Verificar que la eliminación fue autorizada por un administrador."},
    "win_7034":  {"nivel": "critico", "titulo": "Servicio terminó inesperadamente",
                  "causa": "Un servicio del sistema terminó de forma inesperada.",
                  "solucion": "1) Identificar qué servicio falló. 2) Revisar logs específicos del servicio. 3) Verificar dependencias del servicio. 4) Reiniciar el servicio y configurar recuperación automática."},
    "win_7036":  {"nivel": "medio",   "titulo": "Cambio de estado de servicio",
                  "causa": "Un servicio cambió su estado (iniciado/detenido).",
                  "solucion": "Verificar si el cambio fue intencional. Si un servicio crítico se detuvo inesperadamente, investigar la causa."},
    "win_7045":  {"nivel": "critico", "titulo": "Nuevo servicio instalado",
                  "causa": "Se instaló un nuevo servicio en el sistema.",
                  "solucion": "Verificar que el servicio es legítimo. Los malwares frecuentemente instalan servicios para persistencia. Revisar el ejecutable del servicio con antivirus."},
    "win_1000":  {"nivel": "medio",   "titulo": "Error de aplicación",
                  "causa": "Una aplicación terminó con error.",
                  "solucion": "1) Identificar la aplicación que falló. 2) Actualizar la aplicación a la última versión. 3) Reinstalar si el error persiste. 4) Revisar compatibilidad con el SO."},
    "win_1001":  {"nivel": "medio",   "titulo": "Reporte de errores de Windows (Dr. Watson)",
                  "causa": "Windows generó un reporte de fallo de aplicación o sistema.",
                  "solucion": "Revisar el reporte en %LOCALAPPDATA%\\CrashDumps. Actualizar la aplicación o driver que causó el fallo."},
    "win_10016": {"nivel": "medio",   "titulo": "Error de permisos DCOM",
                  "causa": "Un componente no tiene los permisos necesarios para iniciar un servidor DCOM.",
                  "solucion": "1) Abrir dcomcnfg. 2) Localizar el componente con error. 3) Configurar permisos de inicio y activación. Generalmente no crítico pero puede afectar funcionalidad."},
    "win_1102":  {"nivel": "critico", "titulo": "Log de auditoría borrado",
                  "causa": "El log de eventos de seguridad fue borrado.",
                  "solucion": "ALERTA DE SEGURIDAD: El borrado de logs puede indicar actividad maliciosa para ocultar huellas. Investigar quién realizó esta acción e iniciar protocolo de respuesta a incidentes."},
    "win_4698":  {"nivel": "medio",   "titulo": "Tarea programada creada",
                  "causa": "Se creó una nueva tarea en el Programador de tareas.",
                  "solucion": "Verificar que la tarea es legítima. Las tareas programadas son un método común de persistencia de malware."},
    "win_5145":  {"nivel": "medio",   "titulo": "Acceso a recurso compartido de red",
                  "causa": "Un cliente accedió a un recurso compartido de red.",
                  "solucion": "Si el acceso es inusual o desde IPs no autorizadas, podría indicar reconocimiento de red o exfiltración de datos."},

    # Linux/Syslog eventos
    "linux_oom": {"nivel": "critico", "titulo": "OOM Killer activado — proceso terminado por falta de memoria",
                  "causa": "El sistema se quedó sin memoria RAM disponible y el kernel terminó procesos para recuperarla.",
                  "solucion": "1) Agregar más RAM al servidor. 2) Optimizar el proceso que consume más memoria. 3) Configurar swap adecuado. 4) Revisar memory leaks en aplicaciones."},
    "linux_disk_full": {"nivel": "critico", "titulo": "Disco lleno",
                  "causa": "El sistema de archivos está al 100% de capacidad.",
                  "solucion": "1) Identificar archivos grandes: 'du -sh /* | sort -rh | head'. 2) Limpiar logs antiguos: 'journalctl --vacuum-size=1G'. 3) Ampliar el volumen o agregar disco. 4) Configurar rotación de logs."},
    "linux_ssh_fail": {"nivel": "critico", "titulo": "Múltiples fallos de autenticación SSH",
                  "causa": "Intentos repetidos de login SSH fallidos — posible ataque de fuerza bruta.",
                  "solucion": "1) Instalar fail2ban: 'apt install fail2ban'. 2) Cambiar puerto SSH por defecto. 3) Deshabilitar login de root por SSH. 4) Usar autenticación por clave pública únicamente."},
    "linux_kernel_panic": {"nivel": "critico", "titulo": "Kernel Panic detectado",
                  "causa": "Error crítico del kernel que causó un fallo del sistema.",
                  "solucion": "1) Revisar dmesg para identificar el módulo que causó el panic. 2) Actualizar kernel: 'apt upgrade linux-image'. 3) Verificar hardware defectuoso. 4) Revisar logs de hardware."},
    "linux_segfault": {"nivel": "medio", "titulo": "Segmentation Fault en proceso",
                  "causa": "Un proceso intentó acceder a memoria que no le pertenece.",
                  "solucion": "1) Identificar el proceso afectado. 2) Actualizar la aplicación. 3) Verificar integridad de archivos del sistema: 'debsums'. 4) Podría indicar exploit en curso."},
    "linux_sudo": {"nivel": "medio", "titulo": "Uso de sudo — escalada de privilegios",
                  "causa": "Un usuario ejecutó comandos con privilegios de root mediante sudo.",
                  "solucion": "Verificar que el uso de sudo fue autorizado. Revisar el comando ejecutado. Auditar la política de sudoers regularmente."},
    "linux_cron": {"nivel": "bajo",  "titulo": "Ejecución de tarea cron",
                  "causa": "Una tarea programada fue ejecutada por el sistema.",
                  "solucion": "Si la tarea no es conocida, verificar /etc/crontab y /etc/cron.d/ para tareas no autorizadas."},
    "linux_service_fail": {"nivel": "critico", "titulo": "Servicio del sistema falló",
                  "causa": "Un servicio systemd terminó con error.",
                  "solucion": "1) Ver detalles: 'journalctl -u NOMBRE_SERVICIO'. 2) Reiniciar: 'systemctl restart NOMBRE'. 3) Verificar configuración del servicio. 4) Revisar dependencias."},
}

def nivel_color(nivel):
    return {"critico": "danger", "medio": "warning", "bajo": "success", "info": "info"}.get(nivel, "secondary")

# ============================================================
# LECTOR DE EVENTOS WINDOWS via impacket
# ============================================================

def leer_eventos_windows(ip: str, username: str, password: str, domain: str = "", max_eventos: int = 50) -> list:
    """Lee eventos del Event Log de Windows remotamente via impacket"""
    try:
        from impacket.smbconnection import SMBConnection
        from impacket.dcerpc.v5 import transport, even6, ndr
        from impacket.dcerpc.v5.dtypes import NULL
    except ImportError:
        return [{"error": "impacket no disponible"}]

    eventos = []

    try:
        # Conexión SMB
        smb = SMBConnection(ip, ip, timeout=10)
        smb.login(username, password, domain)

        # Conexión al Event Log via RPC
        rpctransport = transport.SMBTransport(ip, 445, r'\eventlog', smb_connection=smb)
        dce = rpctransport.get_dce_rpc()
        dce.connect()
        dce.bind(even6.MSRPC_UUID_EVEN6)

        # Canales a consultar
        canales = ['System', 'Security', 'Application']

        for canal in canales:
            try:
                request = even6.EvtRpcRegisterLogQuery()
                request['Path'] = canal + '\x00'
                request['Query'] = '*[System[(Level=1 or Level=2 or Level=3)]]\x00'
                request['Flags'] = even6.EvtQueryChannelPath | even6.EvtQueryReverseDirection

                resp = dce.request(request)
                log_handle = resp['Handle']

                # Leer eventos
                read_req = even6.EvtRpcQueryNext()
                read_req['LogQuery']        = log_handle
                read_req['NumRequestedRecords'] = min(20, max_eventos // len(canales))
                read_req['TimeOutEnd']      = 1000
                read_req['Flags']           = 0

                try:
                    read_resp = dce.request(read_req)
                    for event_xml in read_resp.get('ResultBuffer', []):
                        eventos.append({
                            "canal":   canal,
                            "raw":     str(event_xml),
                            "os_type": "windows"
                        })
                except Exception:
                    pass

                # Cerrar handle
                close_req = even6.EvtRpcClose()
                close_req['Handle'] = log_handle
                try:
                    dce.request(close_req)
                except Exception:
                    pass

            except Exception:
                continue

        smb.logoff()

    except Exception as e:
        return [{"error": f"No se pudo conectar al Event Log de Windows: {str(e)}. Verificar que el servicio 'Remote Registry' y 'Windows Event Log' estén activos y que el firewall permita el puerto 445."}]

    return eventos

def leer_eventos_windows_wmi(ip: str, username: str, password: str, domain: str = "") -> list:
    """Alternativa: leer eventos via WMI"""
    try:
        from impacket.dcerpc.v5.dcomrt import DCOMConnection
        from impacket.dcerpc.v5.dcom import wmi
        from impacket.dcerpc.v5.dtypes import NULL
    except ImportError:
        return []

    eventos = []
    try:
        dcom = DCOMConnection(ip, username, password, domain, oxidResolver=True, doKerberos=False)
        iInterface = dcom.CoCreateInstanceEx(wmi.CLSID_WbemLevel1Login, wmi.IID_IWbemLevel1Login)
        iWbemLevel1Login = wmi.IWbemLevel1Login(iInterface)
        iWbemServices = iWbemLevel1Login.NTLMLogin('//./root/cimv2', NULL, NULL)
        iWbemLevel1Login.RemRelease()
        query = "SELECT * FROM Win32_NTLogEvent WHERE Type = 'error' OR Type = 'warning'"
        iEnumWbemClassObject = iWbemServices.ExecQuery(query.strip())

        while True:
            try:
                pEnum = iEnumWbemClassObject.Next(0xffffffff, 1)[0]
                record = pEnum.getProperties()
                eventos.append({
                    "canal":       str(record.get('Logfile', {}).get('value', '')),
                    "event_id":    str(record.get('EventCode', {}).get('value', '')),
                    "nivel":       {'error': 'critico', 'warning': 'medio'}.get(str(record.get('Type', {}).get('value', '')).lower(), 'info'),
                    "fuente":      str(record.get('SourceName', {}).get('value', '')),
                    "mensaje":     str(record.get('Message', {}).get('value', ''))[:300],
                    "fecha":       str(record.get('TimeGenerated', {}).get('value', '')),
                    "os_type":     "windows",
                })
                if len(eventos) >= 50:
                    break
            except Exception:
                break

        dcom.disconnect()

    except Exception as e:
        eventos.append({"error": str(e)})

    return eventos

# ============================================================
# LECTOR DE EVENTOS LINUX via SSH
# ============================================================

def leer_eventos_linux(ip: str, username: str, password: str = None, key_path: str = None, port: int = 22) -> list:
    """Lee eventos del sistema Linux via SSH"""
    try:
        import paramiko
    except ImportError:
        return [{"error": "paramiko no disponible"}]

    eventos = []
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        connect_args = {
            "hostname": ip,
            "port":     port,
            "username": username,
            "timeout":  15,
        }
        if key_path:
            connect_args["key_filename"] = key_path
        elif password:
            connect_args["password"] = password

        client.connect(**connect_args)

        # Comandos para obtener eventos
        comandos = [
            # Errores críticos del sistema (journalctl)
            ("journalctl -p err -n 30 --no-pager -o short-iso 2>/dev/null || "
             "grep -i 'error\\|critical\\|failed\\|fatal' /var/log/syslog 2>/dev/null | tail -30"),
            # OOM Killer
            "dmesg | grep -i 'oom\\|killed process' | tail -10 2>/dev/null",
            # Fallos de autenticación SSH
            "grep 'Failed password\\|Invalid user' /var/log/auth.log 2>/dev/null | tail -20 || "
            "grep 'Failed password\\|Invalid user' /var/log/secure 2>/dev/null | tail -20",
            # Uso de disco
            "df -h 2>/dev/null",
            # Servicios fallidos
            "systemctl --failed --no-pager 2>/dev/null | head -20",
            # Carga del sistema
            "uptime 2>/dev/null",
        ]

        resultados_raw = {}
        for cmd in comandos:
            stdin, stdout, stderr = client.exec_command(cmd, timeout=10)
            output = stdout.read().decode('utf-8', errors='ignore').strip()
            if output:
                resultados_raw[cmd[:30]] = output

        client.close()

        # Procesar resultados
        eventos = procesar_eventos_linux(resultados_raw)

    except paramiko.AuthenticationException:
        return [{"error": "Credenciales SSH incorrectas. Verificar usuario y contraseña."}]
    except paramiko.NoValidConnectionsError:
        return [{"error": f"No se puede conectar por SSH a {ip}:{port}. Verificar que SSH está activo y el puerto es correcto."}]
    except Exception as e:
        return [{"error": f"Error SSH: {str(e)}"}]

    return eventos

def procesar_eventos_linux(raw: dict) -> list:
    """Procesa la salida de comandos Linux y genera eventos estructurados"""
    eventos = []

    for cmd_key, output in raw.items():
        lineas = output.split('\n')

        for linea in lineas:
            linea_lower = linea.lower()

            evento = {
                "canal":   "System",
                "event_id":"",
                "nivel":   "info",
                "fuente":  "",
                "mensaje": linea.strip()[:300],
                "fecha":   "",
                "os_type": "linux",
                "tipo_raw": "",
            }

            # Clasificar por contenido
            if any(x in linea_lower for x in ['oom', 'out of memory', 'killed process']):
                evento.update({"nivel": "critico", "tipo_raw": "linux_oom", "canal": "Kernel",
                               "fuente": "OOM Killer"})
            elif any(x in linea_lower for x in ['no space left', 'disk full', '100%']):
                evento.update({"nivel": "critico", "tipo_raw": "linux_disk_full", "canal": "Storage"})
            elif any(x in linea_lower for x in ['failed password', 'invalid user', 'authentication failure']):
                evento.update({"nivel": "critico", "tipo_raw": "linux_ssh_fail", "canal": "Security",
                               "fuente": "sshd"})
            elif 'kernel panic' in linea_lower:
                evento.update({"nivel": "critico", "tipo_raw": "linux_kernel_panic", "canal": "Kernel"})
            elif 'segfault' in linea_lower or 'segmentation fault' in linea_lower:
                evento.update({"nivel": "medio", "tipo_raw": "linux_segfault", "canal": "Application"})
            elif any(x in linea_lower for x in ['sudo:', 'sudo ']):
                evento.update({"nivel": "medio", "tipo_raw": "linux_sudo", "canal": "Security"})
            elif any(x in linea_lower for x in ['failed', 'error', 'critical', 'fatal']):
                evento.update({"nivel": "medio", "canal": "System"})
            elif 'cron' in linea_lower:
                evento.update({"nivel": "bajo", "tipo_raw": "linux_cron", "canal": "Cron"})
            else:
                evento.update({"nivel": "info"})

            if linea.strip() and len(linea.strip()) > 10:
                eventos.append(evento)

    # Servicios fallidos
    for cmd_key, output in raw.items():
        if 'failed' in output.lower() and 'systemctl' in cmd_key.lower():
            for linea in output.split('\n'):
                if '●' in linea or 'failed' in linea.lower():
                    eventos.append({
                        "canal":    "Services",
                        "event_id": "",
                        "nivel":    "critico",
                        "fuente":   "systemd",
                        "mensaje":  linea.strip()[:300],
                        "fecha":    "",
                        "os_type":  "linux",
                        "tipo_raw": "linux_service_fail",
                    })

    return eventos[:50]

# ============================================================
# ANÁLISIS IA DE EVENTOS
# ============================================================

def analizar_eventos(eventos: list) -> dict:
    """Analiza los eventos y genera diagnóstico con IA"""

    if not eventos:
        return {
            "total":      0,
            "criticos":   0,
            "medios":     0,
            "bajos":      0,
            "diagnostico":  [],
            "resumen_ia": "No se encontraron eventos de error en el sistema. El equipo parece estar operando con normalidad.",
        }

    # Verificar si hay errores de conexión
    errores_conexion = [e for e in eventos if "error" in e and len(e) == 1]
    if errores_conexion:
        return {
            "total":      0,
            "criticos":   0,
            "medios":     0,
            "bajos":      0,
            "diagnostico":  [],
            "resumen_ia": errores_conexion[0].get("error", "Error de conexión"),
            "error_conexion": True,
        }

    diagnostico = []
    eventos_procesados = set()

    for evento in eventos:
        if "error" in evento:
            continue

        tipo_raw = evento.get("tipo_raw", "")
        nivel    = evento.get("nivel", "info")
        mensaje  = evento.get("mensaje", "")
        event_id = evento.get("event_id", "")
        os_type  = evento.get("os_type", "unknown")

        # Buscar conocimiento en la base
        conocimiento = None

        # Buscar por tipo_raw (Linux)
        if tipo_raw and tipo_raw in CONOCIMIENTO_EVENTOS:
            conocimiento = CONOCIMIENTO_EVENTOS[tipo_raw]

        # Buscar por event_id (Windows)
        if not conocimiento and event_id:
            key = f"win_{event_id}"
            if key in CONOCIMIENTO_EVENTOS:
                conocimiento = CONOCIMIENTO_EVENTOS[key]

        # Evitar duplicados del mismo tipo
        dedup_key = tipo_raw or event_id or mensaje[:50]
        if dedup_key in eventos_procesados:
            continue
        eventos_procesados.add(dedup_key)

        if conocimiento:
            diag = {
                "nivel":       conocimiento["nivel"],
                "color":       nivel_color(conocimiento["nivel"]),
                "titulo":      conocimiento["titulo"],
                "causa":       conocimiento["causa"],
                "solucion":    conocimiento["solucion"],
                "evento_raw":  mensaje[:200],
                "canal":       evento.get("canal", ""),
                "fuente":      evento.get("fuente", ""),
                "fecha":       evento.get("fecha", ""),
                "os_type":     os_type,
            }
        elif nivel in ["critico", "medio"]:
            # Diagnóstico genérico para eventos sin conocimiento específico
            diag = {
                "nivel":      nivel,
                "color":      nivel_color(nivel),
                "titulo":     f"Evento de {nivel} detectado",
                "causa":      f"Se detectó un evento de nivel {nivel} en el canal {evento.get('canal', 'desconocido')}.",
                "solucion":   "Revisar el mensaje del evento completo en el sistema. Consultar la documentación del servicio o aplicación afectada.",
                "evento_raw": mensaje[:200],
                "canal":      evento.get("canal", ""),
                "fuente":     evento.get("fuente", ""),
                "fecha":      evento.get("fecha", ""),
                "os_type":    os_type,
            }
        else:
            continue

        diagnostico.append(diag)

    # Ordenar por criticidad
    orden = {"critico": 0, "medio": 1, "bajo": 2, "info": 3}
    diagnostico.sort(key=lambda x: orden.get(x["nivel"], 4))

    # Conteos
    criticos = sum(1 for d in diagnostico if d["nivel"] == "critico")
    medios   = sum(1 for d in diagnostico if d["nivel"] == "medio")
    bajos    = sum(1 for d in diagnostico if d["nivel"] == "bajo")

    # Resumen IA
    resumen = generar_resumen_eventos(diagnostico, criticos, medios, len(eventos))

    return {
        "total":      len(eventos),
        "criticos":   criticos,
        "medios":     medios,
        "bajos":      bajos,
        "diagnostico":  diagnostico[:20],
        "resumen_ia": resumen,
    }

def generar_resumen_eventos(diagnostico: list, criticos: int, medios: int, total: int) -> str:
    """Genera resumen ejecutivo del análisis de eventos"""

    if not diagnostico:
        return f"Se analizaron {total} eventos del sistema. No se identificaron patrones de riesgo que requieran atención inmediata."

    lineas = [f"Se analizaron {total} eventos del sistema identificando {len(diagnostico)} situaciones que requieren atención."]

    if criticos > 0:
        titles = [d["titulo"] for d in diagnostico if d["nivel"] == "critico"][:2]
        lineas.append(f"⚠ HAY {criticos} EVENTO(S) CRÍTICO(S): {', '.join(titles)}. Requieren atención inmediata.")

    if medios > 0:
        lineas.append(f"Adicionalmente se detectaron {medios} evento(s) de nivel medio que deben planificarse para resolución próxima.")

    if criticos == 0 and medios == 0:
        lineas.append("Los eventos detectados son de bajo impacto. Se recomienda mantener el monitoreo regular.")

    return " ".join(lineas)

# ============================================================
# MAIN
# ============================================================

def main():
    parser = argparse.ArgumentParser(description='TechAsset Pro — Lector de Eventos con IA')
    parser.add_argument('--ip',       required=True,  help='IP del equipo')
    parser.add_argument('--os',       required=True,  choices=['windows','linux'], help='Sistema operativo')
    parser.add_argument('--user',     required=True,  help='Usuario')
    parser.add_argument('--pass',     dest='password',default='',    help='Contraseña')
    parser.add_argument('--key',      default=None,   help='Ruta a clave privada SSH (Linux)')
    parser.add_argument('--domain',   default='',     help='Dominio Windows')
    parser.add_argument('--port',     type=int, default=22, help='Puerto SSH (Linux)')
    args = parser.parse_args()

    resultado = {
        "ip":          args.ip,
        "os_type":     args.os,
        "generado_en": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
    }

    # Leer eventos según OS
    if args.os == 'windows':
        eventos = leer_eventos_windows_wmi(args.ip, args.user, args.password, args.domain)
        if eventos and "error" in eventos[0]:
            # Fallback a Event Log directo
            eventos = leer_eventos_windows(args.ip, args.user, args.password, args.domain)
    else:
        eventos = leer_eventos_linux(args.ip, args.user, args.password, args.key, args.port)

    # Analizar con IA
    analisis = analizar_eventos(eventos)
    resultado["analisis"] = analisis

    print(json.dumps(resultado, ensure_ascii=False, default=str))

if __name__ == '__main__':
    main()
