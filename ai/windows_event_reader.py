#!/usr/bin/env python3
# ============================================================
#  TECHASSET PRO — Lector de Eventos Windows
#  Archivo: /var/www/TechAsset/ai/windows_event_reader.py
#
#  Lee el Windows Event Log de forma remota via WMI/pywin32
#  o localmente si se ejecuta en el mismo equipo Windows.
#
#  Uso:
#    python3 windows_event_reader.py --host 192.168.0.79 --limit 50
#    python3 windows_event_reader.py --host 192.168.0.79 --log System --limit 20
# ============================================================

import sys
import json
import argparse
import subprocess
from datetime import datetime, timedelta

def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument('--host',   default='192.168.0.79', help='IP del servidor Windows')
    parser.add_argument('--user',   default='',             help='Usuario Windows (opcional)')
    parser.add_argument('--passwd', default='',             help='Contraseña Windows (opcional)')
    parser.add_argument('--log',    default='Application',  help='Log a leer: Application, System, Security')
    parser.add_argument('--limit',  type=int, default=50,   help='Máximo de eventos a retornar')
    parser.add_argument('--hours',  type=int, default=24,   help='Horas hacia atrás a consultar')
    return parser.parse_args()

def nivel_a_texto(nivel: int) -> str:
    mapa = {1: 'Critical', 2: 'Error', 3: 'Warning', 4: 'Information', 5: 'Verbose'}
    return mapa.get(nivel, 'Unknown')

def nivel_a_clase(nivel: int) -> str:
    mapa = {1: 'danger', 2: 'danger', 3: 'warning', 4: 'info', 5: 'secondary'}
    return mapa.get(nivel, 'secondary')

def leer_via_powershell_remoto(host: str, log: str, limit: int, hours: int, user: str, passwd: str) -> list:
    """
    Usa PowerShell con Invoke-Command para leer el Event Log remotamente.
    Requiere WinRM habilitado en el host destino.
    """
    since = (datetime.now() - timedelta(hours=hours)).strftime('%Y-%m-%dT%H:%M:%S')

    ps_script = f"""
$events = Get-WinEvent -LogName '{log}' -MaxEvents {limit} -ErrorAction SilentlyContinue |
    Where-Object {{ $_.TimeCreated -gt [datetime]'{since}' }} |
    Select-Object TimeCreated, Id, LevelDisplayName, Level, ProviderName, Message |
    ForEach-Object {{
        [PSCustomObject]@{{
            time     = $_.TimeCreated.ToString('yyyy-MM-dd HH:mm:ss')
            event_id = $_.Id
            level    = $_.LevelDisplayName
            level_n  = $_.Level
            source   = $_.ProviderName
            message  = ($_.Message -replace '[\\r\\n]+', ' ').Substring(0, [Math]::Min(200, ($_.Message -replace '[\\r\\n]+', ' ').Length))
        }}
    }}
$events | ConvertTo-Json -Compress
"""

    if user and passwd:
        cmd = [
            'powershell', '-NoProfile', '-NonInteractive', '-Command',
            f"""
$pass = ConvertTo-SecureString '{passwd}' -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential('{user}', $pass)
$s = New-PSSession -ComputerName '{host}' -Credential $cred -ErrorAction Stop
$result = Invoke-Command -Session $s -ScriptBlock {{ {ps_script} }}
Remove-PSSession $s
$result
"""
        ]
    else:
        cmd = [
            'powershell', '-NoProfile', '-NonInteractive', '-Command',
            f"Invoke-Command -ComputerName '{host}' -ScriptBlock {{ {ps_script} }}"
        ]

    result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)

    if result.returncode != 0 or not result.stdout.strip():
        raise RuntimeError(f"PowerShell remoto falló: {result.stderr[:300]}")

    raw = json.loads(result.stdout.strip())
    if isinstance(raw, dict):
        raw = [raw]

    eventos = []
    for e in raw:
        eventos.append({
            'time':     e.get('time', ''),
            'event_id': e.get('event_id', 0),
            'level':    e.get('level', 'Unknown'),
            'level_n':  e.get('level_n', 4),
            'badge':    nivel_a_clase(e.get('level_n', 4)),
            'source':   e.get('source', ''),
            'message':  e.get('message', ''),
            'origin':   'windows',
            'host':     host,
            'log':      log,
        })
    return eventos


def leer_via_wmi(host: str, log: str, limit: int, hours: int) -> list:
    """
    Alternativa: usa wmic desde la línea de comandos.
    Funciona sin WinRM pero requiere SMB/RPC habilitado.
    """
    since_wmi = (datetime.now() - timedelta(hours=hours)).strftime('%Y%m%d%H%M%S.000000+000')

    cmd = [
        'wmic', '/node:' + host,
        'NTEVENTLOG',
        'where',
        f"Logfile='{log}' AND TimeGenerated > '{since_wmi}'",
        'get',
        'EventCode,Type,SourceName,Message,TimeGenerated',
        '/format:csv'
    ]

    result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)

    if result.returncode != 0:
        raise RuntimeError(f"WMIC falló: {result.stderr[:200]}")

    lineas = [l for l in result.stdout.strip().split('\n') if l.strip() and ',' in l]
    if len(lineas) < 2:
        return []

    headers = [h.strip().lower() for h in lineas[0].split(',')]
    eventos = []

    for linea in lineas[1:limit+1]:
        cols = linea.split(',')
        if len(cols) < len(headers):
            continue
        row = dict(zip(headers, [c.strip() for c in cols]))

        tipo_mapa = {'Error': 2, 'Warning': 3, 'Information': 4}
        nivel_n = tipo_mapa.get(row.get('type', ''), 4)

        eventos.append({
            'time':     row.get('timegenerated', '')[:19].replace('T', ' '),
            'event_id': row.get('eventcode', '0'),
            'level':    row.get('type', 'Information'),
            'level_n':  nivel_n,
            'badge':    nivel_a_clase(nivel_n),
            'source':   row.get('sourcename', ''),
            'message':  row.get('message', '')[:200],
            'origin':   'windows',
            'host':     host,
            'log':      log,
        })

    return eventos


def main():
    args = parse_args()
    resultado = {
        'origen':  'windows',
        'host':    args.host,
        'log':     args.log,
        'total':   0,
        'eventos': [],
        'error':   None,
    }

    try:
        # Intentar primero PowerShell remoto, luego WMIC como fallback
        try:
            eventos = leer_via_powershell_remoto(
                args.host, args.log, args.limit, args.hours, args.user, args.passwd
            )
        except Exception as e1:
            try:
                eventos = leer_via_wmi(args.host, args.log, args.limit, args.hours)
            except Exception as e2:
                raise RuntimeError(
                    f"PowerShell remoto: {str(e1)[:150]} | WMIC: {str(e2)[:150]}"
                )

        resultado['eventos'] = eventos
        resultado['total']   = len(eventos)

    except Exception as e:
        resultado['error'] = str(e)

    print(json.dumps(resultado, ensure_ascii=False))


if __name__ == '__main__':
    main()
