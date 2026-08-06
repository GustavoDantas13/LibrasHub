import json
import os
import threading
import traceback
import uuid
from pathlib import Path

import numpy as np

from sklearn.metrics import (
    classification_report,
    confusion_matrix
)

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.preprocessing import StandardScaler
from sklearn.utils.class_weight import compute_class_weight

from tensorflow.keras.callbacks import (
    Callback,
    EarlyStopping,
    ReduceLROnPlateau
)

from tensorflow.keras.layers import (
    Add,
    BatchNormalization,
    Bidirectional,
    Conv1D,
    Dense,
    Dropout,
    GlobalAveragePooling1D,
    Input,
    LayerNormalization,
    LSTM,
    MaxPooling1D,
    MultiHeadAttention
)

from tensorflow.keras.models import Model

from tensorflow.keras.regularizers import l2

from tensorflow.keras.utils import to_categorical



# CAMINHOS DO PROJETO


BASE_DIR = Path(__file__).resolve().parents[2]

DATASET_DIR = BASE_DIR / "dataset_processado"

MODEL_PATH = BASE_DIR / "modelo_gestos.keras"

LABELS_PATH = BASE_DIR / "labels.npy"

SCALER_MEAN_PATH = BASE_DIR / "scaler_mean.npy"

SCALER_SCALE_PATH = BASE_DIR / "scaler_scale.npy"

RESULTADOS_DIR = BASE_DIR / "resultados_treinamento"

HISTORY_PATH = RESULTADOS_DIR / "historico.json"

REPORT_PATH = RESULTADOS_DIR / "relatorio_classificacao.json"

CONFUSION_MATRIX_PATH = (
    RESULTADOS_DIR / "matriz_confusao.npy"
)



# CONFIGURAÇÕES


TOTAL_EPOCHS = 120

BATCH_SIZE = 16

TEST_SIZE = 0.20

RANDOM_STATE = 42



# ESTADO GLOBAL DO TREINAMENTO


_estado_lock = threading.Lock()

_thread_treinamento = None

_evento_cancelamento = threading.Event()

_estado = {
    "success": True,
    "job_id": None,
    "status": "aguardando",
    "message": "Aguardando início do treinamento.",
    "epoch": 0,
    "total_epochs": TOTAL_EPOCHS,
    "progress": 0.0,
    "accuracy": 0.0,
    "val_accuracy": 0.0,
    "loss": 0.0,
    "val_loss": 0.0,
    "finished": False,
    "failed": False,
    "cancelled": False,
    "error": None,
    "logs": [],
    "resultado": None
}



# FUNÇÕES DE ESTADO


def _atualizar_estado(**dados):

    with _estado_lock:
        _estado.update(dados)


def _adicionar_log(mensagem):

    mensagem = str(mensagem)

    print(mensagem)

    with _estado_lock:

        _estado["logs"].append({
            "id": len(_estado["logs"]),
            "mensagem": mensagem
        })


def obter_status_treinamento(desde_log=0):

    try:
        desde_log = int(desde_log)
    except (TypeError, ValueError):
        desde_log = 0

    with _estado_lock:

        copia = dict(_estado)

        logs = list(_estado["logs"])

    copia["logs"] = [
        item
        for item in logs
        if item["id"] >= desde_log
    ]

    copia["proximo_log"] = len(logs)

    return copia


def treinamento_em_execucao():

    with _estado_lock:

        return _estado["status"] in [
            "preparando",
            "treinando",
            "avaliando",
            "salvando"
        ]



# CALLBACK DE PROGRESSO


