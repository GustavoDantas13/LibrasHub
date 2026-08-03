
import numpy as np



# scaler salvo durante o treinamento
scaler_mean = np.load("scaler_mean.npy")
scaler_scale = np.load("scaler_scale.npy")

# evita divisão por zero
scaler_scale[scaler_scale == 0] = 1



# * Usado na tradução e nos datasets

def normalizar_mao(mao): 
    wrist = mao.landmark[0] 
    middle = mao.landmark[9]

    escala = np.sqrt( 
        (middle.x - wrist.x) ** 2 + (middle.y - wrist.y) ** 2 + (middle.z - wrist.z) ** 2 ) 
        
    escala = max(escala, 1e-6) 
        
    pontos = [] 
        
    for lm in mao.landmark: 
        pontos.extend([ 
            (lm.x - wrist.x) / escala, 
            (lm.y - wrist.y) / escala, 
            (lm.z - wrist.z) / escala 
            ]) 
            
    return pontos


# ! normaliza a sequencia de gestos como um todo (usado apenas na tradução)

def normalizar(sequencia):

    sequencia = (
        sequencia - scaler_mean
    ) / scaler_scale

    return sequencia.astype(np.float32)