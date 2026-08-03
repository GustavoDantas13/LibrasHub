import numpy as np





# Data argumentacion (mexe nas variações e aumenta a precisão)

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

