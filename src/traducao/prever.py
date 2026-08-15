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
        os.path.isfile(
            CAMINHO_MODELO
        )
        and
        os.path.isfile(
            CAMINHO_LABELS
        )
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

    novas_labels = np.asarray(
        novas_labels
    ).reshape(
        -1
    )

    quantidade_saidas = int(
        novo_modelo.output_shape[
            -1
        ]
    )

    if (
        len(
            novas_labels
        )
        !=
        quantidade_saidas
    ):

        raise RuntimeError(
            "O modelo ativo e o arquivo labels.npy "
            "não pertencem à mesma versão. "
            f"Saídas do modelo: {quantidade_saidas}. "
            f"Labels: {len(novas_labels)}."
        )

    modelo = novo_modelo
    labels = novas_labels
    versao_carregada = versao_atual

    print(
        "Modelo ativo carregado:",
        versao_carregada
    )

    print(
        "Labels ativas:",
        [
            str(
                item
            )
            for item in labels
        ]
    )


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


def prever(
    sequencia
):

    carregar_modelo()

    pred = modelo.predict(
        sequencia,
        verbose=0
    )[0]

    if (
        len(
            pred
        )
        !=
        len(
            labels
        )
    ):

        raise RuntimeError(
            "A quantidade de saídas da previsão não corresponde às labels ativas."
        )

    top = np.argsort(
        pred
    )[::-1][
        :min(
            10,
            len(
                pred
            )
        )
    ]

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
        top[
            0
        ]
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
