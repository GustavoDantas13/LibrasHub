<?php

session_start();

require_once "configs/config.php";
$communityColumn = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'is_community_user'")->fetch();
if (!$communityColumn) {
    $pdo->exec("ALTER TABLE usuario ADD COLUMN is_community_user TINYINT(1) NOT NULL DEFAULT 0 AFTER tp_usuario");
}

if (empty($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$idUsuario = (int) $_SESSION["usuario_id"];

$erro = "";
$sucesso = "";

function buscarUsuario($pdo, $id)
{
    $stmt = $pdo->prepare("
        SELECT
            id_usuario,
            nm_usuario,
            email_usuario,
            tp_usuario,
            is_community_user,
            foto_perfil,
            banner_perfil,
            status_visibilidade,
            ultimo_acesso_em,
            dt_usuario
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");

    $stmt->execute([
        $id
    ]);

    return $stmt->fetch(
        PDO::FETCH_ASSOC
    );
}

function salvarMidiaPerfil(array $arquivo, string $tipo, int $idUsuario, ?string $atual): ?string
{
    if (($arquivo["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $atual;
    }

    if (($arquivo["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo["tmp_name"] ?? "")) {
        throw new RuntimeException("Não foi possível receber a imagem de " . $tipo . ".");
    }

    if ((int) ($arquivo["size"] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException("A imagem de " . $tipo . " deve ter no máximo 5 MB.");
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo["tmp_name"]);
    $extensoes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    if (!isset($extensoes[$mime])) {
        throw new RuntimeException("Use JPG, PNG ou WebP na imagem de " . $tipo . ".");
    }

    $pasta = dirname(__DIR__) . "/static/uploads/perfis/" . $idUsuario;
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        throw new RuntimeException("Não foi possível preparar a pasta de imagens do perfil.");
    }

    $nome = $tipo . "_" . bin2hex(random_bytes(10)) . "." . $extensoes[$mime];
    $destino = $pasta . "/" . $nome;
    if (!move_uploaded_file($arquivo["tmp_name"], $destino)) {
        throw new RuntimeException("Não foi possível salvar a imagem de " . $tipo . ".");
    }

    if ($atual && str_starts_with($atual, "../static/uploads/perfis/")) {
        $antigo = dirname(__DIR__) . "/" . ltrim(substr($atual, 3), "/");
        if (is_file($antigo)) {
            @unlink($antigo);
        }
    }

    return "../static/uploads/perfis/" . $idUsuario . "/" . $nome;
}

$usuario = buscarUsuario(
    $pdo,
    $idUsuario
);

if (!$usuario) {
    session_destroy();

    header(
        "Location: login.php"
    );

    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["acao"] ?? "") === "atualizar_perfil"
) {

    $nome = trim(
        $_POST["nome"]
        ?? ""
    );

    $email = trim(
        $_POST["email"]
        ?? ""
    );

    $statusVisibilidade = ($_POST["status_visibilidade"] ?? "visivel") === "oculto"
        ? "oculto"
        : "visivel";

    if (
        $nome === ""
        ||
        $email === ""
    ) {

        $erro =
            "Preencha nome e email.";

    } elseif (
        mb_strlen($nome) < 2
    ) {

        $erro =
            "Informe um nome válido.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erro =
            "Email inválido.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT id_usuario
                FROM usuario
                WHERE
                    email_usuario = ?
                    AND id_usuario != ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $idUsuario
            ]);

            if ($stmt->fetch()) {

                $erro =
                    "Este email já está em uso por outra conta.";

            } else {

                $fotoPerfil = salvarMidiaPerfil(
                    $_FILES["foto_perfil"] ?? [],
                    "foto",
                    $idUsuario,
                    $usuario["foto_perfil"] ?? null
                );

                $bannerPerfil = salvarMidiaPerfil(
                    $_FILES["banner_perfil"] ?? [],
                    "banner",
                    $idUsuario,
                    $usuario["banner_perfil"] ?? null
                );

                $stmt = $pdo->prepare("
                    UPDATE usuario
                    SET
                        nm_usuario = ?,
                        email_usuario = ?,
                        foto_perfil = ?,
                        banner_perfil = ?,
                        status_visibilidade = ?
                    WHERE id_usuario = ?
                ");

                $stmt->execute([
                    $nome,
                    $email,
                    $fotoPerfil,
                    $bannerPerfil,
                    $statusVisibilidade,
                    $idUsuario
                ]);

                $_SESSION["usuario_nome"] =
                    $nome;

                $_SESSION["usuario_email"] =
                    $email;

                $sucesso =
                    "Perfil atualizado com sucesso!";

                $usuario = buscarUsuario(
                    $pdo,
                    $idUsuario
                );
            }

        } catch (RuntimeException $e) {

            $erro = $e->getMessage();

        } catch (PDOException $e) {

            $erro =
                "Não foi possível atualizar o perfil.";
        }
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["acao"] ?? "") === "excluir_conta"
) {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM usuario
            WHERE id_usuario = ?
        ");

        $stmt->execute([
            $idUsuario
        ]);

        if ($stmt->rowCount() > 0) {

            $_SESSION = [];

            if (
                ini_get(
                    "session.use_cookies"
                )
            ) {

                $params =
                    session_get_cookie_params();

                setcookie(
                    session_name(),
                    "",
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();

            header(
                "Location: login.php"
            );

            exit;

        } else {

            $erro =
                "Não foi possível localizar sua conta.";
        }

    } catch (PDOException $e) {

        $erro =
            "Não foi possível excluir sua conta. "
            . "Existem registros vinculados a ela.";
    }
}

$totalTraducoes = 0;
$totalCamera = 0;
$totalUploads = 0;

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM historico
        WHERE id_usuario = ?
    ");

    $stmt->execute([
        $idUsuario
    ]);

    $totalTraducoes =
        (int) $stmt->fetchColumn();


    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM historico
        WHERE
            id_usuario = ?
            AND (
                url_arquivo IS NULL
                OR TRIM(url_arquivo) = ''
            )
    ");

    $stmt->execute([
        $idUsuario
    ]);

    $totalCamera =
        (int) $stmt->fetchColumn();


    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM historico
        WHERE
            id_usuario = ?
            AND url_arquivo IS NOT NULL
            AND TRIM(url_arquivo) <> ''
    ");

    $stmt->execute([
        $idUsuario
    ]);

    $totalUploads =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $totalTraducoes = 0;
    $totalCamera = 0;
    $totalUploads = 0;
}

$inicial =
    strtoupper(
        mb_substr(
            $usuario["nm_usuario"],
            0,
            1
        )
    );

$membroDesde =
    date(
        "d/m/Y",
        strtotime(
            $usuario["dt_usuario"]
        )
    );

$ehAdmin =
    mb_strtolower(
        trim(
            $usuario["tp_usuario"]
        )
    ) === "administrador";

$statusOnline =
    ($usuario["status_visibilidade"] ?? "visivel") === "visivel"
    && !empty($usuario["ultimo_acesso_em"])
    && strtotime((string) $usuario["ultimo_acesso_em"]) >= time() - 300;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" type="image/png" href="../static/images/librashub-logo.png">

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, viewport-fit=cover"
>

<title>
    LibrasHub - Usuário
</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
>

<link
    rel="stylesheet"
    href="../static/css/style.css"
>
<link rel="stylesheet" href="../static/css/app-shell.css">

<script>

(function(){

    try{

        var K = {
            t:"libras_theme",
            f:"libras_fontsize",
            c:"libras_contrast"
        };

        var S = {
            pequena:.9,
            media:1,
            grande:1.15
        };

        function g(k,d){

            try{

                var v =
                    localStorage.getItem(k);

                return v !== null
                    ? v
                    : d;

            }catch(e){

                return d;
            }
        }

        var t =
            g(
                K.t,
                "claro"
            );

        var ef =
            t;

        if(
            t === "automatico"
        ){

            ef =
                (
                    window.matchMedia
                    &&
                    window.matchMedia(
                        "(prefers-color-scheme:dark)"
                    ).matches
                )
                    ? "escuro"
                    : "claro";
        }

        if(
            ef === "escuro"
        ){

            document.documentElement
                .setAttribute(
                    "data-theme",
                    "dark"
                );
        }

        document.documentElement
            .style
            .setProperty(
                "--font-scale",
                S[
                    g(
                        K.f,
                        "media"
                    )
                ] || 1
            );

        if(
            g(
                K.c,
                "off"
            ) === "on"
        ){

            document.documentElement
                .classList
                .add(
                    "high-contrast"
                );
        }

    }catch(e){}

})();

</script>

<style>

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    overflow-y:auto;
}

.content{
    margin-left:260px;
    flex:1;
    padding:40px;
}

.perfil-layout{
    display:grid;
    grid-template-columns:280px 1fr;
    gap:20px;
}

.avatar-lg{
    width:72px;
    height:72px;
    border-radius:50%;
    background:linear-gradient(
        135deg,
        var(--primary),
        color-mix(
            in srgb,
            var(--primary) 60%,
            #000
        )
    );
    color:var(--primary-text);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.5rem;
    font-weight:800;
    margin-bottom:12px;
    box-shadow:
        0 4px 16px
        color-mix(
            in srgb,
            var(--primary) 35%,
            transparent
        );
}

.user-name{
    font-weight:700;
    font-size:1rem;
    margin-bottom:2px;
}

.user-email{
    font-size:.75rem;
    color:var(--text-muted);
    margin-bottom:8px;
    overflow-wrap:anywhere;
}

.badge-tipo{
    display:inline-flex;
    align-items:center;
    gap:5px;
    font-size:.6875rem;
    font-weight:600;
    background:
        color-mix(
            in srgb,
            var(--primary) 12%,
            transparent
        );
    color:
        var(
            --primary-link,
            var(--primary)
        );
    border:1px solid
        color-mix(
            in srgb,
            var(--primary) 30%,
            transparent
        );
    border-radius:20px;
    padding:3px 10px;
}

.stats-row{
    display:flex;
    justify-content:space-between;
    margin:18px 0;
    text-align:center;
}

.stat-num{
    font-size:1.25rem;
    font-weight:800;
}

.stat-label{
    font-size:.6875rem;
    color:var(--text-muted);
    margin-top:2px;
}

.stat-divider{
    width:1px;
    background:var(--border);
    align-self:stretch;
    margin:4px 0;
}

.menu-item{
    padding:10px 0;
    font-size:.875rem;
    display:flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    color:var(--text);
    border-radius:6px;
    transition:color .15s;
}

.menu-item:hover{
    color:
        var(
            --primary-link,
            var(--primary)
        );
}

.menu-item.active{
    font-weight:700;
    color:
        var(
            --primary-link,
            var(--primary)
        );
}

.logout-btn{
    margin-top:20px;
    width:100%;
    padding:10px 14px;
    border:1px solid var(--border);
    border-radius:8px;
    background:var(--surface);
    cursor:pointer;
    font-size:.875rem;
    color:var(--text);
    text-align:left;
    display:flex;
    align-items:center;
    gap:8px;
    transition:
        border-color .15s,
        color .15s;
}

.logout-btn:hover{
    border-color:var(--danger);
    color:var(--danger);
}

.info-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.info-title{
    font-size:.9375rem;
    font-weight:700;
}

.info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:0 20px;
}

.info-field{
    padding:14px 0;
    border-bottom:1px solid var(--border);
}

.info-field:nth-last-child(-n+2){
    border-bottom:none;
}

.info-label{
    font-size:.6875rem;
    font-weight:700;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:.5px;
    margin-bottom:4px;
}

.info-value{
    font-size:.875rem;
    font-weight:500;
    overflow-wrap:anywhere;
}

.member-since{
    font-size:.75rem;
    color:var(--text-muted);
    margin-top:16px;
    padding-top:14px;
    border-top:1px solid var(--border);
}

.danger-box{
    border:1px solid var(--danger);
    border-radius:10px;
    padding:18px 20px;
    margin-top:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
}

.danger-info .danger-title{
    color:var(--danger);
    font-size:.75rem;
    font-weight:700;
    margin-bottom:4px;
}

.danger-info .danger-desc{
    font-size:.75rem;
    color:var(--text-muted);
    line-height:1.5;
}

.modal-backdrop{
    position:fixed;
    inset:0;
    z-index:2000;
    background:rgba(0,0,0,.55);
    display:flex;
    align-items:center;
    justify-content:center;
    opacity:0;
    visibility:hidden;
    transition:
        opacity .2s,
        visibility .2s;
    padding:20px;
}

.modal-backdrop.open{
    opacity:1;
    visibility:visible;
}

.modal{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:16px;
    width:100%;
    max-width:480px;
    transform:
        translateY(16px)
        scale(.97);
    transition:
        transform .22s
        cubic-bezier(
            .34,
            1.3,
            .64,
            1
        );
    overflow:hidden;
    box-shadow:
        0 20px 60px
        rgba(0,0,0,.25);
}

.modal-backdrop.open .modal{
    transform:
        translateY(0)
        scale(1);
}

.modal-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:20px 24px 16px;
    border-bottom:1px solid var(--border);
}

.modal-title{
    font-size:1rem;
    font-weight:700;
}

.modal-close{
    width:32px;
    height:32px;
    border-radius:50%;
    border:none;
    background:var(--bg);
    color:var(--text);
    cursor:pointer;
    font-size:1rem;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:background .15s;
}

.modal-close:hover{
    background:var(--border);
}

.modal-body{
    padding:22px 24px;
}

.modal-footer{
    padding:16px 24px 20px;
    display:flex;
    gap:10px;
    justify-content:flex-end;
    border-top:1px solid var(--border);
}

.danger-modal-icon{
    width:56px;
    height:56px;
    border-radius:50%;
    background:
        color-mix(
            in srgb,
            var(--danger) 12%,
            transparent
        );
    color:var(--danger);
    font-size:1.5rem;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 16px;
}

.danger-modal-text{
    text-align:center;
}

.danger-modal-text p{
    font-size:.875rem;
    color:var(--text-muted);
    line-height:1.6;
    margin-top:6px;
}

.confirm-input-wrap{
    margin-top:18px;
}

.confirm-input-wrap label{
    font-size:.75rem;
    font-weight:700;
    color:var(--text-muted);
    display:block;
    margin-bottom:6px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.confirm-input-wrap input{
    width:100%;
    padding:10px 12px;
    border:1px solid var(--border);
    border-radius:8px;
    font-size:.875rem;
    background:var(--input-bg);
    color:var(--input-text);
    font-family:inherit;
    text-align:center;
    letter-spacing:2px;
    font-weight:700;
    transition:border-color .15s;
}

.confirm-input-wrap input:focus{
    outline:none;
    border-color:var(--danger);
    box-shadow:
        0 0 0 3px
        color-mix(
            in srgb,
            var(--danger) 18%,
            transparent
        );
}

.btn-cancel{
    background:var(--bg);
    color:var(--text);
    border:1px solid var(--border);
    border-radius:8px;
    padding:10px 18px;
    font-size:.875rem;
    font-weight:600;
    cursor:pointer;
    transition:.15s;
}

.btn-cancel:hover{
    border-color:var(--text-muted);
}

.modal-alert{
    padding:10px 12px;
    border-radius:8px;
    font-size:.8125rem;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:14px;
}

.modal-alert.error{
    background:var(--danger);
    color:var(--danger-text);
}

.modal-alert.success{
    background:var(--success);
    color:var(--success-text);
}

@media (max-width:768px){

    .perfil-layout{
        grid-template-columns:1fr;
    }

    .sidebar{
        width:260px;
    }

    .content{
        margin-left:0;
    }

}

@media (max-width:600px){

    .info-grid{
        grid-template-columns:1fr;
    }

    .info-field{
        border-bottom:1px solid var(--border) !important;
    }

    .info-field:last-child{
        border-bottom:none !important;
    }

    .info-header{
        align-items:flex-start;
        gap:12px;
    }

    .danger-box{
        flex-direction:column;
        align-items:flex-start;
    }

    .danger-box .btn-danger{
        width:100%;
        justify-content:center;
    }

}


/* ===== RESPONSIVIDADE COMPLETA DO PERFIL ===== */
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

.perfil-layout > *,
.panel,
.info-grid,
.info-field,
.danger-box,
.modal,
.modal-body,
.modal-footer{
    min-width:0;
}

.alert{
    width:100%;
    max-width:760px !important;
    overflow-wrap:anywhere;
}

.user-name,
.user-email,
.info-value,
.danger-desc,
.member-since{
    overflow-wrap:anywhere;
    word-break:break-word;
}

.logout-btn,
.btn-danger,
.btn-cancel,
.btn,
.menu-item{
    min-height:44px;
}

/* ===== BOTÃO HAMBÚRGUER ===== */
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

/* ===== OVERLAY DO MENU ===== */
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

    .perfil-layout{
        grid-template-columns:minmax(240px,280px) minmax(0,1fr);
        gap:16px;
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

    .perfil-layout{
        grid-template-columns:1fr;
    }

    .perfil-layout > .panel:first-child{
        width:100%;
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

    .panel{
        padding:14px;
        border-radius:10px;
    }

    .avatar-lg{
        width:64px;
        height:64px;
        font-size:1.3rem;
    }

    .stats-row{
        gap:10px;
    }

    .stat-num{
        font-size:1.1rem;
    }

    .stat-label{
        font-size:.64rem;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .info-field{
        border-bottom:1px solid var(--border) !important;
    }

    .info-field:last-child{
        border-bottom:none !important;
    }

    .info-header{
        flex-direction:column;
        align-items:stretch;
        gap:12px;
    }

    .info-header .btn{
        width:100%;
        justify-content:center;
        min-height:48px;
    }

    .danger-box{
        flex-direction:column;
        align-items:stretch;
        padding:16px;
    }

    .danger-box .btn-danger{
        width:100%;
        justify-content:center;
        min-height:48px;
        white-space:normal !important;
    }

    .modal-backdrop{
        padding:12px;
        align-items:flex-end;
    }

    .modal{
        max-width:none;
        border-radius:16px 16px 0 0;
        max-height:92dvh;
        overflow-y:auto;
    }

    .modal-header{
        padding:16px 16px 13px;
    }

    .modal-body{
        padding:18px 16px;
    }

    .modal-footer{
        padding:14px 16px 18px;
        flex-direction:column-reverse;
    }

    .modal-footer > button,
    .modal-footer > form,
    .modal-footer > form > button{
        width:100%;
    }

    .modal-footer .btn,
    .modal-footer .btn-cancel,
    .modal-footer .btn-danger{
        min-height:48px;
        justify-content:center;
    }

    .confirm-input-wrap input,
    .field input{
        font-size:16px;
        min-height:48px;
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

    .stats-row{
        margin:14px 0;
    }

    .stat-divider{
        display:none;
    }

    .stats-row{
        display:grid;
        grid-template-columns:repeat(3,1fr);
    }

    .modal-backdrop{
        padding:0;
    }

    .modal{
        border-radius:14px 14px 0 0;
    }
}

</style>

<link rel="stylesheet" href="../static/css/sidebar.css">
</head>

<body>


<?php $sidebarId = "sidebarMenu"; include __DIR__ . "/partials/sidebar.php"; ?>


<main class="content">


    <?php if ($erro): ?>

        <div
            class="alert alert-error"
            style="
                max-width:760px;
                margin-bottom:16px;
            "
        >
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


    <?php if ($sucesso): ?>

        <div
            class="alert alert-success"
            style="
                max-width:760px;
                margin-bottom:16px;
            "
        >
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


    <div class="perfil-layout">


        <div class="panel">


            <div class="avatar-lg">

                <?php if (!empty($usuario["foto_perfil"])): ?>
                    <img src="<?= htmlspecialchars((string) $usuario["foto_perfil"], ENT_QUOTES, "UTF-8") ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                <?php else: ?>
                    <?= htmlspecialchars($inicial, ENT_QUOTES, "UTF-8") ?>
                <?php endif; ?>

            </div>


            <div class="user-name">

                <?= htmlspecialchars(
                    $usuario["nm_usuario"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

            <?php if ($ehAdmin): ?>
                <span class="community-badge"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Administrador</span>
            <?php elseif (!empty($usuario["is_community_user"])): ?>
                <span class="community-badge"><i class="fa-solid fa-certificate" aria-hidden="true"></i> Colaborador da comunidade</span>
            <?php endif; ?>


            <div class="user-email">

                <?= htmlspecialchars(
                    $usuario["email_usuario"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>


            <div class="badge-tipo">

                <i
                    class="fa-solid fa-circle-check"
                    style="font-size:.6rem;"
                ></i>

                <?= htmlspecialchars(
                    $usuario["tp_usuario"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>


            <div class="stats-row">


                <div>

                    <div class="stat-num">

                        <?= $totalTraducoes ?>

                    </div>

                    <div class="stat-label">

                        Traduções

                    </div>

                </div>


                <div class="stat-divider"></div>


                <div>

                    <div class="stat-num">

                        <?= $totalCamera ?>

                    </div>

                    <div class="stat-label">

                        Câmera

                    </div>

                </div>


                <div class="stat-divider"></div>


                <div>

                    <div class="stat-num">

                        <?= $totalUploads ?>

                    </div>

                    <div class="stat-label">

                        Uploads

                    </div>

                </div>


            </div>


            <a
                class="menu-item active"
                href="usuario.php"
            >
                <i class="fa-regular fa-user"></i>
                Meu Perfil
            </a>


            <a
                class="menu-item"
                href="historico.php"
            >
                <i class="fa-solid fa-arrow-rotate-left"></i>
                Histórico
            </a>


            <a
                class="menu-item"
                href="configuracoes.php"
            >
                <i class="fa-solid fa-gear"></i>
                Configurações
            </a>


            <button
                type="button"
                class="logout-btn"
                onclick="window.location.href='logout.php'"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Sair da Conta
            </button>


        </div>


        <div>


            <div class="panel">


                <div class="info-header">


                    <div class="info-title">

                        Informações da Conta

                    </div>


                    <button
                        type="button"
                        class="btn btn-outline"
                        style="
                            padding:7px 14px;
                            font-size:.8125rem;
                            display:flex;
                            align-items:center;
                            gap:6px;
                        "
                        onclick="abrirModalEditar()"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                        Editar Perfil
                    </button>


                </div>


                <div class="info-grid">


                    <div class="info-field">

                        <div class="info-label">
                            Nome Completo
                        </div>

                        <div
                            class="info-value"
                            id="exibeNome"
                        >
                            <?= htmlspecialchars(
                                $usuario["nm_usuario"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>

                    </div>


                    <div class="info-field">

                        <div class="info-label">
                            Email
                        </div>

                        <div
                            class="info-value"
                            id="exibeEmail"
                        >
                            <?= htmlspecialchars(
                                $usuario["email_usuario"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>

                    </div>


                    <div class="info-field">

                        <div class="info-label">
                            Tipo de Usuário
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $usuario["tp_usuario"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    </div>


                    <div class="info-field">

                        <div class="info-label">
                            Status
                        </div>

                        <div
                            class="info-value"
                            style="
                                color:<?= $statusOnline ? "var(--success)" : "var(--text-muted)" ?>;
                                font-weight:600;
                            "
                        >
                            <i
                                class="fa-solid fa-circle"
                                style="
                                    font-size:.5rem;
                                    vertical-align:middle;
                                    margin-right:4px;
                                "
                            ></i>

                            <?= $statusOnline ? "Online" : "Offline" ?>
                        </div>

                    </div>


                </div>


                <div class="member-since">

                    <i
                        class="fa-regular fa-calendar"
                        style="margin-right:5px;"
                    ></i>

                    Membro desde

                    <?= htmlspecialchars(
                        $membroDesde,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>


            </div>


            <div class="danger-box">


                <div class="danger-info">

                    <div class="danger-title">

                        <i
                            class="fa-solid fa-triangle-exclamation"
                            style="margin-right:5px;"
                        ></i>

                        ZONA DE PERIGO

                    </div>

                    <div class="danger-desc">

                        A exclusão é permanente e não pode ser desfeita.
                        <br>

                        Todos os seus dados serão apagados.

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-danger"
                    onclick="abrirModalExcluir()"
                    style="
                        white-space:nowrap;
                        display:flex;
                        align-items:center;
                        gap:6px;
                        padding:10px 16px;
                        border-radius:8px;
                    "
                >
                    <i class="fa-solid fa-trash"></i>
                    Excluir Conta
                </button>


            </div>


        </div>


    </div>


</main>


<div
    class="modal-backdrop"
    id="modalEditar"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tituloEditar"
>


    <div class="modal">


        <div class="modal-header">


            <div
                class="modal-title"
                id="tituloEditar"
            >
                <i
                    class="fa-solid fa-pen-to-square"
                    style="
                        margin-right:8px;
                        color:var(--primary);
                    "
                ></i>

                Editar Perfil
            </div>


            <button
                type="button"
                class="modal-close"
                onclick="fecharModal('modalEditar')"
                aria-label="Fechar"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>


        </div>


        <div class="modal-body">


            <div
                id="modalEditarAlert"
                class="modal-alert"
                style="display:none;"
            ></div>


            <form id="formEditarPerfil" method="POST" action="usuario.php" enctype="multipart/form-data">

                <input type="hidden" name="acao" value="atualizar_perfil">


                <div class="field">

                    <label>
                        Nome Completo
                    </label>

                    <input
                        type="text"
                        id="editNome"
                        name="nome"
                        placeholder="Seu nome completo"
                        required
                    >

                </div>


                <div
                    class="field"
                    style="margin-bottom:0;"
                >

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        id="editEmail"
                        name="email"
                        placeholder="seuemail@exemplo.com"
                        required
                    >

                </div>

                <div class="field">
                    <label for="editStatus">Visibilidade do status</label>
                    <select id="editStatus" name="status_visibilidade">
                        <option value="visivel" <?= ($usuario["status_visibilidade"] ?? "visivel") === "visivel" ? "selected" : "" ?>>Mostrar quando estou online</option>
                        <option value="oculto" <?= ($usuario["status_visibilidade"] ?? "visivel") === "oculto" ? "selected" : "" ?>>Ocultar status (sempre offline)</option>
                    </select>
                </div>

                <div class="field">
                    <label for="editFoto">Foto do perfil</label>
                    <input type="file" id="editFoto" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG ou WebP, até 5 MB.</small>
                </div>

                <div class="field" style="margin-bottom:0;">
                    <label for="editBanner">Banner do perfil</label>
                    <input type="file" id="editBanner" name="banner_perfil" accept="image/jpeg,image/png,image/webp">
                    <small>Esta imagem será exibida no topo do seu perfil Social.</small>
                </div>


            </form>


        </div>


        <div class="modal-footer">


            <button
                type="button"
                class="btn-cancel"
                onclick="fecharModal('modalEditar')"
            >
                Cancelar
            </button>


            <button
                type="button"
                class="btn"
                onclick="salvarPerfil()"
                id="btnSalvarModal"
                style="
                    display:flex;
                    align-items:center;
                    gap:6px;
                "
            >
                <i class="fa-solid fa-floppy-disk"></i>
                Salvar Alterações
            </button>


        </div>


    </div>


</div>


<div
    class="modal-backdrop"
    id="modalExcluir"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tituloExcluir"
>


    <div class="modal">


        <div class="modal-header">


            <div
                class="modal-title"
                id="tituloExcluir"
                style="color:var(--danger);"
            >
                <i
                    class="fa-solid fa-trash"
                    style="margin-right:8px;"
                ></i>

                Excluir Conta
            </div>


            <button
                type="button"
                class="modal-close"
                onclick="fecharModal('modalExcluir')"
                aria-label="Fechar"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>


        </div>


        <div class="modal-body">


            <div class="danger-modal-icon">

                <i class="fa-solid fa-triangle-exclamation"></i>

            </div>


            <div class="danger-modal-text">

                <strong>
                    Tem certeza absoluta?
                </strong>

                <p>
                    Esta ação é
                    <strong>
                        irreversível
                    </strong>.
                    Sua conta, histórico de traduções
                    e todos os dados associados serão
                    permanentemente excluídos.
                </p>

            </div>


            <div class="confirm-input-wrap">

                <label>
                    Para confirmar, digite
                    <strong>
                        EXCLUIR
                    </strong>
                    abaixo:
                </label>

                <input
                    type="text"
                    id="confirmacaoExcluir"
                    placeholder="EXCLUIR"
                    oninput="verificarConfirmacao()"
                    autocomplete="off"
                >

            </div>


        </div>


        <div class="modal-footer">


            <button
                type="button"
                class="btn-cancel"
                onclick="fecharModal('modalExcluir')"
            >
                Cancelar
            </button>


            <form
                method="POST"
                action="usuario.php"
                id="formExcluir"
                style="margin:0;"
            >

                <input
                    type="hidden"
                    name="acao"
                    value="excluir_conta"
                >

                <button
                    type="submit"
                    class="btn-danger"
                    id="btnConfirmarExcluir"
                    disabled
                    style="
                        display:flex;
                        align-items:center;
                        gap:6px;
                        padding:10px 18px;
                        border-radius:8px;
                        opacity:.45;
                        cursor:not-allowed;
                        transition:opacity .15s;
                    "
                >
                    <i class="fa-solid fa-trash"></i>
                    Sim, excluir minha conta
                </button>

            </form>


        </div>


    </div>


</div>


<script>

const PHP_NOME =
    <?= json_encode(
        $usuario["nm_usuario"],
        JSON_UNESCAPED_UNICODE
    ) ?>;

const PHP_EMAIL =
    <?= json_encode(
        $usuario["email_usuario"],
        JSON_UNESCAPED_UNICODE
    ) ?>;


function abrirModal(id){

    document
        .getElementById(id)
        .classList
        .add(
            "open"
        );

    document.body.style.overflow =
        "hidden";
}


function fecharModal(id){

    document
        .getElementById(id)
        .classList
        .remove(
            "open"
        );

    document.body.style.overflow =
        "";
}


document
    .querySelectorAll(
        ".modal-backdrop"
    )
    .forEach(
        function(backdrop){

            backdrop.addEventListener(
                "click",
                function(evento){

                    if(
                        evento.target ===
                        backdrop
                    ){

                        fecharModal(
                            backdrop.id
                        );
                    }
                }
            );
        }
    );


document.addEventListener(
    "keydown",
    function(evento){

        if(
            evento.key === "Escape"
        ){

            fecharModal(
                "modalEditar"
            );

            fecharModal(
                "modalExcluir"
            );
        }
    }
);


function abrirModalEditar(){

    document
        .getElementById(
            "editNome"
        )
        .value =
            PHP_NOME;

    document
        .getElementById(
            "editEmail"
        )
        .value =
            PHP_EMAIL;

    ocultarAlertaEditar();

    abrirModal(
        "modalEditar"
    );

    setTimeout(
        function(){

            document
                .getElementById(
                    "editNome"
                )
                .focus();

        },
        120
    );
}


function ocultarAlertaEditar(){

    var alerta =
        document.getElementById(
            "modalEditarAlert"
        );

    alerta.style.display =
        "none";

    alerta.className =
        "modal-alert";

    alerta.innerHTML =
        "";
}


function mostrarAlertaEditar(
    mensagem,
    tipo
){

    var alerta =
        document.getElementById(
            "modalEditarAlert"
        );

    alerta.className =
        "modal-alert "
        +
        tipo;

    alerta.innerHTML =
        (
            tipo === "error"
                ? "Erro: " : "Sucesso: "
        )
        +
        mensagem;

    alerta.style.display =
        "flex";
}


function salvarPerfil(){

    var nome =
        document
            .getElementById(
                "editNome"
            )
            .value
            .trim();

    var email =
        document
            .getElementById(
                "editEmail"
            )
            .value
            .trim();


    if(
        !nome ||
        !email
    ){

        mostrarAlertaEditar(
            "Preencha nome e email.",
            "error"
        );

        return;
    }


    if(
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/
            .test(
                email
            )
    ){

        mostrarAlertaEditar(
            "Email inválido.",
            "error"
        );

        return;
    }


    var btn =
        document.getElementById(
            "btnSalvarModal"
        );

    btn.disabled =
        true;

    btn.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Salvando…';


    document
        .getElementById(
            "formEditarPerfil"
        )
        .submit();
}


function abrirModalExcluir(){

    document
        .getElementById(
            "confirmacaoExcluir"
        )
        .value =
            "";

    verificarConfirmacao();

    abrirModal(
        "modalExcluir"
    );

    setTimeout(
        function(){

            document
                .getElementById(
                    "confirmacaoExcluir"
                )
                .focus();

        },
        120
    );
}


function verificarConfirmacao(){

    var valor =
        document
            .getElementById(
                "confirmacaoExcluir"
            )
            .value;

    var btn =
        document.getElementById(
            "btnConfirmarExcluir"
        );

    var ok =
        valor === "EXCLUIR";

    btn.disabled =
        !ok;

    btn.style.opacity =
        ok
            ? "1"
            : ".45";

    btn.style.cursor =
        ok
            ? "pointer"
            : "not-allowed";
}

</script>


<script>

(function(){

    var path =
        window.location.pathname
            .split("/")
            .pop()
            .replace(
                /\.php$/,
                ""
            )
            .replace(
                /\.html$/,
                ""
            );

    document
        .querySelectorAll(
            ".sidebar .nav-item[data-page]"
        )
        .forEach(
            function(link){

                if(
                    link.dataset.page ===
                    path
                ){

                    link.classList.add(
                        "active"
                    );
                }
            }
        );

})();

</script>


<button
    class="menu-toggle"
    id="menuToggle"
    type="button"
    aria-label="Abrir menu"
    aria-expanded="false"
    aria-controls="sidebarMenu"
>
    &#9776;
</button>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<script>
(function(){

    var btn =
        document.getElementById(
            "menuToggle"
        );

    var sidebar =
        document.querySelector(
            ".sidebar"
        );

    var overlay =
        document.getElementById(
            "sidebarOverlay"
        );

    if(
        !btn ||
        !sidebar ||
        !overlay
    ){
        return;
    }

    function openMenu(){

        sidebar.classList.add(
            "open"
        );

        overlay.classList.add(
            "open"
        );

        btn.innerHTML =
            "&#10005;";

        btn.setAttribute(
            "aria-label",
            "Fechar menu"
        );

        btn.setAttribute(
            "aria-expanded",
            "true"
        );

        document.body.style.overflow =
            "hidden";
    }

    function closeMenu(){

        sidebar.classList.remove(
            "open"
        );

        overlay.classList.remove(
            "open"
        );

        btn.innerHTML =
            "&#9776;";

        btn.setAttribute(
            "aria-label",
            "Abrir menu"
        );

        btn.setAttribute(
            "aria-expanded",
            "false"
        );

        if(
            !document.querySelector(
                ".modal-backdrop.open"
            )
        ){
            document.body.style.overflow =
                "";
        }
    }

    btn.addEventListener(
        "click",
        function(){

            sidebar.classList.contains(
                "open"
            )
                ? closeMenu()
                : openMenu();
        }
    );

    overlay.addEventListener(
        "click",
        closeMenu
    );

    sidebar
        .querySelectorAll("a")
        .forEach(
            function(link){

                link.addEventListener(
                    "click",
                    closeMenu
                );
            }
        );

    document.addEventListener(
        "keydown",
        function(event){

            if(
                event.key === "Escape" &&
                sidebar.classList.contains("open")
            ){
                closeMenu();
            }
        }
    );

    window.addEventListener(
        "resize",
        function(){

            if(
                window.innerWidth > 900 &&
                sidebar.classList.contains("open")
            ){
                closeMenu();
            }
        }
    );

})();
</script>


<script src="../static/js/acessibility.js" defer></script>
</body>

</html>
