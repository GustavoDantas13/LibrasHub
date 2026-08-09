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

from src.traducao.prever import (
    prever,
    modelo_disponivel
)

from src.traducao.extrair_landmarks import (
    extrair_landmarks_traducao
)

from src.traducao.normalizar import (
    normalizar,
    scaler_disponivel
)

from src.traducao.dataset import (
    criar_dataset
)

from src.traducao.treinamento_cnn import (
    cancelar_treinamento,
    iniciar_treinamento,
    obter_status_treinamento
)


# Flask

app = Flask(__name__)


# Caminhos

BASE_DIR = os.path.dirname(
    os.path.abspath(__file__)
)

LIBRAAS_DIR = os.path.join(
    BASE_DIR,
    "libraas"
)

DATASET_PROCESSADO_DIR = os.path.join(
    BASE_DIR,
    "dataset_processado"
)

UPLOAD_DIR = os.path.join(
    BASE_DIR,
    "uploads"
)


# Configurações

SEQUENCE_LENGTH = 30

FEATURES = 158

CONFIANCA_MINIMA = 0.95

PREDICOES_CONSECUTIVAS = 5


# Estado da tradução em tempo real

buffer = []

frase = []

ultimo_gesto = ""

gesto_confirmado = None

contador_confirmacao = 0


def recursos_traducao_disponiveis():

    return (
        modelo_disponivel()
        and
        scaler_disponivel()
    )


def erro_recursos_traducao():

    arquivos_ausentes = []

    if not modelo_disponivel():

        arquivos_ausentes.extend([
            "modelo_gestos.keras",
            "labels.npy"
        ])

    if not scaler_disponivel():

        arquivos_ausentes.extend([
            "scaler_mean.npy",
            "scaler_scale.npy"
        ])

    arquivos_ausentes = list(
        dict.fromkeys(
            arquivos_ausentes
        )
    )

    return (
        "O modelo ainda não está disponível. "
        "Realize o treinamento antes da tradução. "
        "Arquivos necessários: "
        + ", ".join(arquivos_ausentes)
    )


# Upload de arquivos do dataset

@app.route(
    "/criar_dataset",
    methods=["POST"]
)
def criar_dataset_api():

    print(
        ">>> /criar_dataset foi acionada",
        flush=True
    )

    if "mediaFile" not in request.files:

        return jsonify({
            "success": False,
            "error": (
                "Nenhum arquivo enviado ao Flask."
            )
        }), 400

    dataset = request.form.get(
        "dataset",
        ""
    ).strip()

    if not dataset:

        return jsonify({
            "success": False,
            "error": (
                "Nome do dataset não informado."
            )
        }), 400

    arquivo = request.files[
        "mediaFile"
    ]

    if not arquivo.filename:

        return jsonify({
            "success": False,
            "error": "Arquivo sem nome."
        }), 400

    nome_dataset = secure_filename(
        dataset
    )

    nome_arquivo = secure_filename(
        arquivo.filename
    )

    if not nome_dataset:

        return jsonify({
            "success": False,
            "error": (
                "Nome do dataset inválido."
            )
        }), 400

    if not nome_arquivo:

        return jsonify({
            "success": False,
            "error": (
                "Nome do arquivo inválido."
            )
        }), 400

    pasta_dataset = os.path.join(
        LIBRAAS_DIR,
        nome_dataset
    )

    caminho_arquivo = os.path.join(
        pasta_dataset,
        nome_arquivo
    )

    try:

        os.makedirs(
            pasta_dataset,
            exist_ok=True
        )

        arquivo.save(
            caminho_arquivo
        )

        if not os.path.isfile(
            caminho_arquivo
        ):

            raise RuntimeError(
                "O arquivo não apareceu no disco "
                "após o salvamento."
            )

        tamanho = os.path.getsize(
            caminho_arquivo
        )

        if tamanho <= 0:

            if os.path.isfile(
                caminho_arquivo
            ):
                os.remove(
                    caminho_arquivo
                )

            raise RuntimeError(
                "O arquivo salvo está vazio."
            )

        print(
            "Arquivo salvo:",
            caminho_arquivo,
            flush=True
        )

        return jsonify({
            "success": True,
            "rota": "criar_dataset",
            "dataset": nome_dataset,
            "arquivo": nome_arquivo,
            "tamanho": tamanho,
            "pasta": pasta_dataset,
            "existe": os.path.isfile(
                caminho_arquivo
            )
        }), 200

    except Exception as erro:

        print(
            "Erro ao salvar:",
            repr(erro),
            flush=True
        )

        return jsonify({
            "success": False,
            "error": str(erro),
            "pasta": pasta_dataset,
            "caminho": caminho_arquivo
        }), 500


