import os
import numpy as np


BASE_DIR = os.path.dirname(
    os.path.dirname(
        os.path.dirname(
            os.path.abspath(__file__)
        )
    )
)

SCALER_MEAN_PATH = os.path.join(
    BASE_DIR,
    "scaler_mean.npy"
)

SCALER_SCALE_PATH = os.path.join(
    BASE_DIR,
    "scaler_scale.npy"
)


scaler_mean = None
scaler_scale = None


def carregar_scaler():

    global scaler_mean
    global scaler_scale

    if (
        scaler_mean is not None and
        scaler_scale is not None
    ):
        return

    if not os.path.isfile(SCALER_MEAN_PATH):
        raise RuntimeError(
            "O arquivo scaler_mean.npy não foi encontrado. "
            "Treine o modelo antes de realizar traduções."
        )

    if not os.path.isfile(SCALER_SCALE_PATH):
        raise RuntimeError(
            "O arquivo scaler_scale.npy não foi encontrado. "
            "Treine o modelo antes de realizar traduções."
        )

    scaler_mean = np.load(
        SCALER_MEAN_PATH
    )

    scaler_scale = np.load(
        SCALER_SCALE_PATH
    )

    scaler_scale[
        scaler_scale == 0
    ] = 1


def recarregar_scaler():

    global scaler_mean
    global scaler_scale

    scaler_mean = None
    scaler_scale = None

    carregar_scaler()


def scaler_disponivel():

    return (
        os.path.isfile(SCALER_MEAN_PATH) and
        os.path.isfile(SCALER_SCALE_PATH)
    )


def normalizar_mao(mao):

    wrist = mao.landmark[0]
    middle = mao.landmark[9]

    escala = np.sqrt(
        (middle.x - wrist.x) ** 2 +
        (middle.y - wrist.y) ** 2 +
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


def normalizar(sequencia):

    carregar_scaler()

    sequencia = (
        sequencia - scaler_mean
    ) / scaler_scale

    return sequencia.astype(
        np.float32
    )