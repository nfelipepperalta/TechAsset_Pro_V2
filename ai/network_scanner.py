#!/usr/bin/env python3
# ============================================================
#  TECHASSET PRO — Escáner de Red con Análisis IA
#  Archivo: /var/www/TechAsset/ai/network_scanner.py
#
#  Funciones:
#    - Escanear una IP o rango de IPs
#    - Detectar hostname, OS, MAC, puertos abiertos
#    - Analizar vulnerabilidades y riesgos de seguridad
#    - Generar recomendaciones con IA
#
#  Uso:
#    python3 network_scanner.py --ip 192.168.0.1
#    python3 network_scanner.py --range 192.168.0.0/24
# ============================================================

import sys
import json
import argparse
import warnings
import subprocess
from datetime import datetime

warnings.filterwarnings('ignore')

try:
    import nmap
except ImportError:
    print(json.dumps({"error": "python-nmap no instalado. Ejecutar: apt-get install -y python3-nmap"}))
    sys.exit(1)

# ============================================================
# BASE DE CONOCIMIENTO DE SEGURIDAD
# Puertos peligrosos, servicios vulnerables y recomendaciones
# ============================================================

PUERTOS_RIESGO = {
    21:   {"nombre": "FTP",         "riesgo": "alto",   "razon": "Protocolo sin cifrado. Credenciales transmitidas en texto plano.",
           "recomendacion": "Deshabilitar FTP y migrar a SFTP (puerto 22) o FTPS. Si es necesario, usar solo en red interna con VPN."},
    22:   {"nombre": "SSH",         "riesgo": "medio",  "razon": "SSH expuesto — riesgo de fuerza bruta si no está bien configurado.",
           "recomendacion": "Configurar autenticación por clave pública, deshabilitar login de root, cambiar puerto por defecto y limitar intentos fallidos con fail2ban."},
    23:   {"nombre": "Telnet",      "riesgo": "critico","razon": "Protocolo obsoleto sin cifrado. Transmite todo en texto plano incluyendo contraseñas.",
           "recomendacion": "DESHABILITAR INMEDIATAMENTE. Migrar a SSH (puerto 22). No hay justificación moderna para usar Telnet."},
    25:   {"nombre": "SMTP",        "riesgo": "medio",  "razon": "Servidor de correo expuesto. Riesgo de relay abierto y spam.",
           "recomendacion": "Verificar configuración anti-relay. Usar autenticación SMTP y TLS. Considerar filtro antispam."},
    53:   {"nombre": "DNS",         "riesgo": "medio",  "razon": "Servicio DNS expuesto. Riesgo de amplificación DDoS y cache poisoning.",
           "recomendacion": "Restringir consultas recursivas a clientes internos. Implementar DNSSEC. Actualizar software DNS regularmente."},
    80:   {"nombre": "HTTP",        "riesgo": "medio",  "razon": "Servidor web sin cifrado. Tráfico transmitido en texto plano.",
           "recomendacion": "Migrar a HTTPS (puerto 443) con certificado SSL/TLS válido. Configurar redirección automática de HTTP a HTTPS."},
    110:  {"nombre": "POP3",        "riesgo": "alto",   "razon": "Protocolo de correo sin cifrado.",
           "recomendacion": "Usar POP3S (puerto 995) con TLS o migrar a IMAP con cifrado."},
    135:  {"nombre": "RPC",         "riesgo": "alto",   "razon": "Microsoft RPC expuesto. Historial de vulnerabilidades críticas (MS03-026, Blaster worm).",
           "recomendacion": "Bloquear en firewall para acceso externo. Mantener Windows actualizado con parches de seguridad."},
    139:  {"nombre": "NetBIOS",     "riesgo": "alto",   "razon": "NetBIOS expuesto. Permite enumeración de usuarios, grupos y recursos compartidos.",
           "recomendacion": "Bloquear en firewall. Deshabilitar NetBIOS sobre TCP/IP si no es necesario para la red local."},
    143:  {"nombre": "IMAP",        "riesgo": "medio",  "razon": "IMAP sin cifrado expuesto.",
           "recomendacion": "Usar IMAPS (puerto 993) con TLS obligatorio."},
    161:  {"nombre": "SNMP",        "riesgo": "alto",   "razon": "SNMP v1/v2 sin cifrado. Community string 'public' por defecto permite lectura de configuración.",
           "recomendacion": "Migrar a SNMPv3 con autenticación y cifrado. Cambiar community strings por defecto. Restringir a IPs de gestión."},
    389:  {"nombre": "LDAP",        "riesgo": "alto",   "razon": "LDAP sin cifrado expuesto. Permite enumeración de usuarios del directorio.",
           "recomendacion": "Usar LDAPS (puerto 636) con TLS. Restringir acceso solo a servidores que necesiten autenticación LDAP."},
    443:  {"nombre": "HTTPS",       "riesgo": "bajo",   "razon": "Servidor web con cifrado. Verificar versión de TLS y certificado válido.",
           "recomendacion": "Verificar que usa TLS 1.2 o superior. Deshabilitar TLS 1.0 y 1.1. Renovar certificado antes de vencimiento."},
    445:  {"nombre": "SMB",         "riesgo": "critico","razon": "SMB expuesto. Vulnerable a EternalBlue (WannaCry, NotPetya). Uno de los vectores de ataque más explotados.",
           "recomendacion": "BLOQUEAR EN FIREWALL INMEDIATAMENTE para acceso externo. Aplicar MS17-010. Deshabilitar SMBv1. Solo permitir en red interna segmentada."},
    1433: {"nombre": "SQL Server",  "riesgo": "critico","razon": "Base de datos SQL Server expuesta directamente a la red. Riesgo de acceso no autorizado a datos.",
           "recomendacion": "NUNCA exponer SQL Server directamente. Usar firewall para permitir solo desde aplicaciones autorizadas. Cambiar puerto por defecto."},
    1521: {"nombre": "Oracle DB",   "riesgo": "critico","razon": "Base de datos Oracle expuesta.",
           "recomendacion": "Restringir acceso por firewall. Solo permitir desde servidores de aplicación autorizados."},
    3306: {"nombre": "MySQL",       "riesgo": "critico","razon": "MySQL/MariaDB expuesto directamente. Base de datos accesible desde la red.",
           "recomendacion": "Bloquear acceso externo. Bind solo a 127.0.0.1 si es local. Usar usuario con mínimos privilegios necesarios."},
    3389: {"nombre": "RDP",         "riesgo": "critico","razon": "Escritorio remoto Windows expuesto. Blanco frecuente de ataques de fuerza bruta y ransomware.",
           "recomendacion": "RESTRINGIR ACCESO por firewall. Habilitar NLA (Network Level Authentication). Usar VPN para acceso remoto. Considerar cambiar puerto. Implementar MFA."},
    4444: {"nombre": "Metasploit",  "riesgo": "critico","razon": "Puerto típico de reverse shells y frameworks de hacking. Posible compromiso del sistema.",
           "recomendacion": "INVESTIGAR INMEDIATAMENTE. Este puerto no debería estar abierto. Podría indicar que el sistema fue comprometido."},
    5432: {"nombre": "PostgreSQL",  "riesgo": "critico","razon": "PostgreSQL expuesto directamente.",
           "recomendacion": "Restringir por firewall. Configurar pg_hba.conf para permitir solo conexiones autorizadas."},
    5900: {"nombre": "VNC",         "riesgo": "alto",   "razon": "Acceso remoto de escritorio VNC expuesto. Frecuentemente sin autenticación fuerte.",
           "recomendacion": "Usar VNC solo sobre túnel SSH o VPN. Configurar contraseña fuerte. Considerar migrar a solución más segura."},
    6379: {"nombre": "Redis",       "riesgo": "critico","razon": "Redis expuesto sin autenticación por defecto. Permite lectura/escritura de datos y ejecución de comandos.",
           "recomendacion": "Nunca exponer Redis a Internet. Configurar requirepass en redis.conf. Bind solo a interfaces internas."},
    8080: {"nombre": "HTTP-Alt",    "riesgo": "medio",  "razon": "Puerto web alternativo expuesto. Posible panel de administración o aplicación web sin SSL.",
           "recomendacion": "Verificar qué servicio usa este puerto. Si es panel de administración, restringir acceso y agregar HTTPS."},
    8443: {"nombre": "HTTPS-Alt",   "riesgo": "bajo",   "razon": "Puerto HTTPS alternativo.",
           "recomendacion": "Verificar certificado SSL válido y configuración TLS correcta."},
    27017:{"nombre": "MongoDB",     "riesgo": "critico","razon": "MongoDB expuesto sin autenticación por defecto. Miles de bases de datos han sido comprometidas por esta configuración.",
           "recomendacion": "URGENTE: Habilitar autenticación en MongoDB. Bind solo a interfaces internas. Nunca exponer a Internet."},
}

