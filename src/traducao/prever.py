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

CAMINHO_MODELO = os.path.join(
    BASE_DIR,
    "modelo_gestos.keras"
)

CAMINHO_LABELS = os.path.join(
    BASE_DIR,
    "labels.npy"
)

CAMINHO_VERSAO = os.path.join(
    BASE_DIR,
    "modelo_ativo.version"
)


modelo = None
labels = None
versao_carregada = None


def modelo_disponivel():

    return (
        os.path.isfile(CAMINHO_MODELO)
        and
        os.path.isfile(CAMINHO_LABELS)
    )


def obter_versao():

    if not os.path.isfile(
        CAMINHO_VERSAO
    ):
        return "sem-versao"

    try:

        with open(
            CAMINHO_VERSAO,
            "r",
            encoding="utf-8"
        ) as arquivo:

            return arquivo.read().strip()

    except OSError:

        return "sem-versao"


def carregar_modelo():

    global modelo
    global labels
    global versao_carregada

    versao_atual = obter_versao()

    if (
        modelo is not None
        and
        labels is not None
        and
        versao_carregada == versao_atual
    ):
        return

    if not os.path.isfile(
        CAMINHO_MODELO
    ):

        raise RuntimeError(
            "O arquivo modelo_gestos.keras não foi encontrado. "
            "Treine ou ative um modelo antes de realizar traduções."
        )

    if not os.path.isfile(
        CAMINHO_LABELS
    ):

        raise RuntimeError(
            "O arquivo labels.npy não foi encontrado. "
            "Treine ou ative um modelo antes de realizar traduções."
        )

    novo_modelo = load_model(
        CAMINHO_MODELO
    )

    novas_labels = np.load(
        CAMINHO_LABELS,
        allow_pickle=True
    )

    modelo = novo_modelo
    labels = novas_labels
    versao_carregada = versao_atual


def recarregar_modelo():

    global modelo
    global labels
    global versao_carregada

    modelo = None
    labels = None
    versao_carregada = None

    carregar_modelo()


def descarregar_modelo():

    global modelo
    global labels
    global versao_carregada

    modelo = None
    labels = None
    versao_carregada = None


def prever(sequencia):

    carregar_modelo()

    print(
        sequencia.shape
    )

    pred = modelo.predict(
        sequencia,
        verbose=0
    )[0]

    top = np.argsort(
        pred
    )[::-1][:10]

    print(
        "\n========================"
    )

    for indice in top:

        nome_gesto = str(
            labels[
                indice
            ]
        )

        confianca = float(
            pred[
                indice
            ]
        )

        print(
            f"{nome_gesto:15s} -> "
            f"{confianca * 100:.2f}%"
        )

    print(
        "========================\n"
    )

    indice_principal = int(
        top[0]
    )

    gesto = str(
        labels[
            indice_principal
        ]
    )

    confianca = float(
        pred[
            indice_principal
        ]
    )

    return (
        gesto,
        confianca
    )