class CallbackProgresso(Callback):

    def __init__(
        self,
        total_epochs,
        evento_cancelamento
    ):

        super().__init__()

        self.total_epochs = total_epochs

        self.evento_cancelamento = (
            evento_cancelamento
        )

    def on_train_begin(self, logs=None):

        _atualizar_estado(
            status="treinando",
            message="Treinamento iniciado.",
            epoch=0,
            progress=0.0
        )

        _adicionar_log(
            "Treinamento do modelo iniciado."
        )

    def on_epoch_end(self, epoch, logs=None):

        logs = logs or {}

        epoca_atual = epoch + 1

        progresso = (
            epoca_atual /
            self.total_epochs
        ) * 100

        accuracy = float(
            logs.get("accuracy", 0.0)
        )

        val_accuracy = float(
            logs.get("val_accuracy", 0.0)
        )

        loss = float(
            logs.get("loss", 0.0)
        )

        val_loss = float(
            logs.get("val_loss", 0.0)
        )

        _atualizar_estado(
            status="treinando",
            message=(
                f"Época {epoca_atual} "
                f"de {self.total_epochs}"
            ),
            epoch=epoca_atual,
            progress=round(progresso, 2),
            accuracy=accuracy,
            val_accuracy=val_accuracy,
            loss=loss,
            val_loss=val_loss
        )

        _adicionar_log(
            (
                f"Época {epoca_atual}/"
                f"{self.total_epochs} - "
                f"accuracy: {accuracy:.4f} - "
                f"val_accuracy: "
                f"{val_accuracy:.4f} - "
                f"loss: {loss:.4f} - "
                f"val_loss: {val_loss:.4f}"
            )
        )

        if self.evento_cancelamento.is_set():

            self.model.stop_training = True

            _adicionar_log(
                "Cancelamento detectado. "
                "Encerrando treinamento..."
            )

    def on_train_batch_end(
        self,
        batch,
        logs=None
    ):

        if self.evento_cancelamento.is_set():

            self.model.stop_training = True



# CARREGAMENTO DO DATASET


def carregar_dataset(dataset_dir):

    dataset_dir = Path(dataset_dir)

    if not dataset_dir.is_dir():

        raise FileNotFoundError(
            (
                "Pasta de datasets processados "
                f"não encontrada: {dataset_dir}"
            )
        )

    arquivos_npy = sorted(
        dataset_dir.glob("*.npy")
    )

    if not arquivos_npy:

        raise RuntimeError(
            "Nenhum arquivo .npy foi encontrado."
        )

    X = []
    y = []

    _adicionar_log(
        f"Carregando datasets de: {dataset_dir}"
    )

    for caminho in arquivos_npy:

        gesto = caminho.stem

        _adicionar_log(
            f"Carregando classe: {gesto}"
        )

        dados = np.load(
            caminho,
            allow_pickle=False
        )

        if dados.ndim != 3:

            raise ValueError(
                (
                    f"O arquivo {caminho.name} "
                    "não possui o formato esperado. "
                    f"Shape recebido: {dados.shape}"
                )
            )

        for amostra in dados:

            X.append(amostra)

            y.append(gesto)

    X = np.asarray(
        X,
        dtype=np.float32
    )

    y = np.asarray(y)

    if len(X) == 0:

        raise RuntimeError(
            "O dataset não possui amostras."
        )

    classes, quantidades = np.unique(
        y,
        return_counts=True
    )

    if len(classes) < 2:

        raise RuntimeError(
            (
                "O treinamento exige pelo menos "
                "duas classes diferentes."
            )
        )

    classes_insuficientes = [
        classe
        for classe, quantidade
        in zip(classes, quantidades)
        if quantidade < 2
    ]

    if classes_insuficientes:

        raise RuntimeError(
            (
                "As seguintes classes possuem menos "
                "de duas amostras: "
                + ", ".join(classes_insuficientes)
            )
        )

    _adicionar_log(
        f"Total de amostras: {len(X)}"
    )

    _adicionar_log(
        f"Formato do dataset: {X.shape}"
    )

    _adicionar_log(
        f"Classes encontradas: {len(classes)}"
    )

    return X, y



# CRIAÇÃO DO MODELO