# Processamento do dataset

@app.route(
    "/finalizar_dataset",
    methods=["POST"]
)
def finalizar_dataset():

    print(
        ">>> Finalizando dataset",
        flush=True
    )


    dataset = request.form.get(
        "dataset",
        ""
    ).strip()


    if dataset == "":

        return jsonify({
            "success": False,
            "error": (
                "Dataset não informado."
            )
        }), 400


    nome_dataset = secure_filename(
        dataset
    )


    if nome_dataset == "":

        return jsonify({
            "success": False,
            "error": (
                "Nome do dataset inválido."
            )
        }), 400


    pasta_gesto = os.path.join(
        LIBRAAS_DIR,
        nome_dataset
    )


    print(
        "Dataset:",
        nome_dataset
    )

    print(
        "Entrada:",
        LIBRAAS_DIR
    )

    print(
        "Pasta do gesto:",
        pasta_gesto
    )

    print(
        "Saída:",
        DATASET_PROCESSADO_DIR
    )


    if not os.path.isdir(
        LIBRAAS_DIR
    ):

        return jsonify({
            "success": False,

            "error": (
                "A pasta libraas não foi encontrada. "
                "Nenhum arquivo foi salvo pela rota "
                "/criar_dataset."
            ),

            "caminho":
                LIBRAAS_DIR

        }), 404


    if not os.path.isdir(
        pasta_gesto
    ):

        return jsonify({
            "success": False,

            "error": (
                "A pasta do gesto não foi encontrada."
            ),

            "caminho":
                pasta_gesto

        }), 404


    arquivos = [

        nome

        for nome
        in os.listdir(
            pasta_gesto
        )

        if os.path.isfile(
            os.path.join(
                pasta_gesto,
                nome
            )
        )
    ]


    print(
        "Arquivos encontrados:",
        len(
            arquivos
        )
    )


    if len(arquivos) == 0:

        return jsonify({
            "success": False,

            "error": (
                "A pasta do gesto está vazia."
            )

        }), 400


    try:

        resultado = criar_dataset(
            LIBRAAS_DIR,
            DATASET_PROCESSADO_DIR
        )


        if not isinstance(
            resultado,
            dict
        ):

            return jsonify({
                "success": False,

                "error": (
                    "A função criar_dataset não "
                    "retornou um resultado válido."
                )

            }), 500


        if not resultado.get(
            "success",
            False
        ):

            return jsonify(
                resultado
            ), 400


        dataset_gerado = None


        for item in resultado.get(
            "datasets_gerados",
            []
        ):

            if (
                item.get(
                    "gesto"
                )
                ==
                nome_dataset
            ):

                dataset_gerado = item

                break


        if dataset_gerado is None:

            return jsonify({
                "success": False,

                "error": (
                    "O processamento terminou, "
                    "mas o arquivo do gesto solicitado "
                    "não foi encontrado."
                )

            }), 500


        caminho_absoluto = (
            dataset_gerado.get(
                "caminho"
            )
        )


        if (
            not caminho_absoluto
            or
            not os.path.isfile(
                caminho_absoluto
            )
        ):

            return jsonify({
                "success": False,

                "error": (
                    "O arquivo .npy não foi encontrado "
                    "após o processamento."
                )

            }), 500


        base_dir = os.path.dirname(
            os.path.abspath(
                __file__
            )
        )


        caminho_relativo = os.path.relpath(
            caminho_absoluto,
            base_dir
        )


        caminho_relativo = (
            caminho_relativo
            .replace(
                "\\",
                "/"
            )
        )


        print(
            "Dataset gerado:",
            caminho_relativo
        )


        # Remove a pasta libraas
        # somente depois que o .npy
        # foi confirmado.

        if os.path.isdir(
            LIBRAAS_DIR
        ):

            shutil.rmtree(
                LIBRAAS_DIR
            )


        pasta_removida = (
            not os.path.exists(
                LIBRAAS_DIR
            )
        )


        print(
            "Pasta libraas removida:",
            pasta_removida
        )


        return jsonify({

            "success": True,

            "nome_gesto":
                nome_dataset,

            "dataset":
                caminho_relativo,

            "arquivo_dataset":
                dataset_gerado.get(
                    "arquivo"
                ),

            "total_amostras":
                dataset_gerado.get(
                    "amostras",
                    0
                ),

            "shape":
                dataset_gerado.get(
                    "shape",
                    []
                ),

            "arquivos_recebidos":
                len(
                    arquivos
                ),

            "pasta_libraas_removida":
                pasta_removida

        }), 200


    except Exception as erro:

        print(
            "Erro ao finalizar dataset:",
            repr(
                erro
            )
        )


        return jsonify({

            "success": False,

            "error":
                str(
                    erro
                )

        }), 500

