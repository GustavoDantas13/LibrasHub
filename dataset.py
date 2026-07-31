import cv2
import os
import numpy as np
import mediapipe as mp

# ============================================
# CONFIGURAÇÕES
# ============================================

DATASET_DIR = "libraas"
OUTPUT_DIR = "dataset_processado"

SEQUENCE_LENGTH = 30

# ---------- MÃOS ----------

HAND_LANDMARKS = 63
TOTAL_HANDS = 2

# ---------- POSE ----------

POSE_POINTS = 8
POSE_VALUES = POSE_POINTS * 4

FEATURES = HAND_LANDMARKS * TOTAL_HANDS + POSE_VALUES

os.makedirs(OUTPUT_DIR, exist_ok=True)

# ============================================
# MEDIAPIPE
# ============================================

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


# ============================================
# NORMALIZAÇÃO DA MÃO
# ============================================

def normalizar_mao(mao):

    wrist = mao.landmark[0]
    middle = mao.landmark[9]

    escala = np.sqrt(

        (middle.x - wrist.x)**2 +

        (middle.y - wrist.y)**2 +

        (middle.z - wrist.z)**2

    )

    escala = max(escala, 1e-6)

    pontos = []

    for lm in mao.landmark:

        pontos.extend([

            (lm.x - wrist.x) / escala,

            (lm.y - wrist.y) / escala,

            (lm.z - wrist.z) / escala

        ])

    return pontos


def normalizar_mao(mao): 
    wrist = mao.landmark[0] 
    middle = mao.landmark[9]

    escala = np.sqrt( 
        (middle.x - wrist.x) ** 2 + (middle.y - wrist.y) ** 2 + (middle.z - wrist.z) ** 2 ) 
        
    escala = max(escala, 1e-6) 
        
    pontos = [] 
        
    for lm in mao.landmark: 
        pontos.extend([ 
            (lm.x - wrist.x) / escala, 
            (lm.y - wrist.y) / escala, 
            (lm.z - wrist.z) / escala 
            ]) 
            
    return pontos



# ============================================
# EXTRAÇÃO DOS LANDMARKS
# ============================================

def extrair_landmarks(frame):

    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

    resultado_maos = hands.process(rgb)
    resultado_pose = pose.process(rgb)

    vetor = []

    # =====================================
    # MÃOS
    # =====================================

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

    # =====================================
    # POSE
    # =====================================

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

# ============================================
# SUAVIZAÇÃO TEMPORAL
# ============================================

def suavizar(sequencia):

    if len(sequencia) <= 1:
        return sequencia

    nova = sequencia.copy()

    for i in range(1, len(nova)):

        nova[i] = (

            0.7 * nova[i]

            +

            0.3 * nova[i - 1]

        )

    return nova

# ============================================
# INTERPOLAÇÃO TEMPORAL
# ============================================

def interpolar(frames):

    if len(frames) == 0:
        return None

    frames = np.array(frames, dtype=np.float32)

    if len(frames) == 1:

        return np.repeat(

            frames,

            SEQUENCE_LENGTH,

            axis=0

        )

    x_original = np.linspace(

        0,

        1,

        len(frames)

    )

    x_novo = np.linspace(

        0,

        1,

        SEQUENCE_LENGTH

    )

    novo = np.zeros(

        (

            SEQUENCE_LENGTH,

            FEATURES

        ),

        dtype=np.float32

    )

    for i in range(FEATURES):

        novo[:, i] = np.interp(

            x_novo,

            x_original,

            frames[:, i]

        )

    return suavizar(novo)


# ============================================
# IMAGEM
# ============================================

def processar_imagem(caminho):

    img = cv2.imread(caminho)

    if img is None:
        return None

    frame = extrair_landmarks(img)

    return np.repeat(

        frame[np.newaxis, :],

        SEQUENCE_LENGTH,

        axis=0

    )
# ============================================
# VÍDEO
# ============================================

def processar_video(caminho):

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

        landmarks = extrair_landmarks(frame)

        # Se não detectou nenhuma mão,
        # reutiliza o último frame válido
        if np.allclose(landmarks, 0):

            landmarks = ultimo_valido.copy()

        else:

            ultimo_valido = landmarks.copy()

        sequencia.append(landmarks)

    cap.release()

    return np.asarray(sequencia, dtype=np.float32)
# ============================================
# DATA AUGMENTATION
# ============================================

def augmentar(amostra):

    nova = amostra.copy()

    # pequeno ruído gaussiano

    ruido = np.random.normal(

        0,

        0.003,

        nova.shape

    )

    nova += ruido

    # pequena alteração de escala

    escala = np.random.uniform(

        0.97,

        1.03

    )

    nova *= escala

    return nova.astype(np.float32)

# ============================================
# PROCESSAMENTO
# ============================================

total_amostras = 0

for gesto in sorted(os.listdir(DATASET_DIR)):

    pasta = os.path.join(DATASET_DIR, gesto)

    if not os.path.isdir(pasta):
        continue

    print(f"\n📂 {gesto}")

    amostras = []

    arquivos = sorted(os.listdir(pasta))

    for arquivo in arquivos:

        caminho = os.path.join(pasta, arquivo)

        ext = os.path.splitext(arquivo)[1].lower()

        try:

            if ext in [

                ".jpg",

                ".jpeg",

                ".png"

            ]:

                seq = processar_imagem(caminho)

            elif ext in [

                ".mp4",

                ".avi",

                ".mov",

                ".mkv"

            ]:

                seq = processar_video(caminho)

            else:

                continue

            if seq is None:

                continue

            amostras.append(seq)

            # augmentation
            amostras.append(

                augmentar(seq)

            )

            total_amostras += 2

            print("   ✔", arquivo)

        except Exception as erro:

            print(

                "   ✖",

                arquivo,

                erro

            )

    if len(amostras):

        amostras = np.array(

            amostras,

            dtype=np.float32

        )

        np.save(

            os.path.join(

                OUTPUT_DIR,

                gesto + ".npy"

            ),

            amostras

        )

        print()

        print(

            f"💾 {gesto}"

        )

        print(

            "   Amostras:",

            len(amostras)

        )

        print(

            "   Shape:",

            amostras.shape

        )

    else:

        print(

            "⚠ Nenhuma amostra válida."

        )

# ============================================
# FINALIZAÇÃO
# ============================================

hands.close()
pose.close()

print("\n=================================")
print("Dataset criado com sucesso!")
print("=================================")
print(f"Total de amostras: {total_amostras}")
print(f"Features por frame: {FEATURES}")
print(f"Frames por sequência: {SEQUENCE_LENGTH}")
print("=================================")