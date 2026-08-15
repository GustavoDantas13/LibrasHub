import os
import numpy as np


BASE_DIR = os.path.dirname(
    os.path.dirname(
        os.path.dirname(
            os.path.abspath(__file__)
        )
    )
)


CAMINHO_MEAN = os.path.join(
    BASE_DIR,
    "scaler_mean.npy"
)


CAMINHO_SCALE = os.path.join(
    BASE_DIR,
    "scaler_scale.npy"
)


CAMINHO_VERSAO = os.path.join(
    BASE_DIR,
    "modelo_ativo.version"
)


scaler_mean = None
scaler_scale = None
versao_scaler = None


def scaler_disponivel():

    return (
        os.path.isfile(
            CAMINHO_MEAN
        )
        and
        os.path.isfile(
            CAMINHO_SCALE
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


def carregar_scaler():

    global scaler_mean
    global scaler_scale
    global versao_scaler

    versao_atual = obter_versao()

    if (
        scaler_mean is not None
        and
        scaler_scale is not None
        and
        versao_scaler == versao_atual
    ):

        return

    if not os.path.isfile(
        CAMINHO_MEAN
    ):

        raise RuntimeError(
            "O arquivo scaler_mean.npy não foi encontrado. "
            "Treine ou ative um modelo antes de realizar traduções."
        )

    if not os.path.isfile(
        CAMINHO_SCALE
    ):

        raise RuntimeError(
            "O arquivo scaler_scale.npy não foi encontrado. "
            "Treine ou ative um modelo antes de realizar traduções."
        )

    novo_mean = np.load(
        CAMINHO_MEAN
    )

    novo_scale = np.load(
        CAMINHO_SCALE
    )

    novo_mean = np.asarray(
        novo_mean,
        dtype=np.float32
    ).reshape(
        -1
    )

    novo_scale = np.asarray(
        novo_scale,
        dtype=np.float32
    ).reshape(
        -1
    )

    if (
        novo_mean.shape
        !=
        novo_scale.shape
    ):

        raise RuntimeError(
            "Os arquivos scaler_mean.npy e scaler_scale.npy são incompatíveis."
        )

    novo_scale[
        novo_scale == 0
    ] = 1

    scaler_mean = novo_mean
    scaler_scale = novo_scale
    versao_scaler = versao_atual

    print(
        "Scaler ativo carregado:",
        versao_scaler,
        "features:",
        len(
            scaler_mean
        )
    )


def recarregar_scaler():

    global scaler_mean
    global scaler_scale
    global versao_scaler

    scaler_mean = None
    scaler_scale = None
    versao_scaler = None

    carregar_scaler()


def descarregar_scaler():

    global scaler_mean
    global scaler_scale
    global versao_scaler

    scaler_mean = None
    scaler_scale = None
    versao_scaler = None


def normalizar_mao(
    mao
):

    wrist = mao.landmark[
        0
    ]

    middle = mao.landmark[
        9
    ]

    escala = np.sqrt(
        (middle.x - wrist.x) ** 2
        +
        (middle.y - wrist.y) ** 2
        +
        (middle.z - wrist.z) ** 2
    )

    escala = max(
        escala,
        1e-6
    )

    pontos = []

    for lm in mao.landmark:

        pontos.extend([
            (lm.x - wrist.x) / escala,
            (lm.y - wrist.y) / escala,
            (lm.z - wrist.z) / escala
        ])

    return pontos


def normalizar(
    sequencia
):

    carregar_scaler()

    sequencia = np.asarray(
        sequencia,
        dtype=np.float32
    )

    if (
        sequencia.shape[
            -1
        ]
        !=
        len(
            scaler_mean
        )
    ):

        raise RuntimeError(
            "A sequência recebida não é compatível com o scaler ativo. "
            f"Features da sequência: {sequencia.shape[-1]}. "
            f"Features do scaler: {len(scaler_mean)}."
        )

    sequencia = (
        sequencia
        -
        scaler_mean
    ) / scaler_scale

    return sequencia.astype(
        np.float32
    )
