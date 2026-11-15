from __future__ import annotations

import importlib
import json
from pathlib import Path

import numpy as np


ROOT = Path(__file__).resolve().parents[1]
DEPENDENCIAS = {
    "flask": "Flask",
    "cv2": "opencv-contrib-python",
    "mediapipe": "mediapipe",
    "tensorflow": "tensorflow",
    "sklearn": "scikit-learn",
    "werkzeug": "Werkzeug",
}


def main() -> int:
    erros: list[str] = []
    avisos: list[str] = []

    for modulo, pacote in DEPENDENCIAS.items():
        try:
            importlib.import_module(modulo)
        except Exception as exc:
            erros.append(f"Dependência ausente ou incompatível: {pacote} ({exc})")

    arquivos = [
        "modelo_gestos.keras",
        "labels.npy",
        "scaler_mean.npy",
        "scaler_scale.npy",
        "modelo_ativo.version",
    ]
    for nome in arquivos:
        if not (ROOT / nome).is_file():
            erros.append(f"Arquivo obrigatório ausente: {nome}")

    if not erros or all("Dependência" in erro for erro in erros):
        try:
            labels = np.asarray(np.load(ROOT / "labels.npy", allow_pickle=True)).reshape(-1)
            mean = np.asarray(np.load(ROOT / "scaler_mean.npy")).reshape(-1)
            scale = np.asarray(np.load(ROOT / "scaler_scale.npy")).reshape(-1)
            if mean.shape != scale.shape:
                erros.append("scaler_mean.npy e scaler_scale.npy possuem dimensões diferentes.")
            if len(mean) != 158:
                erros.append(f"O scaler possui {len(mean)} features; o serviço espera 158.")
            if len(labels) < 2:
                erros.append("labels.npy não contém classes suficientes.")
            if np.any(scale == 0):
                avisos.append("O scaler contém valores zero; eles serão neutralizados durante o carregamento.")
        except Exception as exc:
            erros.append(f"Falha ao validar artefatos do modelo: {exc}")

    resultado = {
        "ok": not erros,
        "erros": erros,
        "avisos": avisos,
        "proximo_passo": "python tradutor.py" if not erros else "pip install -r requirements.txt",
    }
    print(json.dumps(resultado, ensure_ascii=False, indent=2))
    return 0 if not erros else 1


if __name__ == "__main__":
    raise SystemExit(main())
