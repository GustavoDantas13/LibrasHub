import cv2
import os
import numpy as np
import mediapipe as mp

# ! Funções

from src.traducao.processar_imagem import processar_imagem_dataset
from src.traducao.processar_video import processar_video_dataset
from src.traducao.augmentar import augmentar
from src.traducao.extrair_landmarks import hands, pose

# CONFIGURAÇÕES


SEQUENCE_LENGTH = 30


def criar_dataset(dataset_dir, output_dir):

    os.makedirs(output_dir, exist_ok=True)

    total_amostras = 0

    for gesto in sorted(os.listdir(dataset_dir)):

        pasta = os.path.join(dataset_dir, gesto)

        if not os.path.isdir(pasta):
            continue

        print(f"\n {gesto}")

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

                    seq = processar_imagem_dataset(caminho)

                elif ext in [

                    ".mp4",

                    ".avi",

                    ".mov",

                    ".mkv"

                ]:

                    seq = processar_video_dataset(caminho)

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

                    output_dir,

                    gesto + ".npy"

                ),

                amostras

            )

            print()

            print(

                f"{gesto}"

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

                "Nenhuma amostra válida."

            )

        
    hands.close()
    pose.close()

    return {
        "success": True,
        "total_amostras": total_amostras,
        "output": output_dir
    }
