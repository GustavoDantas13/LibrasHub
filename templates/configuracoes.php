<?php

session_start();

require_once "configs/config.php";

if (empty($_SESSION["usuario_id"])) {
    header("Location: ../index.php");
    exit;
}

$idUsuarioAtual = (int) $_SESSION["usuario_id"];

$stmt = $pdo->prepare("
    SELECT
        id_usuario,
        nm_usuario,
        email_usuario,
        tp_usuario,
        dt_usuario
    FROM usuario
    WHERE id_usuario = ?
    LIMIT 1
");

$stmt->execute([
    $idUsuarioAtual
]);

$usuarioAtual = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$usuarioAtual) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../index.php");
    exit;
}

$_SESSION["usuario_tipo"] =
    $usuarioAtual["tp_usuario"];

$ehAdmin =
    strcasecmp(
        trim(
            $usuarioAtual["tp_usuario"]
        ),
        "Administrador"
    ) === 0;

$erro = "";
$sucesso = "";

if (empty($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(random_bytes(24));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_conta') {
    $csrfValido = hash_equals((string)$_SESSION['account_csrf'], (string)($_POST['csrf'] ?? ''));
    $confirmacaoValida = trim((string)($_POST['confirmacao'] ?? '')) === 'EXCLUIR';
    if (!$csrfValido || !$confirmacaoValida) {
        $erro = 'Confirmação de exclusão inválida.';
    } else {
        try {
            authRevokeRememberToken($pdo);
            $stmt = $pdo->prepare('DELETE FROM usuario WHERE id_usuario=?');
            $stmt->execute([$idUsuarioAtual]);
            if (!$stmt->rowCount()) throw new RuntimeException('Conta não encontrada.');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$cookie['path'],'domain'=>$cookie['domain'],'secure'=>$cookie['secure'],'httponly'=>$cookie['httponly'],'samesite'=>$cookie['samesite'] ?? 'Lax']);
            }
            session_destroy();
            header('Location: ../index.php', true, 303);
            exit;
        } catch (Throwable $e) {
            $erro = 'Não foi possível excluir sua conta. Existem registros vinculados a ela.';
        }
    }
}