OS_RIESGOS = {
    "windows xp":        {"riesgo": "critico", "msg": "Windows XP sin soporte desde 2014. Sin parches de seguridad."},
    "windows 7":         {"riesgo": "critico", "msg": "Windows 7 sin soporte desde 2020. Sin parches de seguridad."},
    "windows server 2003":{"riesgo":"critico", "msg": "Windows Server 2003 sin soporte desde 2015."},
    "windows server 2008":{"riesgo":"alto",    "msg": "Windows Server 2008 sin soporte desde 2020."},
    "windows server 2012":{"riesgo":"alto",    "msg": "Windows Server 2012 sin soporte desde 2023."},
    "windows 10":        {"riesgo": "bajo",    "msg": "Windows 10 con soporte activo. Mantener actualizaciones al día."},
    "windows 11":        {"riesgo": "bajo",    "msg": "Windows 11 — versión actual con soporte activo."},
    "windows server 2019":{"riesgo":"bajo",    "msg": "Windows Server 2019 con soporte hasta 2029."},
    "windows server 2022":{"riesgo":"bajo",    "msg": "Windows Server 2022 con soporte hasta 2031."},
    "ubuntu 14":         {"riesgo": "critico", "msg": "Ubuntu 14.04 sin soporte desde 2019."},
    "ubuntu 16":         {"riesgo": "alto",    "msg": "Ubuntu 16.04 sin soporte estándar desde 2021."},
    "ubuntu 18":         {"riesgo": "medio",   "msg": "Ubuntu 18.04 en soporte extendido hasta 2028 con ESM."},
    "ubuntu 20":         {"riesgo": "bajo",    "msg": "Ubuntu 20.04 LTS con soporte hasta 2025."},
    "ubuntu 22":         {"riesgo": "bajo",    "msg": "Ubuntu 22.04 LTS con soporte hasta 2027."},
    "ubuntu 24":         {"riesgo": "bajo",    "msg": "Ubuntu 24.04 LTS — versión actual con soporte hasta 2029."},
    "centos 6":          {"riesgo": "critico", "msg": "CentOS 6 sin soporte desde 2020."},
    "centos 7":          {"riesgo": "alto",    "msg": "CentOS 7 sin soporte desde junio 2024."},
    "debian 8":          {"riesgo": "critico", "msg": "Debian 8 sin soporte desde 2022."},
    "debian 9":          {"riesgo": "alto",    "msg": "Debian 9 sin soporte estándar desde 2020."},
    "debian 10":         {"riesgo": "medio",   "msg": "Debian 10 en LTS hasta 2024."},
    "debian 11":         {"riesgo": "bajo",    "msg": "Debian 11 con soporte activo."},
    "debian 12":         {"riesgo": "bajo",    "msg": "Debian 12 — versión estable actual."},
}

