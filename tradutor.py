from flask import (
    Flask,
    jsonify,
    request
)

import os
import cv2
import shutil
import numpy as np
import uuid
import time

from werkzeug.utils import secure_filename


from src.traducao.processar_imagem import (
    processar_imagem_traducao
)

from src.traducao.processar_video import (
    processar_video_traducao
)

from src.traducao.prever import prever

from src.traducao.extrair_landmarks import (
    extrair_landmarks_traducao
)

from src.traducao.normalizar import normalizar

from src.traducao.dataset import criar_dataset

from src.traducao.treinamento_cnn import (
    cancelar_treinamento,
    iniciar_treinamento,
    obter_status_treinamento
)



# FLASK


app = Flask(__name__)



# CONFIGURAÇÕES


BASE_DIR = os.path.dirname(
    os.path.abspath(__file__)
)

SEQUENCE_LENGTH = 30

FEATURES = 158



# ESTADO DA TRADUÇÃO EM TEMPO REAL


buffer = []

frase = []

ultimo_gesto = ""

gesto_confirmado = None

contador_confirmacao = 0

CONFIANCA_MINIMA = 0.95

PREDICOES_CONSECUTIVAS = 5



# UPLOAD DO DATASET


@app.route(
    "/criar_dataset",
    methods=["POST"]
)
def criar_dataset_api():

    if "mediaFile" not in request.files:

        return jsonify({
            "success": False,
            "error": "Nenhum arquivo enviado."
        }), 400

    dataset = request.form.get(
        "dataset",
        ""
    ).strip()

    if dataset == "":

        return jsonify({
            "success": False,
            "error": (
                "Nome do dataset inválido."
            )
        }), 400

    arquivo = request.files[
        "mediaFile"
    ]

    if arquivo.filename == "":

        return jsonify({
            "success": False,
            "error": "Arquivo inválido."
        }), 400

    nome_dataset = secure_filename(
        dataset
    )

    if nome_dataset == "":

        return jsonify({
            "success": False,
            "error": (
                "O nome do dataset não possui "
                "caracteres válidos."
            )
        }), 400

    pasta = os.path.join(
        BASE_DIR,
        "libraas",
        nome_dataset
    )

    os.makedirs(
        pasta,
        exist_ok=True
    )

    nome_arquivo = secure_filename(
        arquivo.filename
    )

    if nome_arquivo == "":

        return jsonify({
            "success": False,
            "error": (
                "O nome do arquivo é inválido."
            )
        }), 400

    caminho = os.path.join(
        pasta,
        nome_arquivo
    )

    try:

        arquivo.save(caminho)

    except Exception as erro:

        return jsonify({
            "success": False,
            "error": str(erro)
        }), 500

    return jsonify({
        "success": True,
        "arquivo": nome_arquivo,
        "dataset": nome_dataset
    }), 200



# PROCESSAMENTO DO DATASET