# Iniciar treinamento

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


# Status do treinamento

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


# Cancelar treinamento

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


# Análise de fotos e vídeos

@app.route(
    "/analisar",
    methods=["POST"]
)
def analisar():

    if not recursos_traducao_disponiveis():

        return jsonify({
            "success": False,
            "error": (
                erro_recursos_traducao()
            )
        }), 400

    print(
        ">>> Requisição recebida!"
    )

    arquivos = list(
        request.files.values()
    )

    if not arquivos:

        return jsonify({
            "success": False,
            "error": (
                "Nenhum arquivo enviado."
            )
        }), 400

    resultados = []

    os.makedirs(
        UPLOAD_DIR,
        exist_ok=True
    )

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

        nome_temporario = (
            f"{uuid.uuid4()}{extensao}"
        )

        caminho = os.path.join(
            UPLOAD_DIR,
            nome_temporario
        )

        try:

            arquivo.save(
                caminho
            )

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
                    "arquivo": arquivo.filename,
                    "erro": (
                        "Não foi possível processar."
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

    return jsonify({
        "success": True,
        "resultados": resultados
    }), 200


# Tradução em tempo real

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

    if not recursos_traducao_disponiveis():

        return jsonify({
            "status": "erro",
            "error": (
                erro_recursos_traducao()
            ),
            "texto": ""
        }), 400

    inicio = time.time()

    if "frame" not in request.files:

        return jsonify({
            "status": "erro",
            "error": (
                "Nenhum frame recebido."
            ),
            "texto": " ".join(frase)
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
            "error": (
                "Frame inválido."
            ),
            "texto": " ".join(frase)
        }), 400

    try:

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
            buffer,
            dtype=np.float32
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

        return jsonify({
            "status": "traduzido",
            "texto": " ".join(frase)
        }), 200

    except Exception as erro:

        print(
            "Erro na tradução em tempo real:",
            repr(erro)
        )

        buffer.clear()

        gesto_confirmado = None

        contador_confirmacao = 0

        return jsonify({
            "status": "erro",
            "error": str(erro),
            "texto": " ".join(frase)
        }), 500


# Limpar tradução

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

    return jsonify({
        "ok": True
    }), 200


# Iniciar servidor

if __name__ == "__main__":

    app.run(
        debug=True,
        use_reloader=False,
        threaded=True
    )