# ============================================================
# ESCANEO DE RED
# ============================================================

def escanear_ip(ip: str, intensidad: str = "normal") -> dict:
    """Escanea una IP y retorna información completa del host"""

    nm = nmap.PortScanner()

    # Argumentos según intensidad
    args_map = {
        "rapido": "-sV -O --osscan-guess -T4 --top-ports 100",
        "normal": "-sV -O --osscan-guess -T4 -p 1-10000",
        "completo": "-sV -O --osscan-guess -A -T4 -p-",
    }
    args = args_map.get(intensidad, args_map["normal"])

    try:
        nm.scan(hosts=ip, arguments=args, sudo=True)
    except Exception as e:
        # Intentar sin sudo si falla
        try:
            nm.scan(hosts=ip, arguments="-sV --osscan-guess -T4 --top-ports 1000")
        except Exception as e2:
            return {"error": f"Error al escanear {ip}: {str(e2)}"}

    if ip not in nm.all_hosts():
        return {"error": f"Host {ip} no responde o está inaccesible"}

    host = nm[ip]
    resultado = {
        "ip":        ip,
        "estado":    host.state(),
        "hostname":  "",
        "mac":       "",
        "fabricante":"",
        "os":        [],
        "os_mejor":  "",
        "puertos":   [],
        "servicios": [],
    }

    # Hostname
    hostnames = host.hostnames()
    if hostnames:
        resultado["hostname"] = hostnames[0].get("name", "") or ip

    # MAC Address
    if "mac" in host.get("addresses", {}):
        resultado["mac"] = host["addresses"]["mac"]
        resultado["fabricante"] = host.get("vendor", {}).get(resultado["mac"], "")

    # Sistema operativo
    if "osmatch" in host:
        for os_match in host["osmatch"][:3]:
            resultado["os"].append({
                "nombre":    os_match.get("name", ""),
                "precision": int(os_match.get("accuracy", 0)),
            })
        if resultado["os"]:
            resultado["os_mejor"] = resultado["os"][0]["nombre"]

    # Puertos y servicios
    for proto in host.all_protocols():
        puertos = host[proto].keys()
        for puerto in sorted(puertos):
            info = host[proto][puerto]
            if info["state"] == "open":
                resultado["puertos"].append({
                    "puerto":   puerto,
                    "protocolo":proto,
                    "estado":   info["state"],
                    "servicio": info.get("name", ""),
                    "version":  info.get("version", ""),
                    "producto": info.get("product", ""),
                    "extra":    info.get("extrainfo", ""),
                })

    return resultado

