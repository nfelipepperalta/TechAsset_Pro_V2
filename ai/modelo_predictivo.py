#!/usr/bin/env python3
# ============================================================
#  TECHASSET PRO — Modelo de IA Predictiva de Fallos
#  Archivo: /var/www/TechAsset/ai/modelo_predictivo.py
#
#  Predice:
#    - Probabilidad de fallo por equipo (0-100%)
#    - Ranking de activos de alto riesgo
#    - Fecha estimada del próximo mantenimiento
#
#  Uso:
#    python3 modelo_predictivo.py
#    python3 modelo_predictivo.py --asset_id 5
# ============================================================

import sys
import json
import argparse
import warnings
import os
from datetime import datetime, timedelta

warnings.filterwarnings('ignore')

# ── Dependencias ──────────────────────────────────────────────
try:
    import pandas as pd
    import numpy as np
    import pyodbc
    from sklearn.ensemble import RandomForestClassifier, GradientBoostingRegressor
    from sklearn.preprocessing import LabelEncoder, MinMaxScaler
    from sklearn.model_selection import train_test_split
    from sklearn.metrics import accuracy_score
    import joblib
except ImportError as e:
    print(json.dumps({"error": f"Dependencia faltante: {str(e)}. Ejecutar: pip3 install scikit-learn pandas numpy pyodbc --break-system-packages"}))
    sys.exit(1)

# ── Configuración de conexión ─────────────────────────────────
def get_connection():
    """Lee credenciales del .env de TechAsset Pro"""
    env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
    config = {}

    try:
        with open(env_path, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    key, _, value = line.partition('=')
                    config[key.strip()] = value.strip()
    except FileNotFoundError:
        # Fallback: variables de entorno del sistema
        config = {
            'DB_HOST':       os.environ.get('DB_HOST', 'localhost'),
            'DB_NAME':       os.environ.get('DB_NAME', 'TECHASSET'),
            'DB_USER':       os.environ.get('DB_USER', 'sa'),
            'DB_PASS':       os.environ.get('DB_PASS', ''),
            'DB_TRUST_CERT': os.environ.get('DB_TRUST_CERT', 'true'),
        }

    conn_str = (
        f"DRIVER={{ODBC Driver 18 for SQL Server}};"
        f"SERVER={config.get('DB_HOST', 'localhost')},{config.get('DB_PORT', '1433')};"
        f"DATABASE={config.get('DB_NAME', 'TECHASSET')};"
        f"UID={config.get('DB_USER', 'sa')};"
        f"PWD={config.get('DB_PASS', '')};"
        f"TrustServerCertificate={'yes' if config.get('DB_TRUST_CERT','false').lower()=='true' else 'no'};"
        f"Encrypt=yes;"
    )

    return pyodbc.connect(conn_str, timeout=15)

# ── Extracción de datos ───────────────────────────────────────
def extract_data(conn):
    """Extrae activos y su historial de mantenimientos"""

    query_activos = """
        SELECT
            a.id,
            a.nombre,
            a.marca,
            a.modelo,
            at.nombre           AS tipo,
            at.categoria,
            a.estado,
            a.fecha_compra,
            a.garantia_hasta,
            a.valor,
            DATEDIFF(DAY, a.fecha_compra, GETDATE())        AS edad_dias,
            DATEDIFF(DAY, ISNULL(a.garantia_hasta, a.fecha_compra), GETDATE()) AS dias_sin_garantia,
            ISNULL(
                (SELECT COUNT(*) FROM dbo.maintenance m WHERE m.asset_id = a.id), 0
            ) AS total_mantenimientos,
            ISNULL(
                (SELECT COUNT(*) FROM dbo.maintenance m WHERE m.asset_id = a.id AND m.tipo = 'correctivo'), 0
            ) AS mantenimientos_correctivos,
            ISNULL(
                (SELECT COUNT(*) FROM dbo.maintenance m WHERE m.asset_id = a.id AND m.tipo = 'preventivo'), 0
            ) AS mantenimientos_preventivos,
            ISNULL(
                (SELECT SUM(ISNULL(m.costo,0)) FROM dbo.maintenance m WHERE m.asset_id = a.id), 0
            ) AS costo_total_mantenimiento,
            ISNULL(
                (SELECT DATEDIFF(DAY, MAX(m.fecha), GETDATE())
                 FROM dbo.maintenance m WHERE m.asset_id = a.id), 9999
            ) AS dias_desde_ultimo_mantenimiento,
            ISNULL(
                (SELECT TOP 1 m.proximo_mantenimiento
                 FROM dbo.maintenance m
                 WHERE m.asset_id = a.id AND m.proximo_mantenimiento IS NOT NULL
                 ORDER BY m.proximo_mantenimiento DESC), NULL
            ) AS proximo_mantenimiento,
            ISNULL(
                (SELECT COUNT(*) FROM dbo.alert al WHERE al.asset_id = a.id), 0
            ) AS total_alertas,
            d.nombre AS departamento
        FROM dbo.asset a
        LEFT JOIN dbo.asset_type  at ON a.asset_type_id = at.id
        LEFT JOIN dbo.department   d ON a.department_id  = d.id
        WHERE a.estado <> 'baja'
        ORDER BY a.id
    """

    df = pd.read_sql(query_activos, conn)
    return df

# ── Feature Engineering ───────────────────────────────────────
def build_features(df):
    """Construye las características para el modelo ML"""

    # Encoders para variables categóricas
    le_categoria = LabelEncoder()
    le_estado    = LabelEncoder()
    le_tipo      = LabelEncoder()

    df['categoria_enc'] = le_categoria.fit_transform(df['categoria'].fillna('otro'))
    df['estado_enc']    = le_estado.fit_transform(df['estado'].fillna('activo'))
    df['tipo_enc']      = le_tipo.fit_transform(df['tipo'].fillna('otro'))

    # Features numéricas
    features = [
        'edad_dias',
        'dias_sin_garantia',
        'total_mantenimientos',
        'mantenimientos_correctivos',
        'mantenimientos_preventivos',
        'costo_total_mantenimiento',
        'dias_desde_ultimo_mantenimiento',
        'total_alertas',
        'categoria_enc',
        'estado_enc',
        'tipo_enc',
    ]

    # Rellenar nulos
    for f in features:
        df[f] = pd.to_numeric(df[f], errors='coerce').fillna(0)

    # Variable objetivo: 1 = alto riesgo de fallo
    # Criterios heurísticos basados en el historial
    df['riesgo_binario'] = (
        (df['mantenimientos_correctivos'] >= 2) |
        (df['dias_desde_ultimo_mantenimiento'] > 365) |
        (df['dias_sin_garantia'] > 365) |
        (df['total_alertas'] >= 3) |
        (df['edad_dias'] > 1825)  # más de 5 años
    ).astype(int)

    return df, features

# ── Modelo de clasificación (probabilidad de fallo) ───────────
def train_classifier(df, features):
    """Entrena RandomForest para probabilidad de fallo"""

    X = df[features]
    y = df['riesgo_binario']

    # Si hay muy pocos datos, usar reglas directas
    if len(df) < 10:
        return None, None

    # Manejo de clases desbalanceadas
    class_counts = y.value_counts()
    if len(class_counts) < 2:
        return None, None

    # Solo usar stratify si cada clase tiene al menos 2 muestras
    min_class_count = class_counts.min()
    use_stratify    = y if min_class_count >= 2 else None
    test_size       = 0.2 if len(X) >= 20 else 0.1

    try:
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42, stratify=use_stratify
        )
    except ValueError:
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42
        )

    model = RandomForestClassifier(
        n_estimators=100,
        max_depth=6,
        random_state=42,
        class_weight='balanced'
    )
    model.fit(X_train, y_train)

    accuracy = accuracy_score(y_test, model.predict(X_test)) if len(X_test) > 0 else 0

    return model, accuracy

