import os
import numpy as np

from src.traducao.processar_imagem import (
    processar_imagem_dataset
)

from src.traducao.processar_video import (
    processar_video_dataset
)

from src.traducao.augmentar import augmentar


SEQUENCE_LENGTH = 30


def criar_dataset(
    dataset_dir,
    output_dir,
    cancelar_evento=None
):

    def cancelado():

        return (
            cancelar_evento is not None
            and
            cancelar_evento.is_set()
        )


    def resposta_cancelada():

        for caminho in arquivos_gerados_nesta_execucao:

            if os.path.isfile(
                caminho
            ):

                try:

                    os.remove(
                        caminho
                    )

                except OSError:

                    pass

        return {
            "success": False,
            "cancelled": True,
            "error": "Criação do dataset cancelada.",
            "total_amostras": 0,
            "datasets_gerados": []
        }


    if not os.path.isdir(
        dataset_dir
    ):

        return {
            "success": False,
            "error": (
                "A pasta de entrada do dataset "
                "não foi encontrada."
            ),
            "total_amostras": 0,
            "datasets_gerados": []
        }


    os.makedirs(
        output_dir,
        exist_ok=True
    )


    total_amostras = 0
    datasets_gerados = []
    arquivos_gerados_nesta_execucao = []


    for gesto in sorted(
        os.listdir(
            dataset_dir
        )
    ):

        if cancelado():

            return resposta_cancelada()


        pasta = os.path.join(
            dataset_dir,
            gesto
        )


        if not os.path.isdir(
            pasta
        ):

            continue


        print()
        print(
            "Processando gesto:",
            gesto
        )


        amostras = []


        arquivos = sorted(
            os.listdir(
                pasta
            )
        )


        for arquivo in arquivos:

            if cancelado():

                return resposta_cancelada()


            caminho = os.path.join(
                pasta,
                arquivo
            )


            if not os.path.isfile(
                caminho
            ):

                continue


            ext = os.path.splitext(
                arquivo
            )[1].lower()


            try:

                if ext in [
                    ".jpg",
                    ".jpeg",
                    ".png"
                ]:

                    seq = (
                        processar_imagem_dataset(
                            caminho
                        )
                    )


                elif ext in [
                    ".mp4",
                    ".avi",
                    ".mov",
                    ".mkv"
                ]:

                    seq = (
                        processar_video_dataset(
                            caminho,
                            cancelar_evento=
                                cancelar_evento
                        )
                    )


                else:

                    continue


                if cancelado():

                    return resposta_cancelada()


                if seq is None:

                    print(
                        "   ✖",
                        arquivo,
                        "Sequência inválida."
                    )

                    continue


                amostras.append(
                    seq
                )


                if cancelado():

                    return resposta_cancelada()


                amostras.append(
                    augmentar(
                        seq
                    )
                )


                total_amostras += 2


                print(
                    "   ✔",
                    arquivo
                )


            except Exception as erro:

                if cancelado():

                    return resposta_cancelada()


                print(
                    "   ✖",
                    arquivo,
                    erro
                )


        if cancelado():

            return resposta_cancelada()


        if len(amostras) == 0:

            print(
                "Nenhuma amostra válida para:",
                gesto
            )

            continue


        amostras = np.array(
            amostras,
            dtype=np.float32
        )


        nome_arquivo = (
            gesto
            +
            ".npy"
        )


        caminho_saida = os.path.join(
            output_dir,
            nome_arquivo
        )


        np.save(
            caminho_saida,
            amostras
        )


        arquivos_gerados_nesta_execucao.append(
            caminho_saida
        )


        if cancelado():

            return resposta_cancelada()


        caminho_absoluto = os.path.abspath(
            caminho_saida
        )


        datasets_gerados.append({

            "gesto":
                gesto,

            "arquivo":
                nome_arquivo,

            "caminho":
                caminho_absoluto,

            "amostras":
                len(
                    amostras
                ),

            "shape":
                list(
                    amostras.shape
                )
        })


        print()

        print(
            gesto
        )

        print(
            "   Amostras:",
            len(
                amostras
            )
        )

        print(
            "   Shape:",
            amostras.shape
        )

        print(
            "   Dataset:",
            caminho_absoluto
        )


    if cancelado():

        return resposta_cancelada()


    if len(
        datasets_gerados
    ) == 0:

        return {
            "success": False,
            "error": (
                "Nenhum dataset foi gerado."
            ),
            "total_amostras": 0,
            "datasets_gerados": []
        }


    return {
        "success": True,

        "total_amostras":
            total_amostras,

        "datasets_processados":
            len(
                datasets_gerados
            ),

        "output":
            os.path.abspath(
                output_dir
            ),

        "datasets_gerados":
            datasets_gerados
    }
