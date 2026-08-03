import cv2
import numpy as np
import mediapipe as mp


# Funções

from src.traducao.normalizar import normalizar_mao

# ! Configurações

SEQUENCE_LENGTH = 30

HAND_LANDMARKS = 63
TOTAL_HANDS = 2
POSE_POINTS = 8
POSE_VALUES = POSE_POINTS * 4

FEATURES = HAND_LANDMARKS * TOTAL_HANDS + POSE_VALUES

# Mediapipe

mp_hands = mp.solutions.hands
mp_pose = mp.solutions.pose

hands = mp_hands.Hands(

    static_image_mode=False,

    max_num_hands=2,

    model_complexity=1,
    min_detection_confidence=0.6,
    min_tracking_confidence=0.6

)

pose = mp_pose.Pose(

    static_image_mode=False,

    model_complexity=1,
    smooth_landmarks=True,
    min_detection_confidence=0.6,
    min_tracking_confidence=0.6

)


# Dataset


def extrair_landmarks_dataset(frame):

    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

    resultado_maos = hands.process(rgb)
    resultado_pose = pose.process(rgb)

    vetor = []

    
    # Mãos
    

    maos = []

    if (
        resultado_maos.multi_hand_landmarks
        and
        resultado_maos.multi_handedness
    ):

        pares = list(

            zip(

                resultado_maos.multi_hand_landmarks,

                resultado_maos.multi_handedness

            )

        )

        # sempre Esquerda depois Direita

        pares.sort(

            key=lambda p:

            0 if p[1].classification[0].label == "Left"

            else 1

        )

        for mao, _ in pares[:2]:

            maos.append(

                normalizar_mao(mao)

            )

    while len(maos) < 2:

        maos.append([0] * HAND_LANDMARKS)

    for pontos in maos:

        vetor.extend(pontos)

    
    # Pose
    

    indices = [

        11, 12,      # ombros

        13, 14,      # cotovelos

        15, 16,      # punhos

        23, 24       # quadril

    ]

    if resultado_pose.pose_landmarks:

        lms = resultado_pose.pose_landmarks.landmark

        ombro_esq = lms[11]
        ombro_dir = lms[12]

        cx = (ombro_esq.x + ombro_dir.x) / 2
        cy = (ombro_esq.y + ombro_dir.y) / 2
        cz = (ombro_esq.z + ombro_dir.z) / 2

        escala = np.sqrt(

            (ombro_esq.x - ombro_dir.x) ** 2 +

            (ombro_esq.y - ombro_dir.y) ** 2 +

            (ombro_esq.z - ombro_dir.z) ** 2

        )

        escala = max(escala, 1e-6)

        for idx in indices:

            lm = lms[idx]

            vetor.extend([

                (lm.x - cx) / escala,

                (lm.y - cy) / escala,

                (lm.z - cz) / escala,

                lm.visibility

            ])

    else:

        vetor.extend([0] * POSE_VALUES)

    return np.array(vetor, dtype=np.float32)





# Tradução

def extrair_landmarks_traducao(frame):

    if frame is None:
        return np.zeros(FEATURES, dtype=np.float32)

    if frame.size == 0:
        return np.zeros(FEATURES, dtype=np.float32)

    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    
    resultado_maos = hands.process(rgb)
    resultado_pose = pose.process(rgb)

    vetor = []

    
    # Pose (mãos)

    maos = []

    if (

        resultado_maos.multi_hand_landmarks
        and
        resultado_maos.multi_handedness

    ):

        pares = list(

            zip(

                resultado_maos.multi_hand_landmarks,

                resultado_maos.multi_handedness

            )

        )

        pares.sort(

            key=lambda p:

            0

            if p[1].classification[0].label == "Left"

            else 1

        )

        for mao, _ in pares[:2]:

            maos.append(

                normalizar_mao(mao)

            )

    while len(maos) < 2:

        maos.append([0] * 63)

    for pontos in maos:

        vetor.extend(pontos)

    # Pose (ombros)

    indices = [

        11, 12,

        13, 14,

        15, 16,

        23, 24

    ]

    if resultado_pose.pose_landmarks:
    
            lms = resultado_pose.pose_landmarks.landmark
    
            ombro_esq = lms[11]
            ombro_dir = lms[12]
    
            cx = (ombro_esq.x + ombro_dir.x) / 2
            cy = (ombro_esq.y + ombro_dir.y) / 2
            cz = (ombro_esq.z + ombro_dir.z) / 2
    
            escala = np.sqrt(
    
                (ombro_esq.x - ombro_dir.x) ** 2 +
    
                (ombro_esq.y - ombro_dir.y) ** 2 +
    
                (ombro_esq.z - ombro_dir.z) ** 2
    
            )
    
            escala = max(escala, 1e-6)
    
            for idx in indices:
    
                lm = lms[idx]
    
                vetor.extend([
    
                    (lm.x - cx) / escala,
    
                    (lm.y - cy) / escala,
    
                    (lm.z - cz) / escala,
    
                    lm.visibility
    
                ])
    
    else:

        vetor.extend([0] * 32)

    return np.array(vetor, dtype=np.float32)
