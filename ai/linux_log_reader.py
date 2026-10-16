#!/usr/bin/env python3
# ============================================================
#  TECHASSET PRO — Lector de Logs Linux
#  Archivo: /var/www/TechAsset/ai/linux_log_reader.py
#
#  Lee logs del sistema Linux local:
#    - Apache error/access
#    - Syslog / journald
#    - Auth.log
#
#  Uso:
#    python3 linux_log_reader.py --limit 50 --hours 24
# ============================================================

import sys
import json
import argparse
import subprocess
import re
import os
from datetime import datetime, timedelta

FUENTES = {
    'apache_error':  '/var/log/apache2/error.log',
    'apache_access': '/var/log/apache2/access.log',
    'syslog':        '/var/log/syslog',
    'auth':          '/var/log/auth.log',
    'kern':          '/var/log/kern.log',
}

# Patrones para clasificar nivel de cada línea
NIVEL_PATTERNS = [
    (re.compile(r'\b(crit|emerg|alert|fatal|FATAL|CRITICAL)\b', re.I), 'Critical',     'danger',   1),
    (re.compile(r'\b(error|ERROR|err)\b'),                              'Error',        'danger',   2),
    (re.compile(r'\b(warn|WARNING|warning)\b', re.I),                  'Warning',      'warning',  3),
    (re.compile(r'\b(notice|info|INFO|NOTICE)\b', re.I),               'Information',  'info',     4),
]

# Patrones de timestamp en distintos formatos de log
TS_PATTERNS = [
    # Apache: [Wed Apr 22 04:36:46.586333 2026]
    (re.compile(r'\[(\w{3} \w{3} \d+ \d+:\d+:\d+)(?:\.\d+)? (\d{4})\]'),
     lambda m: datetime.strptime(f"{m.group(1)} {m.group(2)}", "%a %b %d %H:%M:%S %Y")),
    # Syslog: May  6 14:32:01
    (re.compile(r'^(\w{3}\s+\d+\s+\d+:\d+:\d+)'),
     lambda m: datetime.strptime(f"{datetime.now().year} {m.group(1).strip()}", "%Y %b %d %H:%M:%S")),
    # ISO: 2026-05-06T14:32:01
    (re.compile(r'(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})'),
     lambda m: datetime.fromisoformat(m.group(1))),
]


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument('--limit', type=int, default=50,  help='Máx eventos por fuente')
    parser.add_argument('--hours', type=int, default=24,  help='Horas hacia atrás')
    parser.add_argument('--fuente', default='all',        help='Fuente específica o "all"')
    return parser.parse_args()


def extraer_timestamp(linea: str):
    for patron, parser in TS_PATTERNS:
        m = patron.search(linea)
        if m:
            try:
                return parser(m)
            except Exception:
                pass
    return None


def clasificar_nivel(linea: str):
    for patron, nivel, badge, nivel_n in NIVEL_PATTERNS:
        if patron.search(linea):
            return nivel, badge, nivel_n
    return 'Information', 'info', 4


def leer_archivo(path: str, fuente: str, limit: int, desde: datetime) -> list:
    if not os.path.exists(path):
        return []

    eventos = []

    # Usar tail para no leer archivos enormes
    try:
        result = subprocess.run(
            ['tail', '-n', str(limit * 5), path],
            capture_output=True, text=True, timeout=10
        )
        lineas = result.stdout.splitlines()
    except Exception:
        return []

    for linea in lineas:
        linea = linea.strip()
        if not linea:
            continue

        ts = extraer_timestamp(linea)
        if ts and ts < desde:
            continue

        nivel, badge, nivel_n = clasificar_nivel(linea)

        # Solo mostrar errores y warnings de apache access (no todas las peticiones)
        if fuente == 'apache_access' and nivel_n >= 4:
            if ' 200 ' in linea or ' 304 ' in linea:
                continue

        eventos.append({
            'time':     ts.strftime('%Y-%m-%d %H:%M:%S') if ts else 'Sin fecha',
            'event_id': '-',
            'level':    nivel,
            'level_n':  nivel_n,
            'badge':    badge,
            'source':   fuente,
            'message':  linea[:250],
            'origin':   'linux',
            'host':     'localhost',
            'log':      fuente,
        })

    # Ordenar por tiempo descendente y recortar
    eventos.sort(key=lambda x: x['time'], reverse=True)
    return eventos[:limit]


def leer_journald(limit: int, hours: int) -> list:
    """Lee journald como alternativa al syslog clásico."""
    try:
        result = subprocess.run(
            ['journalctl', '--no-pager', '-n', str(limit), '--since', f'{hours} hours ago',
             '-p', 'warning', '-o', 'short-iso'],
            capture_output=True, text=True, timeout=15
        )
        if result.returncode != 0:
            return []
    except FileNotFoundError:
        return []

    eventos = []
    for linea in result.stdout.splitlines():
        linea = linea.strip()
        if not linea or linea.startswith('--'):
            continue

        ts = extraer_timestamp(linea)
        nivel, badge, nivel_n = clasificar_nivel(linea)

        eventos.append({
            'time':     ts.strftime('%Y-%m-%d %H:%M:%S') if ts else 'Sin fecha',
            'event_id': '-',
            'level':    nivel,
            'level_n':  nivel_n,
            'badge':    badge,
            'source':   'journald',
            'message':  linea[:250],
            'origin':   'linux',
            'host':     'localhost',
            'log':      'journald',
        })

    return eventos


def main():
    args = parse_args()
    desde = datetime.now() - timedelta(hours=args.hours)

    resultado = {
        'origen':  'linux',
        'host':    'localhost',
        'total':   0,
        'eventos': [],
        'error':   None,
        'fuentes': [],
    }

    try:
        todos = []

        fuentes_a_leer = FUENTES if args.fuente == 'all' else {
            args.fuente: FUENTES.get(args.fuente, args.fuente)
        }

        for nombre, path in fuentes_a_leer.items():
            eventos = leer_archivo(path, nombre, args.limit, desde)
            if eventos:
                todos.extend(eventos)
                resultado['fuentes'].append({'nombre': nombre, 'path': path, 'count': len(eventos)})

        # Complementar con journald si hay pocos eventos
        if len(todos) < 10:
            jd = leer_journald(args.limit, args.hours)
            if jd:
                todos.extend(jd)
                resultado['fuentes'].append({'nombre': 'journald', 'path': 'systemd', 'count': len(jd)})

        # Ordenar todo por nivel (críticos primero) y luego por tiempo
        todos.sort(key=lambda x: (x['level_n'], x['time']), reverse=False)
        todos = todos[:args.limit * 2]

        resultado['eventos'] = todos
        resultado['total']   = len(todos)

    except Exception as e:
        resultado['error'] = str(e)

    print(json.dumps(resultado, ensure_ascii=False))


if __name__ == '__main__':
    main()
