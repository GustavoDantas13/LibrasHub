from flask import Flask, render_template, request, jsonify

import os
import cv2
import numpy as np
import mediapipe as mp
import uuid
import time

from tensorflow.keras.models import load_model


# Flask (mexe nesse aqui também não)


app = Flask(__name__)


# ! Configurações


SEQUENCE_LENGTH = 30

FEATURES = 158

UPLOAD_FOLDER = "uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)


# ! Carrega modelo de tradução

modelo = load_model("modelo_gestos.keras")

labels = np.load("labels.npy")

# scaler salvo durante o treinamento
scaler_mean = np.load("scaler_mean.npy")
scaler_scale = np.load("scaler_scale.npy")

# evita divisão por zero
scaler_scale[scaler_scale == 0] = 1



# Buffer

buffer = []

frase = []

ultimo_gesto = ""

gesto_confirmado = None
contador_confirmacao = 0

CONFIANCA_MINIMA = 0.95
PREDICOES_CONSECUTIVAS = 5



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
    min_detection_confidence=0.6,
    min_tracking_confidence=0.6

)

# Interpolação (se pá eu separo em um arquivo separado) 
# (obs: responsavel por dar a sequência de tempo para o sistema)

def interpolar(frames):

    frames = np.array(frames, dtype=np.float32)

    if len(frames) == SEQUENCE_LENGTH:
        return frames

    novo = []

    indices = np.linspace(

        0,

        len(frames) - 1,

        SEQUENCE_LENGTH

    )

    for indice in indices:

        i0 = int(np.floor(indice))
        i1 = min(i0 + 1, len(frames) - 1)

        alpha = indice - i0

        frame = (

            (1 - alpha) * frames[i0] +

            alpha * frames[i1]

        )

        novo.append(frame)

    return np.array(novo, dtype=np.float32)


# Extração de Landmarks (se pá eu separo em um arquivo separado)

def extrair_landmarks(frame):

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


# ? Normalização (se pá eu separo em um arquivo separado)