# ── Modelo de regresión (días al próximo mantenimiento) ───────
def calcular_proximo_mantenimiento(row):
    """Estima fecha del próximo mantenimiento basado en el historial"""

    # Si ya tiene fecha programada, usarla
    if row.get('proximo_mantenimiento') and pd.notna(row['proximo_mantenimiento']):
        fecha = pd.to_datetime(row['proximo_mantenimiento'])
        dias  = (fecha - datetime.now()).days
        return max(0, dias), fecha.strftime('%Y-%m-%d')

    # Estimación basada en tipo de activo y edad
    intervalos = {
        'infraestructura':  180,  # cada 6 meses
        'computacion':      365,  # anual
        'comunicaciones':   365,  # anual
        'periferico':       180,  # cada 6 meses
        'otro':             365,
    }

    categoria = row.get('categoria', 'otro') or 'otro'
    intervalo = intervalos.get(categoria, 365)

    dias_desde_ultimo = row.get('dias_desde_ultimo_mantenimiento', 0) or 0
    dias_restantes    = max(0, intervalo - dias_desde_ultimo)
    fecha_estimada    = (datetime.now() + timedelta(days=dias_restantes)).strftime('%Y-%m-%d')

    return int(dias_restantes), fecha_estimada

# ── Cálculo de score de riesgo ────────────────────────────────
def calcular_score_riesgo(row, model, features):
    """Calcula probabilidad de fallo de 0 a 100"""

    if model is not None:
        try:
            X = pd.DataFrame([row[features]])
            for f in features:
                X[f] = pd.to_numeric(X[f], errors='coerce').fillna(0)
            prob = model.predict_proba(X)[0]
            # prob[1] = probabilidad de riesgo alto
            return round(float(prob[1]) * 100, 1)
        except Exception:
            pass

    # Fallback: scoring por reglas cuando no hay suficientes datos
    score = 0

    edad_dias = float(row.get('edad_dias', 0) or 0)
    mant_corr = float(row.get('mantenimientos_correctivos', 0) or 0)
    dias_sin  = float(row.get('dias_desde_ultimo_mantenimiento', 0) or 0)
    sin_gar   = float(row.get('dias_sin_garantia', 0) or 0)
    alertas   = float(row.get('total_alertas', 0) or 0)

    # Edad del equipo (máx 25 puntos)
    if edad_dias > 1825:    score += 25  # más de 5 años
    elif edad_dias > 1095:  score += 15  # más de 3 años
    elif edad_dias > 365:   score += 5

    # Mantenimientos correctivos (máx 30 puntos)
    score += min(30, mant_corr * 10)

    # Tiempo sin mantenimiento (máx 25 puntos)
    if dias_sin > 730:    score += 25
    elif dias_sin > 365:  score += 15
    elif dias_sin > 180:  score += 8

    # Sin garantía (máx 10 puntos)
    if sin_gar > 365: score += 10
    elif sin_gar > 0: score += 5

    # Alertas activas (máx 10 puntos)
    score += min(10, alertas * 3)

    return round(min(100.0, score), 1)

