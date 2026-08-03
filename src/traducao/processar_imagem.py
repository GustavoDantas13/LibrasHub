import cv2
import numpy as np

from src.traducao.extrair_landmarks import extrair_landmarks_dataset, extrair_landmarks_traducao
from src.traducao.normalizar import normalizar


SEQUENCE_LENGTH = 30
FEATURES = 158


def processar_imagem_dataset(caminho):

    img = cv2.imread(caminho)

    if img is None:
        return None

    frame = extrair_landmarks_dataset(img)

    return np.repeat(

        frame[np.newaxis, :],

        SEQUENCE_LENGTH,

        axis=0

    )


def processar_imagem_traducao(caminho):

    imagem = cv2.imread(caminho)

    if imagem is None:
        return None

    frame = extrair_landmarks_traducao(imagem)

    sequencia = np.repeat(

        frame[np.newaxis, :],

        SEQUENCE_LENGTH,

        axis=0

    )

    sequencia = normalizar(sequencia)


    return sequencia.reshape(

        1,

        SEQUENCE_LENGTH,

        FEATURES

    )