if (
    isset($_GET["acao"]) &&
    $_GET["acao"] === "exportar_dados"
) {
    $stmt = $pdo->prepare("
        SELECT
            h.id_historico,
            h.id_gesto,
            g.nm_gesto,
            h.url_arquivo,
            h.texto_resultado,
            h.salvo,
            h.criado_em
        FROM historico h
        LEFT JOIN gesto g
            ON g.id_gesto = h.id_gesto
        WHERE h.id_usuario = ?
        ORDER BY h.criado_em DESC
    ");

    $stmt->execute([
        $idUsuarioAtual
    ]);

    $historico = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    $dados = [
        "usuario" => [
            "id_usuario" =>
                (int) $usuarioAtual["id_usuario"],
            "nome" =>
                $usuarioAtual["nm_usuario"],
            "email" =>
                $usuarioAtual["email_usuario"],
            "tipo" =>
                $usuarioAtual["tp_usuario"],
            "membro_desde" =>
                $usuarioAtual["dt_usuario"]
        ],
        "historico" => $historico
    ];

    header(
        "Content-Type: application/json; charset=utf-8"
    );

    header(
        'Content-Disposition: attachment; filename="librashub_dados.json"'
    );

    echo json_encode(
        $dados,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["acao"] ?? "") === "limpar_historico"
) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM historico
            WHERE id_usuario = ? AND salvo = 0
        ");

        $stmt->execute([
            $idUsuarioAtual
        ]);

        $sucesso =
            "Histórico não salvo removido. As traduções salvas foram preservadas.";

    } catch (PDOException $e) {
        $erro =
            "Não foi possível limpar o histórico.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, viewport-fit=cover"
>

<link
    rel="icon"
    type="image/png"
    href="../static/images/librashub-logo.png"
>

<title>
    LibrasHub - Configurações
</title>

<link
    rel="stylesheet"
    href="../static/css/style.css"
>
<link rel="stylesheet" href="../static/css/app-shell.css">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
    integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
>

<script>
try {
    const saved = JSON.parse(localStorage.getItem('librashub_accessibility_v2') || '{}');
    const selected = saved.theme || 'auto';
    const effective = selected === 'auto' ? (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light') : selected;
    document.documentElement.dataset.theme = effective;
    document.documentElement.style.setProperty('--font-scale', (Number(saved.font) || 100) / 100);
    document.documentElement.classList.toggle('high-contrast', Boolean(saved.contrast));
} catch (error) {}
</script>

<style>

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    overflow:hidden;
}

.content{
    margin-left:260px;
    flex:1;
    padding:40px;
}

.seg-row{
    display:flex;
    gap:8px;
    margin:10px 0 16px 0;
}

.seg-btn{
    flex:1;
    padding:9px;
    text-align:center;
    border:1px solid var(--border);
    border-radius:8px;
    font-size:0.8125rem;
    cursor:pointer;
    background:var(--surface);
    color:var(--text);
}

.seg-btn.active{
    background:var(--primary);
    color:var(--primary-text);
    border-color:var(--primary);
    font-weight:600;
}

.row-between{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    padding:12px 0;
    border-bottom:1px solid var(--bg);
}

.row-between:last-child{
    border-bottom:none;
}

.row-title{
    font-size:0.875rem;
    font-weight:600;
}

.row-desc{
    font-size:0.75rem;
    color:var(--text-muted);
}

.link-item{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 0;
    border:0;
    border-bottom:1px solid var(--bg);
    background:transparent;
    color:var(--text);
    font:inherit;
    font-size:0.875rem;
    text-align:left;
    cursor:pointer;
}

.link-item:last-child{
    border-bottom:none;
}

.link-item:hover{
    color:var(--text-muted);
}

.settings-form{
    margin:0;
}

.settings-alert{
    max-width:640px;
    margin-bottom:16px;
}

.settings-section{max-width:760px;margin-bottom:16px}.settings-section>.row-desc{margin:-4px 0 16px}.account-actions{display:grid;gap:8px}.account-action{width:100%;min-height:64px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--surface);color:var(--text);font:inherit;text-align:left;text-decoration:none;cursor:pointer}.account-action>span{display:grid;grid-template-columns:24px 1fr;align-items:center;column-gap:10px}.account-action>span>i{grid-row:1/3;color:var(--primary)}.account-action strong,.account-action small{display:block}.account-action small{color:var(--text-muted)}.account-action--danger{border-color:color-mix(in srgb,var(--danger) 55%,var(--border))}.account-action--danger>span>i,.account-action--danger>i{color:var(--danger)}
.settings-dialog{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:1rem;background:rgba(3,9,20,.72);backdrop-filter:blur(5px)}.settings-dialog[hidden]{display:none}.settings-dialog__card{width:min(100%,34rem);max-height:90dvh;overflow-y:auto;margin:0;padding:clamp(1rem,3vw,1.5rem)}.settings-dialog__card header,.settings-dialog__card footer{display:flex;align-items:center;justify-content:space-between;gap:1rem}.settings-dialog__card header{padding-bottom:.8rem;border-bottom:1px solid var(--border)}.settings-dialog__card header h2{margin:0}.settings-dialog__card header button{width:40px;height:40px;border:1px solid var(--border);border-radius:50%;background:var(--surface);color:var(--text)}.settings-dialog__card footer{margin-top:1rem;justify-content:flex-end}.settings-dialog__card .btn-danger,.settings-dialog__card .btn-cancel{min-height:44px;padding:.65rem 1rem;border-radius:10px}

@media(max-width:900px){

    .content{
        margin-left:0;
        padding:20px;
    }

}

