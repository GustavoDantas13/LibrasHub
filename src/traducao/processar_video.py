import cv2
import numpy as np

from src.traducao.extrair_landmarks import (
    extrair_landmarks_dataset,
    extrair_landmarks_traducao
)

from src.traducao.normalizar import (
    normalizar
)


SEQUENCE_LENGTH = 30

FEATURES = 158


def processar_video_dataset(
    caminho,
    cancelar_evento=None
):

    cap = cv2.VideoCapture(
        caminho
    )

    try:

        if not cap.isOpened():

            return None


        total_frames = int(
            cap.get(
                cv2.CAP_PROP_FRAME_COUNT
            )
        )


        if total_frames <= 0:

            return None


        indices = np.linspace(
            0,
            total_frames - 1,
            SEQUENCE_LENGTH,
            dtype=int
        )


        sequencia = []


        ultimo_valido = np.zeros(
            FEATURES,
            dtype=np.float32
        )


        for indice in indices:

            if (
                cancelar_evento is not None
                and
                cancelar_evento.is_set()
            ):

                return None


            cap.set(
                cv2.CAP_PROP_POS_FRAMES,
                int(
                    indice
                )
            )


            ok, frame = cap.read()


            if (
                cancelar_evento is not None
                and
                cancelar_evento.is_set()
            ):

                return None


            if not ok:

                sequencia.append(
                    ultimo_valido.copy()
                )

                continue


            landmarks = (
                extrair_landmarks_dataset(
                    frame
                )
            )


            if (
                cancelar_evento is not None
                and
                cancelar_evento.is_set()
            ):

                return None


            if np.allclose(
                landmarks,
                0
            ):

                landmarks = (
                    ultimo_valido.copy()
                )

            else:

                ultimo_valido = (
                    landmarks.copy()
                )


            sequencia.append(
                landmarks
            )


        return np.asarray(
            sequencia,
            dtype=np.float32
        )


    finally:

        cap.release()


def processar_video_traducao(
    caminho
):

    cap = cv2.VideoCapture(
        caminho
    )

    try:

        if not cap.isOpened():

            return None


        total_frames = int(
            cap.get(
                cv2.CAP_PROP_FRAME_COUNT
            )
        )


        if total_frames <= 0:

            return None


        indices = np.linspace(
            0,
            total_frames - 1,
            SEQUENCE_LENGTH,
            dtype=int
        )


        sequencia = []


        ultimo_valido = np.zeros(
            FEATURES,
            dtype=np.float32
        )


        for indice in indices:

            cap.set(
                cv2.CAP_PROP_POS_FRAMES,
                int(
                    indice
                )
            )


            ok, frame = cap.read()


            if not ok:

                sequencia.append(
                    ultimo_valido.copy()
                )

                continue


            landmarks = (
                extrair_landmarks_traducao(
                    frame
                )
            )


            if (
                landmarks is None
                or
                np.allclose(
                    landmarks,
                    0
                )
            ):

                landmarks = (
                    ultimo_valido.copy()
                )

            else:

                ultimo_valido = (
                    landmarks.copy()
                )


            sequencia.append(
                landmarks
            )


        sequencia = np.asarray(
            sequencia,
            dtype=np.float32
        )


        sequencia = normalizar(
            sequencia
        )


        return sequencia.reshape(
            1,
            SEQUENCE_LENGTH,
            FEATURES
        )


    finally:

        cap.release()
