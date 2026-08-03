


def suavizar(sequencia):

    if len(sequencia) <= 1:
        return sequencia

    nova = sequencia.copy()

    for i in range(1, len(nova)):

        nova[i] = (

            0.7 * nova[i]

            +

            0.3 * nova[i - 1]

        )

    return nova