@media(max-width:600px){

    .seg-row{
        flex-direction:column;
    }

    .row-between{
        align-items:flex-start;
    }

}


/* ===== RESPONSIVIDADE COMPLETA DAS CONFIGURAÇÕES ===== */
*{
    box-sizing:border-box;
}

html,
body{
    width:100%;
    max-width:100%;
    overflow-x:hidden;
}

body{
    min-height:100vh;
    min-height:100dvh;
}

.sidebar{
    z-index:1200;
    transition:transform .28s ease;
}

.content{
    min-width:0;
}

.panel{
    width:100%;
    min-width:0;
}

.settings-alert{
    width:100%;
    max-width:640px;
    overflow-wrap:anywhere;
}

.row-between > div{
    min-width:0;
}

.row-title,
.row-desc,
.link-item span{
    overflow-wrap:anywhere;
}

.field select{
    width:100%;
    max-width:100%;
    min-height:44px;
}

.seg-btn,
.link-item{
    min-height:44px;
}

/* botão hambúrguer */
.menu-toggle{
    display:none;
    position:fixed;
    top:16px;
    left:16px;
    z-index:1400;
    width:46px;
    height:46px;
    align-items:center;
    justify-content:center;
    border:1px solid var(--border);
    border-radius:12px;
    background:var(--surface);
    color:var(--text);
    font-size:1.35rem;
    cursor:pointer;
    box-shadow:0 8px 22px rgba(0,0,0,.14);
}

/* fundo escuro do menu mobile */
.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    z-index:1100;
    background:rgba(0,0,0,.45);
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease;
}

.sidebar-overlay.open{
    opacity:1;
    pointer-events:auto;
}

@media(max-width:1100px){
    .content{
        padding:30px;
    }
}

@media(max-width:900px){
    .sidebar{
        width:min(82vw,300px);
        height:100vh;
        height:100dvh;
        transform:translateX(-105%);
        box-shadow:10px 0 30px rgba(0,0,0,.18);
    }

    .sidebar.open{
        transform:translateX(0);
    }

    .content{
        margin-left:0;
        padding:82px 20px 28px;
    }

    .menu-toggle{
        display:flex;
    }

    .sidebar-overlay{
        display:block;
    }

    .panel,
    .settings-alert{
        max-width:none !important;
    }
}

@media(max-width:600px){
    .content{
        padding:78px 14px 22px;
    }

    .menu-toggle{
        top:12px;
        left:12px;
        width:44px;
        height:44px;
    }

    .page-title{
        font-size:clamp(1.45rem,7vw,1.9rem);
        line-height:1.2;
    }

    .panel{
        padding:14px;
        border-radius:10px;
    }

    .seg-row{
        display:grid;
        grid-template-columns:1fr;
        gap:8px;
    }

    .seg-btn{
        width:100%;
        min-height:48px;
        font-size:.9rem;
    }

    .row-between{
        align-items:center;
        gap:14px;
    }

    .row-between > div{
        flex:1;
    }

    .row-title{
        font-size:.9rem;
    }

    .row-desc{
        font-size:.78rem;
        line-height:1.4;
    }

    .toggle{
        flex-shrink:0;
    }

    .field select{
        min-height:48px;
        font-size:16px;
    }

    .link-item{
        min-height:48px;
        font-size:.9rem;
        gap:10px;
    }

    .settings-alert{
        font-size:.9rem;
    }
}

@media(max-width:380px){
    .content{
        padding-left:10px;
        padding-right:10px;
    }

    .panel{
        padding:12px;
        border-radius:8px;
    }

    .row-between{
        gap:10px;
    }

    .row-title{
        font-size:.86rem;
    }

    .row-desc{
        font-size:.74rem;
    }

    .link-item{
        font-size:.86rem;
    }
}

</style>

<link rel="stylesheet" href="../static/css/sidebar.css">
</head>