def escanear_rango(rango: str) -> list:
    """Escanea un rango CIDR y retorna hosts activos"""

    nm = nmap.PortScanner()

    try:
        nm.scan(hosts=rango, arguments="-sn -T4", sudo=False)
    except Exception as e:
        try:
            nm.scan(hosts=rango, arguments="-sn -T4")
        except Exception as e2:
            return []

    hosts_activos = []
    for host in nm.all_hosts():
        info = nm[host]
        hostname = ""
        hostnames = info.hostnames()
        if hostnames:
            hostname = hostnames[0].get("name", "") or host

        hosts_activos.append({
            "ip":       host,
            "estado":   info.state(),
            "hostname": hostname,
            "mac":      info.get("addresses", {}).get("mac", ""),
        })

    return hosts_activos

# ============================================================
# ANÁLISIS DE SEGURIDAD CON IA
# ============================================================

def analizar_seguridad(scan_result: dict) -> dict:
    """Analiza los resultados del escaneo y genera recomendaciones IA"""

    hallazgos    = []
    recomendaciones = []
    score_riesgo = 0
    puertos_criticos = []

    # ── Análisis de puertos ───────────────────────────────────
    for puerto_info in scan_result.get("puertos", []):
        puerto = puerto_info["puerto"]

        if puerto in PUERTOS_RIESGO:
            info_riesgo = PUERTOS_RIESGO[puerto]
            nivel = info_riesgo["riesgo"]

            puntos = {"bajo": 5, "medio": 15, "alto": 25, "critico": 40}
            score_riesgo += puntos.get(nivel, 0)

            hallazgo = {
                "tipo":          "puerto",
                "nivel":         nivel,
                "titulo":        f"Puerto {puerto} ({info_riesgo['nombre']}) abierto",
                "descripcion":   info_riesgo["razon"],
                "recomendacion": info_riesgo["recomendacion"],
                "puerto":        puerto,
                "servicio":      puerto_info.get("servicio", ""),
                "version":       f"{puerto_info.get('producto','')} {puerto_info.get('version','')}".strip(),
            }
            hallazgos.append(hallazgo)

            if nivel == "critico":
                puertos_criticos.append(puerto_info["nombre"] if "nombre" in puerto_info else str(puerto))
                recomendaciones.insert(0, info_riesgo["recomendacion"])
            else:
                recomendaciones.append(info_riesgo["recomendacion"])
        else:
            # Puerto abierto no reconocido
            if puerto > 1024:
                hallazgos.append({
                    "tipo":          "puerto",
                    "nivel":         "info",
                    "titulo":        f"Puerto {puerto} abierto ({puerto_info.get('servicio','desconocido')})",
                    "descripcion":   f"Servicio: {puerto_info.get('producto','')} {puerto_info.get('version','')}".strip(),
                    "recomendacion": "Verificar si este servicio es necesario. Si no se usa, cerrarlo para reducir la superficie de ataque.",
                    "puerto":        puerto,
                    "servicio":      puerto_info.get("servicio", ""),
                    "version":       f"{puerto_info.get('producto','')} {puerto_info.get('version','')}".strip(),
                })
                score_riesgo += 3

    # ── Análisis de sistema operativo ─────────────────────────
    os_mejor = scan_result.get("os_mejor", "").lower()
    os_detectado = None

    for os_key, os_info in OS_RIESGOS.items():
        if os_key in os_mejor:
            os_detectado = os_info
            nivel = os_info["riesgo"]
            puntos = {"bajo": 0, "medio": 10, "alto": 20, "critico": 35}
            score_riesgo += puntos.get(nivel, 0)

            hallazgos.append({
                "tipo":          "sistema_operativo",
                "nivel":         nivel,
                "titulo":        f"Sistema operativo: {scan_result.get('os_mejor', 'Desconocido')}",
                "descripcion":   os_info["msg"],
                "recomendacion": "Actualizar al sistema operativo con soporte activo o aplicar Extended Security Updates (ESU)." if nivel in ["critico","alto"] else "Mantener actualizaciones de seguridad al día.",
            })
            break

    if not os_detectado and os_mejor:
        hallazgos.append({
            "tipo":          "sistema_operativo",
            "nivel":         "info",
            "titulo":        f"Sistema operativo detectado: {scan_result.get('os_mejor', 'No determinado')}",
            "descripcion":   "No se pudo determinar el estado de soporte del SO.",
            "recomendacion": "Verificar manualmente que el sistema operativo tenga soporte activo y actualizaciones de seguridad.",
        })

    # ── Análisis general ──────────────────────────────────────
    puertos_abiertos = len(scan_result.get("puertos", []))

    if puertos_abiertos > 20:
        score_riesgo += 15
        hallazgos.append({
            "tipo":          "configuracion",
            "nivel":         "medio",
            "titulo":        f"Superficie de ataque amplia ({puertos_abiertos} puertos abiertos)",
            "descripcion":   "Un gran número de puertos abiertos aumenta la superficie de ataque del sistema.",
            "recomendacion": "Aplicar principio de mínimo privilegio: cerrar todos los puertos que no sean estrictamente necesarios. Implementar firewall con política de denegación por defecto.",
        })
    elif puertos_abiertos > 10:
        score_riesgo += 8
        hallazgos.append({
            "tipo":          "configuracion",
            "nivel":         "info",
            "titulo":        f"{puertos_abiertos} puertos abiertos detectados",
            "descripcion":   "Revisar si todos los servicios son necesarios.",
            "recomendacion": "Revisar y documentar cada puerto abierto. Cerrar los que no sean necesarios.",
        })

    # Sin MAC (host remoto o no detectable)
    if not scan_result.get("mac"):
        hallazgos.append({
            "tipo":          "info",
            "nivel":         "info",
            "titulo":        "MAC Address no detectada",
            "descripcion":   "El equipo está fuera de la red local o el escaneo ARP no fue posible.",
            "recomendacion": "Para escaneos remotos la MAC no es detectable. Registrar físicamente el equipo.",
        })

    # ── Score final ───────────────────────────────────────────
    score_riesgo = min(100, score_riesgo)

    nivel_general = "BAJO"
    color_general = "success"
    if score_riesgo >= 70:
        nivel_general = "CRÍTICO"
        color_general = "danger"
    elif score_riesgo >= 45:
        nivel_general = "ALTO"
        color_general = "danger"
    elif score_riesgo >= 20:
        nivel_general = "MEDIO"
        color_general = "warning"

    # Ordenar hallazgos por criticidad
    orden = {"critico": 0, "alto": 1, "medio": 2, "bajo": 3, "info": 4}
    hallazgos.sort(key=lambda x: orden.get(x["nivel"], 5))

    # Resumen ejecutivo generado por IA
    resumen_ia = generar_resumen_ia(scan_result, hallazgos, score_riesgo, nivel_general)

    return {
        "score_riesgo":    score_riesgo,
        "nivel_riesgo":    nivel_general,
        "color_riesgo":    color_general,
        "puertos_criticos":len([h for h in hallazgos if h["nivel"] == "critico"]),
        "hallazgos":       hallazgos,
        "recomendaciones": list(dict.fromkeys(recomendaciones)),  # sin duplicados
        "resumen_ia":      resumen_ia,
        "total_hallazgos": len(hallazgos),
    }

