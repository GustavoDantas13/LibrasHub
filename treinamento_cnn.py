import os
import numpy as np

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder

from sklearn.preprocessing import StandardScaler

from sklearn.utils.class_weight import compute_class_weight

from sklearn.metrics import (
    classification_report,
    confusion_matrix
)

from tensorflow.keras.regularizers import l2

import matplotlib.pyplot as plt

from tensorflow.keras.utils import to_categorical

from tensorflow.keras.models import Model

from tensorflow.keras.layers import (
    Input,
    Conv1D,
    MaxPooling1D,
    BatchNormalization,
    Bidirectional,
    LSTM,
    Dropout,
    Dense,
    GlobalAveragePooling1D,
    MultiHeadAttention,
    LayerNormalization,
    Add
)

from tensorflow.keras.callbacks import (
    EarlyStopping,
    ReduceLROnPlateau,
    ModelCheckpoint
)

# =======================================
# CARREGAMENTO
# =======================================

DATASET_DIR = "dataset_processado"

X = []
y = []

for arquivo in sorted(os.listdir(DATASET_DIR)):

    if not arquivo.endswith(".npy"):
        continue

    gesto = os.path.splitext(arquivo)[0]

    print(f"📂 {gesto}")

    dados = np.load(os.path.join(DATASET_DIR, arquivo))

    for amostra in dados:
        X.append(amostra)
        y.append(gesto)

X = np.array(X, dtype=np.float32)
y = np.array(y)

print()
print("Total:", len(X))
print("Formato:", X.shape)

TIMESTEPS = X.shape[1]
FEATURES = X.shape[2]

print(f"\nSequência: {TIMESTEPS}")
print(f"Features: {FEATURES}")

# ======================================
# NORMALIZAÇÃO
# ======================================

scaler = StandardScaler()

X = X.reshape(-1, FEATURES)

X = scaler.fit_transform(X)

X = X.reshape(-1, TIMESTEPS, FEATURES)

np.save("scaler_mean.npy", scaler.mean_)
np.save("scaler_scale.npy", scaler.scale_)

# esperado:
# (4200,30,63)

# =======================================
# LABELS
# =======================================

encoder = LabelEncoder()

y = encoder.fit_transform(y)

y = to_categorical(y)

# =======================================
# TREINO / TESTE
# =======================================

X_train, X_test, y_train, y_test = train_test_split(

    X,
    y,
    test_size=0.20,
    random_state=42,
    stratify=y

)

classes = np.argmax(y_train, axis=1)

pesos = compute_class_weight(

    class_weight="balanced",

    classes=np.unique(classes),

    y=classes

)

class_weight = dict(enumerate(pesos))

# =======================================
# MODELO
# =======================================

# Descobre automaticamente o formato do dataset
TIMESTEPS = X.shape[1]
FEATURES = X.shape[2]

print(f"\nSequência: {TIMESTEPS} frames")
print(f"Features por frame: {FEATURES}")

entrada = Input(shape=(TIMESTEPS, FEATURES))

# ==========================
# Bloco CNN 1
# ==========================

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

x = MaxPooling1D(pool_size=2)(x)

# ==========================
# Bloco CNN 2
# ==========================

x = Conv1D(
    128,
    kernel_size=3,
    padding="same",
    activation="relu"
)(x)

x = BatchNormalization()(x)

x = MaxPooling1D(pool_size=2)(x)

# ==========================
# BiLSTM
# ==========================

x = Bidirectional(
    LSTM(
        64,
        return_sequences=True
    )
)(x)

x = Dropout(0.30)(x)

# ==========================
# Self Attention
# ==========================

attention = MultiHeadAttention(
    num_heads=4,
    key_dim=64
)(x, x)

x = Add()([x, attention])

x = LayerNormalization()(x)

# ==========================
# Segunda BiLSTM
# ==========================

x = Bidirectional(
    LSTM(
        32,
        return_sequences=True
    )
)(x)

# ==========================
# Pooling
# ==========================

x = GlobalAveragePooling1D()(x)

# ==========================
# Classificador
# ==========================

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
    len(encoder.classes_),
    activation="softmax"
)(x)

model = Model(inputs=entrada, outputs=saida)

# =======================================
# COMPILAÇÃO
model.compile(

    optimizer="adam",

    loss="categorical_crossentropy",

    metrics=["accuracy"]

)

model.summary()

# =======================================
# CALLBACKS
# =======================================

callbacks=[

    EarlyStopping(

        monitor="val_loss",

        patience=20,

        restore_best_weights=True

    ),

    ReduceLROnPlateau(

        monitor="val_loss",

        factor=0.5,

        patience=7

    ),

    ModelCheckpoint(

        "modelo_gestos.keras",

        save_best_only=True

    )

]

# =======================================
# TREINAMENTO
# =======================================

print("\n🚀 Treinando...\n")

history = model.fit(

    X_train,

    y_train,

    validation_data=(X_test, y_test),

    epochs=120,

    batch_size=16,

    class_weight=class_weight,

    callbacks=callbacks,

    verbose=1

)

# =======================================

loss,acc=model.evaluate(X_test,y_test)

print(f"\nAcurácia: {acc*100:.2f}%")

y_real = np.argmax(y_test, axis=1)

y_pred = np.argmax(

    model.predict(X_test),

    axis=1

)

print()

print(

    classification_report(

        y_real,

        y_pred,

        target_names=encoder.classes_

    )

)

print()

print(

    confusion_matrix(

        y_real,

        y_pred

    )

)

# =======================================

np.save(

    "labels.npy",

    encoder.classes_

)

print("\nModelo salvo.")
print("Labels salvos.")

plt.figure(figsize=(10,5))

plt.plot(history.history["accuracy"])

plt.plot(history.history["val_accuracy"])

plt.legend(["Treino","Validação"])

plt.grid(True)

plt.savefig("accuracy.png")

plt.close()

plt.figure(figsize=(10,5))

plt.plot(history.history["loss"])

plt.plot(history.history["val_loss"])

plt.legend(["Treino","Validação"])

plt.grid(True)

plt.savefig("loss.png")

plt.close()