<body class="app-shell app-dashboard settings-page">


<?php $sidebarId = "sidebarMenu"; include __DIR__ . "/partials/sidebar.php"; ?>


<main class="content">

    <div class="page-title">
        Configurações
    </div>


    <?php if ($erro !== ""): ?>

        <div class="alert alert-error settings-alert">

            <span aria-hidden="true">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </span>

            <span>
                <?= htmlspecialchars(
                    $erro,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </span>

        </div>

    <?php endif; ?>


    <?php if ($sucesso !== ""): ?>

        <div class="alert alert-success settings-alert">

            <span aria-hidden="true">
                <i class="fa-solid fa-check" aria-hidden="true"></i>
            </span>

            <span>
                <?= htmlspecialchars(
                    $sucesso,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </span>

        </div>

    <?php endif; ?>


    <section class="panel settings-section">
        <div class="section-title"><i class="fa-solid fa-universal-access" aria-hidden="true"></i> Acessibilidade</div>
        <p class="row-desc">Todas as preferências visuais e de leitura são salvas em uma única configuração global.</p>
        <div class="settings-a11y" data-a11y-settings></div>
    </section>

    <section class="panel settings-section">
        <div class="section-title"><i class="fa-solid fa-globe" aria-hidden="true"></i> Idioma</div>
        <div class="field">
            <label for="idiomaInterface">Idioma da Interface</label>
            <select id="idiomaInterface" onchange="setInterfaceLanguage(this.value)">
                <option value="pt-BR">Português (Brasil)</option>
                <option value="en">English</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
            </select>
        </div>
    </section>

    <section class="panel settings-section account-actions" aria-labelledby="accountActionsTitle">
        <div class="section-title" id="accountActionsTitle"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Conta</div>
        <a class="account-action" href="logout.php"><span><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><strong>Sair da conta</strong><small>Encerrar a sessão neste dispositivo</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        <button class="account-action account-action--danger" type="button" id="openDeleteAccount"><span><i class="fa-solid fa-trash" aria-hidden="true"></i><strong>Excluir conta</strong><small>Apagar permanentemente a conta e os dados associados</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
    </section>


    
</main>

<div class="settings-dialog" id="deleteAccountDialog" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle" hidden>
    <form class="settings-dialog__card panel" method="post" action="configuracoes.php">
        <header><h2 id="deleteAccountTitle">Excluir conta</h2><button type="button" data-delete-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header>
        <p>Esta ação é permanente. Digite <strong>EXCLUIR</strong> para confirmar.</p>
        <input type="hidden" name="acao" value="excluir_conta">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['account_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="field"><label for="deleteConfirmation">Confirmação</label><input id="deleteConfirmation" name="confirmacao" autocomplete="off" required pattern="EXCLUIR" placeholder="EXCLUIR"></div>
        <footer><button class="btn-cancel" type="button" data-delete-close>Cancelar</button><button class="btn-danger" type="submit">Excluir permanentemente</button></footer>
    </form>
</div>




<script>
const deleteAccountDialog=document.getElementById('deleteAccountDialog');
const closeDeleteAccount=()=>{deleteAccountDialog.hidden=true;document.body.style.overflow=''};
document.getElementById('openDeleteAccount')?.addEventListener('click',()=>{deleteAccountDialog.hidden=false;document.body.style.overflow='hidden';setTimeout(()=>document.getElementById('deleteConfirmation')?.focus(),0)});
deleteAccountDialog?.addEventListener('click',event=>{if(event.target===deleteAccountDialog||event.target.closest('[data-delete-close]'))closeDeleteAccount()});
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!deleteAccountDialog.hidden)closeDeleteAccount()});
const languageSelect=document.getElementById('idiomaInterface');
if(languageSelect)languageSelect.value=localStorage.getItem('libras_idioma')||'pt-BR';
</script>
<script src="../static/js/acessibility.js?v=67" defer></script>
</body>

</html>