@app.route(
    "/finalizar_dataset",
    methods=["POST"]
)
def finalizar_dataset():

    dataset = request.form.get(
        "dataset",
        ""
    ).strip()

    if dataset == "":

        return jsonify({
            "success": False,
            "error": "Dataset não informado."
        }), 400

    dataset_dir = os.path.join(
        BASE_DIR,
        "libraas"
    )

    output_dir = os.path.join(
        BASE_DIR,
        "dataset_processado"
    )

    nome_seguro = secure_filename(
        dataset
    )

    pasta_gesto = os.path.join(
        dataset_dir,
        nome_seguro
    )

    print(">>> Finalizando dataset")

    print(
        "Dataset:",
        dataset
    )

    print(
        "Entrada:",
        dataset_dir
    )

    print(
        "Pasta do gesto:",
        pasta_gesto
    )

    print(
        "Saída:",
        output_dir
    )

    if not os.path.isdir(
        dataset_dir
    ):

        return jsonify({
            "success": False,
            "error": (
                "A pasta libraas não "
                "foi encontrada."
            ),
            "caminho": dataset_dir
        }), 404

    if not os.path.isdir(
        pasta_gesto
    ):

        return jsonify({
            "success": False,
            "error": (
                "A pasta do gesto não "
                "foi encontrada."
            ),
            "caminho": pasta_gesto
        }), 404

    arquivos = [
        nome
        for nome in os.listdir(
            pasta_gesto
        )
        if os.path.isfile(
            os.path.join(
                pasta_gesto,
                nome
            )
        )
    ]

    if len(arquivos) == 0:

        return jsonify({
            "success": False,
            "error": (
                "A pasta do gesto "
                "está vazia."
            )
        }), 400

    try:

        resultado = criar_dataset(
            dataset_dir,
            output_dir
        )

        if not isinstance(
            resultado,
            dict
        ):

            return jsonify({
                "success": False,
                "error": (
                    "A função criar_dataset "
                    "não retornou um "
                    "dicionário válido."
                )
            }), 500

        if not resultado.get(
            "success",
            False
        ):

            return jsonify(
                resultado
            ), 400

        shutil.rmtree(
            dataset_dir
        )

        print(
            "Pasta removida:",
            dataset_dir
        )

        return jsonify({
            **resultado,
            "success": True,
            "dataset": dataset,
            "arquivos_recebidos": (
                len(arquivos)
            ),
            "pasta_libraas_removida": True
        }), 200

    except Exception as erro:

        print(
            "Erro ao finalizar dataset:",
            repr(erro)
        )

        return jsonify({
            "success": False,
            "error": str(erro)
        }), 500



# INICIAR TREINAMENTO


@app.route(
    "/treinar_modelo",
    methods=["POST"]
)
def treinar_modelo_api():

    dados_json = request.get_json(
        silent=True
    ) or {}

    epochs = request.form.get(
        "epochs",
        dados_json.get(
            "epochs",
            120
        )
    )

    batch_size = request.form.get(
        "batch_size",
        dados_json.get(
            "batch_size",
            16
        )
    )

    resultado = iniciar_treinamento(
        epochs=epochs,
        batch_size=batch_size
    )

    if not resultado.get(
        "success",
        False
    ):

        return jsonify(
            resultado
        ), 409

    return jsonify(
        resultado
    ), 202



# STATUS DO TREINAMENTO


@app.route(
    "/status_treinamento",
    methods=["GET"]
)
def status_treinamento_api():

    desde_log = request.args.get(
        "desde_log",
        0
    )

    status = obter_status_treinamento(
        desde_log=desde_log
    )

    return jsonify(
        status
    ), 200



# CANCELAR TREINAMENTO


@app.route(
    "/cancelar_treinamento",
    methods=["POST"]
)
def cancelar_treinamento_api():

    dados_json = request.get_json(
        silent=True
    ) or {}

    job_id = request.form.get(
        "job_id",
        dados_json.get(
            "job_id"
        )
    )

    resultado = cancelar_treinamento(
        job_id=job_id
    )

    if not resultado.get(
        "success",
        False
    ):

        return jsonify(
            resultado
        ), 409

    return jsonify(
        resultado
    ), 200



# ANÁLISE DE FOTO/VÍDEO


@app.route(
    "/analisar",
    methods=["POST"]
)
def analisar():

    print(
        ">>> Requisição recebida!"
    )

    arquivos = list(
        request.files.values()
    )

    if not arquivos:

        return jsonify(
            success=False,
            error="Nenhum arquivo enviado."
        ), 400

    resultados = []

    for arquivo in arquivos:

        extensao = os.path.splitext(
            arquivo.filename
        )[1].lower()

        if extensao not in [
            ".jpg",
            ".jpeg",
            ".png",
            ".mp4",
            ".avi",
            ".mov",
            ".mkv"
        ]:

            resultados.append({
                "arquivo": arquivo.filename,
                "erro": (
                    "Formato não suportado."
                )
            })

            continue

        nome = (
            f"{uuid.uuid4()}{extensao}"
        )

        upload_folder = os.path.join(
            BASE_DIR,
            "uploads"
        )

        os.makedirs(
            upload_folder,
            exist_ok=True
        )

        caminho = os.path.join(
            upload_folder,
            nome
        )

        arquivo.save(
            caminho
        )

        try:

            if extensao in [
                ".jpg",
                ".jpeg",
                ".png"
            ]:

                sequencia = (
                    processar_imagem_traducao(
                        caminho
                    )
                )

            else:

                sequencia = (
                    processar_video_traducao(
                        caminho
                    )
                )

            if sequencia is None:

                resultados.append({
                    "arquivo": (
                        arquivo.filename
                    ),
                    "erro": (
                        "Não foi possível "
                        "processar."
                    )
                })

                continue

            gesto, confianca = prever(
                sequencia
            )

            resultados.append({
                "arquivo": arquivo.filename,
                "gesto": gesto,
                "confianca": round(
                    confianca * 100,
                    2
                )
            })

        except Exception as erro:

            resultados.append({
                "arquivo": arquivo.filename,
                "erro": str(erro)
            })

        finally:

            if os.path.exists(
                caminho
            ):

                os.remove(
                    caminho
                )

    return jsonify(
        success=True,
        resultados=resultados
    ), 200