def generar_resumen_ia(scan: dict, hallazgos: list, score: int, nivel: str) -> str:
    """Genera un resumen ejecutivo en lenguaje natural"""

    ip       = scan.get("ip", "")
    hostname = scan.get("hostname", ip)
    os_str   = scan.get("os_mejor", "Sistema operativo no determinado")
    n_puertos = len(scan.get("puertos", []))
    criticos = [h for h in hallazgos if h["nivel"] == "critico"]
    altos    = [h for h in hallazgos if h["nivel"] == "alto"]

    lineas = []
    lineas.append(f"El equipo {hostname} ({ip}) ha sido analizado con un score de riesgo de {score}/100 — nivel {nivel}.")

    if os_str and os_str != "Sistema operativo no determinado":
        lineas.append(f"Se detectó {os_str} como sistema operativo.")

    lineas.append(f"Se encontraron {n_puertos} puertos abiertos con {len(hallazgos)} hallazgos de seguridad.")

    if criticos:
        nombres = ", ".join([h["titulo"] for h in criticos[:3]])
        lineas.append(f"⚠ ATENCIÓN INMEDIATA REQUERIDA: {len(criticos)} hallazgo(s) crítico(s): {nombres}.")

    if altos:
        lineas.append(f"Adicionalmente hay {len(altos)} hallazgo(s) de riesgo alto que requieren atención pronta.")

    if score >= 70:
        lineas.append("Este equipo representa un riesgo crítico para la seguridad de la red. Se recomienda aislarlo hasta resolver las vulnerabilidades identificadas.")
    elif score >= 45:
        lineas.append("Se recomienda atender las vulnerabilidades identificadas en los próximos días para reducir el riesgo.")
    elif score >= 20:
        lineas.append("El equipo presenta riesgos moderados. Planificar las mejoras en el próximo ciclo de mantenimiento.")
    else:
        lineas.append("El equipo presenta una postura de seguridad aceptable. Mantener actualizaciones regulares.")

    return " ".join(lineas)