def construir_modelo(
    timesteps,
    features,
    quantidade_classes
):

    entrada = Input(
        shape=(timesteps, features),
        name="entrada_landmarks"
    )

    # Bloco CNN 1

    x = Conv1D(
        64,
        kernel_size=3,
        padding="same",
        activation="relu"
    )(entrada)

    x = BatchNormalization()(x)

    x = Conv1D(
        64,
        kernel_size=3,
        padding="same",
        activation="relu"
    )(x)

    x = MaxPooling1D(
        pool_size=2
    )(x)

    # Bloco CNN 2

    x = Conv1D(
        128,
        kernel_size=3,
        padding="same",
        activation="relu"
    )(x)

    x = BatchNormalization()(x)

    x = MaxPooling1D(
        pool_size=2
    )(x)

    # Primeira BiLSTM

    x = Bidirectional(
        LSTM(
            64,
            return_sequences=True
        )
    )(x)

    x = Dropout(0.30)(x)

    # Self Attention

    attention = MultiHeadAttention(
        num_heads=4,
        key_dim=64
    )(x, x)

    x = Add()([
        x,
        attention
    ])

    x = LayerNormalization()(x)

    # Segunda BiLSTM

    x = Bidirectional(
        LSTM(
            32,
            return_sequences=True
        )
    )(x)

    # Pooling

    x = GlobalAveragePooling1D()(x)

    # Classificador

    x = Dense(
        128,
        activation="relu",
        kernel_regularizer=l2(0.0005)
    )(x)

    x = Dropout(0.50)(x)

    x = Dense(
        64,
        activation="relu",
        kernel_regularizer=l2(0.0005)
    )(x)

    saida = Dense(
        quantidade_classes,
        activation="softmax",
        name="classificacao"
    )(x)

    modelo = Model(
        inputs=entrada,
        outputs=saida
    )

    modelo.compile(
        optimizer="adam",
        loss="categorical_crossentropy",
        metrics=["accuracy"]
    )

    return modelo



# SALVAMENTO DO HISTÓRICO


def salvar_historico(history):

    RESULTADOS_DIR.mkdir(
        parents=True,
        exist_ok=True
    )

    historico = {}

    for chave, valores in history.history.items():

        historico[chave] = [
            float(valor)
            for valor in valores
        ]

    with open(
        HISTORY_PATH,
        "w",
        encoding="utf-8"
    ) as arquivo:

        json.dump(
            historico,
            arquivo,
            ensure_ascii=False,
            indent=4
        )

    return historico



# EXECUÇÃO PRINCIPAL DO TREINAMENTO