# TRADUÇÃO EM TEMPO REAL


@app.route(
    "/traducao_tempo_real",
    methods=["POST"]
)
def traducao_tempo_real():

    global buffer
    global frase
    global ultimo_gesto
    global gesto_confirmado
    global contador_confirmacao

    inicio = time.time()

    if "frame" not in request.files:

        return jsonify({
            "status": "erro",
            "error": (
                "Nenhum frame recebido."
            )
        }), 400

    arquivo = request.files[
        "frame"
    ]

    npimg = np.frombuffer(
        arquivo.read(),
        np.uint8
    )

    frame = cv2.imdecode(
        npimg,
        cv2.IMREAD_COLOR
    )

    if frame is None:

        return jsonify({
            "status": "erro",
            "error": "Frame inválido."
        }), 400

    landmarks = (
        extrair_landmarks_traducao(
            frame
        )
    )

    if landmarks is None:

        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        }), 200

    buffer.append(
        landmarks
    )

    if len(buffer) > SEQUENCE_LENGTH:

        buffer.pop(0)

    if len(buffer) < SEQUENCE_LENGTH:

        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        }), 200

    sequencia = np.array(
        buffer
    )

    sequencia = normalizar(
        sequencia
    )

    sequencia = sequencia.reshape(
        1,
        SEQUENCE_LENGTH,
        FEATURES
    )

    gesto, confianca = prever(
        sequencia
    )

    print(
        f"{gesto} -> {confianca:.2%}"
    )

    if confianca < CONFIANCA_MINIMA:

        gesto_confirmado = None

        contador_confirmacao = 0

        return jsonify({
            "status": "aguardando",
            "texto": " ".join(frase)
        }), 200

    if gesto == gesto_confirmado:

        contador_confirmacao += 1

    else:

        gesto_confirmado = gesto

        contador_confirmacao = 1

    if (
        contador_confirmacao <
        PREDICOES_CONSECUTIVAS
    ):

        return jsonify({
            "status": "analisando",
            "texto": " ".join(frase),
            "gesto": gesto,
            "confirmacao": (
                contador_confirmacao
            )
        }), 200

    print(
        "Confirmação:",
        gesto_confirmado,
        contador_confirmacao
    )

    if (
        contador_confirmacao >=
        PREDICOES_CONSECUTIVAS
    ):

        if gesto != ultimo_gesto:

            frase.append(
                gesto
            )

            ultimo_gesto = gesto

            print(
                ">>> GESTO ACEITO:",
                gesto
            )

        buffer.clear()

        gesto_confirmado = None

        contador_confirmacao = 0

        ultimo_gesto = None

        print(
            "Tempo:",
            round(
                time.time() - inicio,
                2
            ),
            "seg"
        )

    return jsonify(
        status="traduzido",
        texto=" ".join(frase)
    ), 200



# LIMPAR TRADUÇÃO


@app.route(
    "/limpar_traducao",
    methods=["POST"]
)
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

    return jsonify(
        ok=True
    ), 200


if __name__ == "__main__":

    app.run(
        debug=True,
        use_reloader=False,
        threaded=True
    )