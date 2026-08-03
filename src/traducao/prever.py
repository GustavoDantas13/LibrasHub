import numpy as np
from tensorflow.keras.models import load_model

modelo = load_model("modelo_gestos.keras")

labels = np.load("labels.npy")


def prever(sequencia):

    print(sequencia.shape)
    pred = modelo.predict(sequencia, verbose=0)[0]

    top = np.argsort(pred)[::-1][:10]

    print("\n========================")

    for i in top:
        print(f"{labels[i]:15s} -> {pred[i]*100:.2f}%")

    print("========================\n")

    indice = top[0]

    return labels[indice], float(pred[indice])
