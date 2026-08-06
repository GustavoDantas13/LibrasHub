import os
import numpy as np

from tensorflow.keras.models import load_model


BASE_DIR = os.path.dirname(
    os.path.dirname(
        os.path.dirname(
            os.path.abspath(__file__)
        )
    )
)

MODELO_PATH = os.path.join(
    BASE_DIR,
    "modelo_gestos.keras"
)

LABELS_PATH = os.path.join(
    BASE_DIR,
    "labels.npy"
)


modelo = None
labels = None


def modelo_disponivel():

    return (
        os.path.isfile(MODELO_PATH)
        and
        os.path.isfile(LABELS_PATH)
    )


def carregar_modelo():

    global modelo
    global labels

    if (
        modelo is not None
        and
        labels is not None
    ):
        return

    if not os.path.isfile(MODELO_PATH):
        raise RuntimeError(
            "O arquivo modelo_gestos.keras não foi encontrado. "
            "Treine o modelo antes de realizar traduções."
        )

    if not os.path.isfile(LABELS_PATH):
        raise RuntimeError(
            "O arquivo labels.npy não foi encontrado. "
            "Treine o modelo antes de realizar traduções."
        )

    modelo = load_model(
        MODELO_PATH
    )

    labels = np.load(
        LABELS_PATH,
        allow_pickle=True
    )


def recarregar_modelo():

    global modelo
    global labels

    modelo = None
    labels = None

    carregar_modelo()


def descarregar_modelo():

    global modelo
    global labels

    modelo = None
    labels = None


def prever(sequencia):

    carregar_modelo()

    print(sequencia.shape)

    pred = modelo.predict(
        sequencia,
        verbose=0
    )[0]

    top = np.argsort(
        pred
    )[::-1][:10]

    print("\n========================")

    for indice in top:

        nome_gesto = str(
            labels[indice]
        )

        confianca = float(
            pred[indice]
        )

        print(
            f"{nome_gesto:15s} -> "
            f"{confianca * 100:.2f}%"
        )

    print("========================\n")

    indice_principal = int(
        top[0]
    )

    gesto = str(
        labels[indice_principal]
    )

    confianca = float(
        pred[indice_principal]
    )

    return gesto, confianca