def executar_treinamento(
    job_id,
    epochs=TOTAL_EPOCHS,
    batch_size=BATCH_SIZE
):

    try:

        _atualizar_estado(
            status="preparando",
            message="Preparando o dataset.",
            total_epochs=epochs
        )

        _adicionar_log(
            f"Job iniciado: {job_id}"
        )

        X, labels_texto = carregar_dataset(
            DATASET_DIR
        )

        timesteps = X.shape[1]

        features = X.shape[2]

        _adicionar_log(
            f"Frames por sequência: {timesteps}"
        )

        _adicionar_log(
            f"Features por frame: {features}"
        )

        # Normalização

        _adicionar_log(
            "Normalizando os dados..."
        )

        scaler = StandardScaler()

        X_2d = X.reshape(
            -1,
            features
        )

        X_2d = scaler.fit_transform(
            X_2d
        )

        X = X_2d.reshape(
            -1,
            timesteps,
            features
        ).astype(np.float32)

        # Codificação dos labels

        encoder = LabelEncoder()

        labels_numericos = (
            encoder.fit_transform(
                labels_texto
            )
        )

        quantidade_classes = len(
            encoder.classes_
        )

        y_categorico = to_categorical(
            labels_numericos,
            num_classes=quantidade_classes
        )

        # Separação treino/teste

        (
            X_train,
            X_test,
            y_train,
            y_test,
            labels_train,
            labels_test
        ) = train_test_split(
            X,
            y_categorico,
            labels_numericos,
            test_size=TEST_SIZE,
            random_state=RANDOM_STATE,
            stratify=labels_numericos
        )

        # Pesos das classes

        classes_unicas = np.unique(
            labels_train
        )

        pesos = compute_class_weight(
            class_weight="balanced",
            classes=classes_unicas,
            y=labels_train
        )

        class_weight = dict(
            zip(
                classes_unicas.tolist(),
                pesos.tolist()
            )
        )

        _adicionar_log(
            (
                f"Treino: {len(X_train)} amostras. "
                f"Validação: {len(X_test)} amostras."
            )
        )

        # Modelo

        modelo = construir_modelo(
            timesteps=timesteps,
            features=features,
            quantidade_classes=quantidade_classes
        )

        _adicionar_log(
            "Arquitetura do modelo criada."
        )

        callback_progresso = CallbackProgresso(
            total_epochs=epochs,
            evento_cancelamento=(
                _evento_cancelamento
            )
        )

        callbacks = [

            callback_progresso,

            EarlyStopping(
                monitor="val_loss",
                patience=20,
                restore_best_weights=True
            ),

            ReduceLROnPlateau(
                monitor="val_loss",
                factor=0.5,
                patience=7,
                verbose=0
            )

        ]

        # Treinamento

        history = modelo.fit(
            X_train,
            y_train,
            validation_data=(
                X_test,
                y_test
            ),
            epochs=epochs,
            batch_size=batch_size,
            class_weight=class_weight,
            callbacks=callbacks,
            verbose=0
        )

        if _evento_cancelamento.is_set():

            _atualizar_estado(
                status="cancelado",
                message=(
                    "Treinamento cancelado "
                    "pelo usuário."
                ),
                finished=True,
                failed=False,
                cancelled=True
            )

            _adicionar_log(
                "Treinamento cancelado."
            )

            return

        # Avaliação

        _atualizar_estado(
            status="avaliando",
            message="Avaliando o modelo."
        )

        _adicionar_log(
            "Avaliando o modelo no conjunto de teste..."
        )

        loss_final, accuracy_final = (
            modelo.evaluate(
                X_test,
                y_test,
                verbose=0
            )
        )

        probabilidades = modelo.predict(
            X_test,
            verbose=0
        )

        y_pred = np.argmax(
            probabilidades,
            axis=1
        )

        matriz = confusion_matrix(
            labels_test,
            y_pred,
            labels=np.arange(
                quantidade_classes
            )
        )

        relatorio = classification_report(
            labels_test,
            y_pred,
            labels=np.arange(
                quantidade_classes
            ),
            target_names=encoder.classes_,
            output_dict=True,
            zero_division=0
        )

        # Salvamento

        _atualizar_estado(
            status="salvando",
            message="Salvando o modelo."
        )

        _adicionar_log(
            "Salvando modelo, labels e normalização..."
        )

        RESULTADOS_DIR.mkdir(
            parents=True,
            exist_ok=True
        )

        modelo.save(MODEL_PATH)

        np.save(
            LABELS_PATH,
            encoder.classes_
        )

        np.save(
            SCALER_MEAN_PATH,
            scaler.mean_
        )

        np.save(
            SCALER_SCALE_PATH,
            scaler.scale_
        )

        np.save(
            CONFUSION_MATRIX_PATH,
            matriz
        )

        with open(
            REPORT_PATH,
            "w",
            encoding="utf-8"
        ) as arquivo:

            json.dump(
                relatorio,
                arquivo,
                ensure_ascii=False,
                indent=4
            )

        historico = salvar_historico(
            history
        )

        quantidade_epocas = len(
            historico.get(
                "loss",
                []
            )
        )

        resultado = {
            "accuracy_final": float(
                accuracy_final
            ),
            "loss_final": float(
                loss_final
            ),
            "epochs_executadas": (
                quantidade_epocas
            ),
            "classes": (
                encoder.classes_.tolist()
            ),
            "quantidade_classes": (
                quantidade_classes
            ),
            "amostras_treino": len(
                X_train
            ),
            "amostras_validacao": len(
                X_test
            ),
            "model_path": str(
                MODEL_PATH
            ),
            "history_path": str(
                HISTORY_PATH
            )
        }

        _atualizar_estado(
            status="concluido",
            message=(
                "Treinamento concluído "
                "com sucesso."
            ),
            progress=100.0,
            accuracy=float(
                history.history[
                    "accuracy"
                ][-1]
            ),
            val_accuracy=float(
                history.history[
                    "val_accuracy"
                ][-1]
            ),
            loss=float(
                history.history[
                    "loss"
                ][-1]
            ),
            val_loss=float(
                history.history[
                    "val_loss"
                ][-1]
            ),
            finished=True,
            failed=False,
            cancelled=False,
            resultado=resultado
        )

        _adicionar_log(
            (
                "Modelo salvo com sucesso. "
                f"Acurácia final: "
                f"{accuracy_final * 100:.2f}%"
            )
        )

    except Exception as erro:

        traceback_texto = traceback.format_exc()

        print(traceback_texto)

        _atualizar_estado(
            status="erro",
            message="Erro durante o treinamento.",
            finished=True,
            failed=True,
            cancelled=False,
            error=str(erro)
        )

        _adicionar_log(
            f"Erro: {erro}"
        )