def normalizar_mao(mao):

    wrist = mao.landmark[0]
    middle = mao.landmark[9]

    escala = np.sqrt(
        (middle.x - wrist.x) ** 2 +
        (middle.y - wrist.y) ** 2 +
        (middle.z - wrist.z) ** 2
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

def normalizar(sequencia):

    sequencia = (
        sequencia - scaler_mean
    ) / scaler_scale

    return sequencia.astype(np.float32)

# ? Processa imagem (se pá eu separo em um arquivo separado)

def processar_imagem(caminho):

    imagem = cv2.imread(caminho)

    if imagem is None:
        return None

    frame = extrair_landmarks(imagem)

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

# ? Processa vídeo (se pá eu separo em um arquivo separado)

def processar_video(caminho):

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

        landmarks = extrair_landmarks(frame)


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

    

# ? Função de previsão de gesto (se pá eu separo em um arquivo separado)

def predizer(sequencia):

    print(sequencia.shape)
    pred = modelo.predict(sequencia, verbose=0)[0]

    top = np.argsort(pred)[::-1][:10]

    print("\n========================")

    for i in top:
        print(f"{labels[i]:15s} -> {pred[i]*100:.2f}%")

    print("========================\n")

    indice = top[0]

    return labels[indice], float(pred[indice])

# * Rotas (Uso do flask para carregar as paginas)

@app.route("/")
def home():
    return render_template("home.html")


@app.route("/leitor")
def leitor():
    return render_template("leitor.html")


@app.route("/fotovideo")
def fotovideo():
    return render_template("fotovideo.html")


@app.route("/ajuda")
def ajuda():
    return render_template("ajuda.html")


# * Analizar

@app.route("/analisar", methods=["POST"])
def analisar():

    arquivos = request.files.getlist("mediaFile")

    if not arquivos:
        return jsonify(
            success=False,
            error="Nenhum arquivo enviado."
        )

    resultados = []

    for arquivo in arquivos:

        extensao = os.path.splitext(arquivo.filename)[1].lower()

        if extensao not in [
            ".jpg", ".jpeg", ".png",
            ".mp4", ".avi", ".mov", ".mkv"
        ]:

            resultados.append({
                "arquivo": arquivo.filename,
                "erro": "Formato não suportado."
            })

            continue

        nome = f"{uuid.uuid4()}{extensao}"
        caminho = os.path.join(UPLOAD_FOLDER, nome)

        arquivo.save(caminho)

        try:

            if extensao in [".jpg", ".jpeg", ".png"]:
                sequencia = processar_imagem(caminho)
            else:
                sequencia = processar_video(caminho)

            if sequencia is None:

                resultados.append({
                    "arquivo": arquivo.filename,
                    "erro": "Não foi possível processar."
                })

                continue

            gesto, confianca = predizer(sequencia)

            resultados.append({
                "arquivo": arquivo.filename,
                "gesto": gesto,
                "confianca": round(confianca * 100, 2)
            })

        except Exception as e:

            resultados.append({
                "arquivo": arquivo.filename,
                "erro": str(e)
            })

        finally:

            if os.path.exists(caminho):
                os.remove(caminho)

    return jsonify(
        success=True,
        resultados=resultados
    )


# * Tempo Real

@app.route("/traducao_tempo_real", methods=["POST"])
def traducao_tempo_real():

    global buffer
    global frase
    global ultimo_gesto
    global gesto_confirmado
    global contador_confirmacao

    inicio = time.time()

    arquivo = request.files["frame"]

    npimg = np.frombuffer(arquivo.read(), np.uint8)

    frame = cv2.imdecode(npimg, cv2.IMREAD_COLOR)

    landmarks = extrair_landmarks(frame)

    if landmarks is None:
        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        })

    buffer.append(landmarks)

    if len(buffer) > SEQUENCE_LENGTH:
        buffer.pop(0)

    if len(buffer) < SEQUENCE_LENGTH:
        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        })

    sequencia = np.array(buffer)

    sequencia = normalizar(sequencia)

    sequencia = sequencia.reshape(
        1,
        SEQUENCE_LENGTH,
        FEATURES
    )

    gesto, confianca = predizer(sequencia)

    print(f"{gesto} -> {confianca:.2%}")

    # ! confiança baixa = descarta
    if confianca < CONFIANCA_MINIMA:

        gesto_confirmado = None
        contador_confirmacao = 0

        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        })

    # mesma predição consecutiva
    if gesto == gesto_confirmado:

        contador_confirmacao += 1

    else:
        gesto_confirmado = gesto
        contador_confirmacao = 1

    if contador_confirmacao < PREDICOES_CONSECUTIVAS:
        return jsonify({
            "status": "analisando",
            "texto": " ".join(frase),
            "gesto": gesto,
            "confirmacao": contador_confirmacao
        })

    print("Confirmação:", gesto_confirmado, contador_confirmacao)

    # ! só aceita após 5 confirmações
    if contador_confirmacao >= PREDICOES_CONSECUTIVAS:

        if gesto != ultimo_gesto:

            frase.append(gesto)

            ultimo_gesto = gesto

            print(">>> GESTO ACEITO:", gesto)

        # ! limpa tudo para começar um novo gesto
        buffer.clear()

        gesto_confirmado = None
        contador_confirmacao = 0
        ultimo_gesto = None

        print("Tempo:", round(time.time() - inicio, 2), "seg")

    return jsonify(
        status="traduzido",
        texto=" ".join(frase)
    )


# * Limpar Tradução em Tempo Real

@app.route("/limpar_traducao", methods=["POST"])
def limpar_traducao():

    global frase
    global buffer
    global ultimo_gesto
    global gesto_confirmado
    global contador_confirmacao

    frase.clear()
    buffer.clear()

    ultimo_gesto = None
    gesto_confirmado = None
    contador_confirmacao = 0

    return jsonify(ok=True)


# ! Não mexe nisso, faz o flask funcionar

if __name__ == "__main__":

    app.run(debug=True, use_reloader=False)