# ── Nivel de riesgo ───────────────────────────────────────────
def nivel_riesgo(score):
    if score >= 70: return 'ALTO'
    if score >= 40: return 'MEDIO'
    return 'BAJO'

def color_riesgo(score):
    if score >= 70: return 'danger'
    if score >= 40: return 'warning'
    return 'success'

# ── Función principal ─────────────────────────────────────────
def analizar(asset_id=None):
    try:
        conn = get_connection()
    except Exception as e:
        print(json.dumps({"error": f"Error de conexión: {str(e)}"}))
        sys.exit(1)

    try:
        df = extract_data(conn)
    except Exception as e:
        print(json.dumps({"error": f"Error al leer datos: {str(e)}"}))
        sys.exit(1)
    finally:
        conn.close()

    if df.empty:
        print(json.dumps({"error": "No hay activos en la base de datos"}))
        sys.exit(1)

    # Preparar features
    df, features = build_features(df)

    # Entrenar modelo
    model, accuracy = train_classifier(df, features)

    # Calcular predicciones para cada activo
    resultados = []
    for _, row in df.iterrows():
        score          = calcular_score_riesgo(row, model, features)
        nivel          = nivel_riesgo(score)
        color          = color_riesgo(score)
        dias_prox, fecha_prox = calcular_proximo_mantenimiento(row)

        resultado = {
            'id':                     int(row['id']),
            'nombre':                 str(row['nombre']),
            'tipo':                   str(row['tipo']),
            'categoria':              str(row['categoria']),
            'marca':                  str(row['marca']),
            'estado':                 str(row['estado']),
            'departamento':           str(row['departamento'] or '—'),
            'probabilidad_fallo':     score,
            'nivel_riesgo':           nivel,
            'color_riesgo':           color,
            'edad_dias':              int(row['edad_dias'] or 0),
            'edad_anos':              round(float(row['edad_dias'] or 0) / 365, 1),
            'total_mantenimientos':   int(row['total_mantenimientos'] or 0),
            'mant_correctivos':       int(row['mantenimientos_correctivos'] or 0),
            'costo_mantenimiento':    float(row['costo_total_mantenimiento'] or 0),
            'dias_sin_mantenimiento': int(row['dias_desde_ultimo_mantenimiento'] or 0),
            'dias_proximo_mant':      dias_prox,
            'fecha_proximo_mant':     fecha_prox,
            'total_alertas':          int(row['total_alertas'] or 0),
            'garantia_vencida':       bool((row['dias_sin_garantia'] or 0) > 0),
        }
        resultados.append(resultado)

    # Ordenar por probabilidad de fallo (mayor riesgo primero)
    resultados.sort(key=lambda x: x['probabilidad_fallo'], reverse=True)

    # Filtrar por asset_id si se especificó
    if asset_id:
        resultados = [r for r in resultados if r['id'] == asset_id]

    # Resumen global
    todos_scores = [r['probabilidad_fallo'] for r in resultados]
    resumen = {
        'total_activos':     len(resultados),
        'riesgo_alto':       sum(1 for r in resultados if r['nivel_riesgo'] == 'ALTO'),
        'riesgo_medio':      sum(1 for r in resultados if r['nivel_riesgo'] == 'MEDIO'),
        'riesgo_bajo':       sum(1 for r in resultados if r['nivel_riesgo'] == 'BAJO'),
        'score_promedio':    round(float(np.mean(todos_scores)), 1) if todos_scores else 0,
        'precision_modelo':  round(float(accuracy) * 100, 1) if accuracy else None,
        'generado_en':       datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'top_riesgo':        resultados[:5],  # Top 5 de mayor riesgo
    }

    output = {
        'resumen':    resumen,
        'activos':    resultados,
        'modelo':     'RandomForest' if model else 'Reglas heurísticas',
    }

    print(json.dumps(output, ensure_ascii=False, default=str))

# ── Entry point ───────────────────────────────────────────────
if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='TechAsset Pro — IA Predictiva')
    parser.add_argument('--asset_id', type=int, help='ID de activo específico', default=None)
    args = parser.parse_args()
    analizar(args.asset_id)
