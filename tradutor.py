from flask import Flask, render_template, request, jsonify

import os
import cv2
import numpy as np
import mediapipe as mp
import uuid
import time


from src.traducao.processar_imagem import processar_imagem_traducao
from src.traducao.processar_video import processar_video_traducao
from src.traducao.prever import prever
from src.traducao.extrair_landmarks import extrair_landmarks_traducao
from src.traducao.normalizar import normalizar

# Flask (mexe nesse aqui também não)


app = Flask(__name__)


# ! Configurações


SEQUENCE_LENGTH = 30

FEATURES = 158

# Buffer

buffer = []

frase = []

ultimo_gesto = ""

gesto_confirmado = None
contador_confirmacao = 0

CONFIANCA_MINIMA = 0.95
PREDICOES_CONSECUTIVAS = 5


# * Analizar

@app.route("/analisar", methods=["POST"])
def analisar():

    print(">>> Requisição recebida!")

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

        BASE_DIR = os.path.dirname(os.path.abspath(__file__))

        UPLOAD_FOLDER = os.path.join(BASE_DIR, "uploads")

        os.makedirs(UPLOAD_FOLDER, exist_ok=True)

        # Faltava esta linha
        caminho = os.path.join(UPLOAD_FOLDER, nome)

        arquivo.save(caminho)

        try:

            if extensao in [".jpg", ".jpeg", ".png"]:
                sequencia = processar_imagem_traducao(caminho)
            else:
                sequencia = processar_video_traducao(caminho)

            if sequencia is None:

                resultados.append({
                    "arquivo": arquivo.filename,
                    "erro": "Não foi possível processar."
                })

                continue

            gesto, confianca = prever(sequencia)

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

    landmarks = extrair_landmarks_traducao(frame)

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

    gesto, confianca = prever(sequencia)

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