# INICIAR TREINAMENTO


def iniciar_treinamento(
    epochs=TOTAL_EPOCHS,
    batch_size=BATCH_SIZE
):

    global _thread_treinamento

    if treinamento_em_execucao():

        return {
            "success": False,
            "error": (
                "Já existe um treinamento "
                "em execução."
            )
        }

    try:

        epochs = int(epochs)

        batch_size = int(batch_size)

    except (TypeError, ValueError):

        return {
            "success": False,
            "error": (
                "Épocas e tamanho do lote "
                "devem ser números inteiros."
            )
        }

    if epochs <= 0:

        return {
            "success": False,
            "error": (
                "O número de épocas deve "
                "ser maior que zero."
            )
        }

    if batch_size <= 0:

        return {
            "success": False,
            "error": (
                "O tamanho do lote deve "
                "ser maior que zero."
            )
        }

    if not DATASET_DIR.is_dir():

        return {
            "success": False,
            "error": (
                "A pasta dataset_processado "
                "não foi encontrada."
            )
        }

    if not list(
        DATASET_DIR.glob("*.npy")
    ):

        return {
            "success": False,
            "error": (
                "Nenhum dataset .npy foi "
                "encontrado para treinamento."
            )
        }

    job_id = str(uuid.uuid4())

    _evento_cancelamento.clear()

    with _estado_lock:

        _estado.clear()

        _estado.update({
            "success": True,
            "job_id": job_id,
            "status": "preparando",
            "message": (
                "Preparando o treinamento."
            ),
            "epoch": 0,
            "total_epochs": epochs,
            "progress": 0.0,
            "accuracy": 0.0,
            "val_accuracy": 0.0,
            "loss": 0.0,
            "val_loss": 0.0,
            "finished": False,
            "failed": False,
            "cancelled": False,
            "error": None,
            "logs": [],
            "resultado": None
        })

    _thread_treinamento = threading.Thread(
        target=executar_treinamento,
        args=(
            job_id,
            epochs,
            batch_size
        ),
        daemon=True,
        name=f"treinamento-{job_id}"
    )

    _thread_treinamento.start()

    return {
        "success": True,
        "job_id": job_id,
        "status": "preparando",
        "message": "Treinamento iniciado."
    }



# CANCELAR TREINAMENTO


def cancelar_treinamento(job_id=None):

    with _estado_lock:

        job_atual = _estado.get("job_id")

        status_atual = _estado.get(
            "status"
        )

    if (
        job_id and
        job_atual and
        job_id != job_atual
    ):

        return {
            "success": False,
            "error": (
                "O identificador do treinamento "
                "não corresponde ao job atual."
            )
        }

    if status_atual not in [
        "preparando",
        "treinando",
        "avaliando",
        "salvando"
    ]:

        return {
            "success": False,
            "error": (
                "Não existe treinamento ativo "
                "para cancelar."
            )
        }

    if status_atual in [
        "avaliando",
        "salvando"
    ]:

        return {
            "success": False,
            "error": (
                "O treinamento já está na etapa "
                "final e não pode ser cancelado."
            )
        }

    _evento_cancelamento.set()

    _atualizar_estado(
        message=(
            "Cancelamento solicitado. "
            "Aguardando o fim da época atual."
        )
    )

    _adicionar_log(
        "Cancelamento solicitado pelo usuário."
    )

    return {
        "success": True,
        "message": (
            "Solicitação de cancelamento enviada."
        )
    }