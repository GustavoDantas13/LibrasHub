import cv2
import numpy as np

from src.traducao.extrair_landmarks import extrair_landmarks_dataset, extrair_landmarks_traducao
from src.traducao.normalizar import normalizar


SEQUENCE_LENGTH = 30

FEATURES = 158


def processar_video_dataset(caminho):

    cap = cv2.VideoCapture(caminho)

    if not cap.isOpened():
        return None

    total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))

    if total_frames <= 0:
        cap.release()
        return None

    # Escolhe apenas 30 frames distribuídos pelo vídeo
    indices = np.linspace(
        0,
        total_frames - 1,
        SEQUENCE_LENGTH,
        dtype=int
    )

    sequencia = []

    ultimo_valido = np.zeros(FEATURES, dtype=np.float32)

    for indice in indices:

        cap.set(cv2.CAP_PROP_POS_FRAMES, int(indice))

        ok, frame = cap.read()

        if not ok:
            sequencia.append(ultimo_valido.copy())
            continue

        landmarks = extrair_landmarks_dataset(frame)

        # Se não detectou nenhuma mão,
        # reutiliza o último frame válido
        if np.allclose(landmarks, 0):

            landmarks = ultimo_valido.copy()

        else:

            ultimo_valido = landmarks.copy()

        sequencia.append(landmarks)

    cap.release()

    return np.asarray(sequencia, dtype=np.float32)


def processar_video_traducao(caminho):

    cap = cv2.VideoCapture(caminho)

    if not cap.isOpened():
        return None

    frames = []

    total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))

    if total_frames <= 0:
            cap.release()
            return None
    
        # Escolhe apenas 30 frames distribuídos pelo vídeo
    indices = np.linspace(
        0,
        total_frames - 1,
        SEQUENCE_LENGTH,
        dtype=int
    )

    sequencia = []

    ultimo_valido = np.zeros(FEATURES, dtype=np.float32)

    for indice in indices:

        cap.set(cv2.CAP_PROP_POS_FRAMES, int(indice))

        ok, frame = cap.read()

        if not ok:
            sequencia.append(ultimo_valido.copy())
            continue

        landmarks = extrair_landmarks_traducao(frame)


        # Se não detectou nenhuma mão,
        # reutiliza o último frame válido
        if np.allclose(landmarks, 0):

            landmarks = ultimo_valido.copy()

        else:

            ultimo_valido = landmarks.copy()

        sequencia.append(landmarks)

    cap.release()

    sequencia = np.asarray(
    sequencia,
    dtype=np.float32
    )


    sequencia = normalizar(sequencia)
    
    return sequencia.reshape(
        1,
        SEQUENCE_LENGTH,
        FEATURES
    )