# ============================================================
# FUNCIÓN PRINCIPAL
# ============================================================

def main():
    parser = argparse.ArgumentParser(description='TechAsset Pro — Escáner de Red con IA')
    parser.add_argument('--ip',         type=str, help='IP a escanear')
    parser.add_argument('--range',      type=str, help='Rango CIDR (ej: 192.168.0.0/24)')
    parser.add_argument('--intensidad', type=str, default='normal',
                        choices=['rapido','normal','completo'], help='Intensidad del escaneo')
    args = parser.parse_args()

    if not args.ip and not args.range:
        print(json.dumps({"error": "Especificar --ip o --range"}))
        sys.exit(1)

    resultado_final = {
        "generado_en": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        "modo":        "ip" if args.ip else "rango",
    }

    if args.range:
        # Escaneo de rango — solo descubrimiento
        hosts = escanear_rango(args.range)
        resultado_final["rango"]       = args.range
        resultado_final["hosts"]       = hosts
        resultado_final["total_hosts"] = len(hosts)

    else:
        # Escaneo detallado de una IP
        scan = escanear_ip(args.ip, args.intensidad)

        if "error" in scan:
            print(json.dumps(scan))
            sys.exit(1)

        analisis = analizar_seguridad(scan)

        resultado_final["scan"]    = scan
        resultado_final["analisis"]= analisis

        # Datos sugeridos para registrar como activo en TechAsset
        resultado_final["sugerencia_activo"] = {
            "nombre":    scan.get("hostname", args.ip),
            "marca":     scan.get("fabricante", ""),
            "modelo":    scan.get("os_mejor", ""),
            "serial":    "",
            "ubicacion": "",
            "ip":        args.ip,
            "mac":       scan.get("mac", ""),
        }

    print(json.dumps(resultado_final, ensure_ascii=False, default=str))

if __name__ == '__main__':
    main()
