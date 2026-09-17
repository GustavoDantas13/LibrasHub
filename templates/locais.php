<?php
session_start();
require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/social_schema.php';
if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.php?redirect=locais.php');
    exit;
}
ensureSocialSchema($pdo);

$uid = (int) $_SESSION['usuario_id'];
$ehAdmin = strcasecmp(trim((string) ($_SESSION['usuario_tipo'] ?? '')), 'Administrador') === 0;
$statement = $pdo->prepare('SELECT is_community_user FROM usuario WHERE id_usuario=?');
$statement->execute([$uid]);
$canPublish = $ehAdmin || (bool) $statement->fetchColumn();
if (empty($_SESSION['locais_csrf'])) {
    $_SESSION['locais_csrf'] = bin2hex(random_bytes(24));
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('libras_theme') || 'claro';
                var dark = theme === 'escuro' || (theme === 'automatico' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.setAttribute('data-theme', 'dark');
                if (localStorage.getItem('libras_contrast') === 'on') document.documentElement.classList.add('high-contrast');
            } catch (error) {}
        })();
    </script>
    <title>Locais acessíveis | LibrasHub</title>
    <link rel="icon" href="../static/images/librashub-logo.png">
    <link rel="stylesheet" href="../static/css/style.css">
    <link rel="stylesheet" href="../static/css/app-shell.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../static/css/sidebar.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
</head>
<body class="app-shell app-dashboard places-page">
<?php $sidebarId = 'sidebarMenu'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="content" id="conteudo-principal">
    <div class="ui-container">
        <header class="places-header">
            <div>
                <h1 class="page-title">Locais acessíveis</h1>
                <p class="page-subtitle">Descubra e compartilhe estabelecimentos com recursos de acessibilidade.</p>
            </div>
            <?php if ($canPublish): ?>
                <button class="ui-button" id="openForm" type="button"><i class="fa-solid fa-plus"></i>Cadastrar local</button>
            <?php endif; ?>
        </header>

        <form class="places-filters ui-card" id="placesFilters" role="search">
            <div class="ui-field">
                <label for="placeSearch">Buscar local, bairro ou cidade</label>
                <input id="placeSearch" type="search" placeholder="Ex.: restaurante, Centro, Itanhaém...">
            </div>
            <div class="ui-field">
                <label for="typeFilter">Tipo de local</label>
                <select id="typeFilter"><option value="">Todos</option><option>Restaurante</option><option>Saúde</option><option>Educação</option><option>Cultura</option><option>Comércio</option><option>Serviço público</option><option>Outro</option></select>
            </div>
            <div class="ui-field">
                <label for="accessibilityFilter">Acessibilidade</label>
                <select id="accessibilityFilter"><option value="">Todos os recursos</option><option value="libras">Atendimento em Libras</option><option value="rampa">Rampa</option><option value="elevador">Elevador</option><option value="banheiro">Banheiro adaptado</option><option value="piso_tatil">Piso tátil</option><option value="braille">Braille</option><option value="atendimento_prioritario">Atendimento prioritário</option><option value="vaga_pcd">Vaga PCD</option></select>
            </div>
            <button class="ui-button places-filter-button" type="submit"><i class="fa-solid fa-magnifying-glass"></i>Filtrar</button>
        </form>

        <section class="places-map-card ui-card" aria-labelledby="mapTitle">
            <div class="places-section-heading"><div><h2 id="mapTitle">Mapa de locais acessíveis</h2><p>Selecione um marcador para consultar o estabelecimento.</p></div></div>
            <div class="places-map" id="placesMap"></div>
            <p class="places-map-status" id="mapStatus" role="status"></p>
        </section>

        <section class="popular-section" aria-labelledby="popularTitle">
            <div class="places-section-heading"><div><h2 id="popularTitle">Mais populares</h2><p>Locais com melhores avaliações e maior interesse da comunidade.</p></div></div>
            <div class="places-grid places-grid--popular" id="popularGrid"><p>Carregando destaques...</p></div>
        </section>

        <section class="places-results" aria-labelledby="resultsTitle">
            <div class="places-section-heading"><div><h2 id="resultsTitle">Resultados da pesquisa</h2><p>Use os filtros para encontrar o local ideal.</p></div></div>
            <div class="places-grid" id="placesGrid" aria-live="polite"><p>Carregando locais...</p></div>
        </section>
    </div>
</main>

<div class="place-modal" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
    <article class="place-modal__card ui-card" id="detailContent"></article>
</div>

<div class="place-lightbox" id="placeLightbox" role="dialog" aria-modal="true" aria-label="Imagem ampliada" hidden>
    <button class="place-lightbox__close" id="closeLightbox" type="button" aria-label="Fechar imagem ampliada"><i class="fa-solid fa-xmark"></i></button>
    <img id="lightboxImage" src="" alt="">
</div>

<div class="place-notice" id="placeNotice" role="dialog" aria-modal="true" aria-labelledby="placeNoticeTitle" hidden>
    <div class="place-notice__card ui-card"><header><h2 id="placeNoticeTitle">LibrasHub</h2><button id="closePlaceNotice" type="button" aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header><p id="placeNoticeMessage"></p><button class="ui-button" id="confirmPlaceNotice" type="button">Entendi</button></div>
</div>

<div class="place-modal" id="formModal" role="dialog" aria-modal="true" aria-labelledby="formTitle">
    <section class="place-modal__card ui-card">
        <header class="place-modal__header">
            <div><h2 id="formTitle">Cadastrar local</h2><p id="formSubtitle">Campos marcados são obrigatórios.</p></div>
            <button class="place-modal__close" type="button" data-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button>
        </header>
        <form class="place-form" id="placeForm" enctype="multipart/form-data">
            <input type="hidden" id="idLocal" name="id_local">
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <input type="hidden" id="coordinatesConfirmed" name="coordinates_confirmed" value="0">

            <div class="ui-field"><label for="nome">Nome *</label><input id="nome" name="nome" maxlength="140" required></div>
            <div class="ui-field"><label for="tipo">Tipo *</label><select id="tipo" name="tipo" required><option value="">Selecione</option><option>Restaurante</option><option>Saúde</option><option>Educação</option><option>Cultura</option><option>Comércio</option><option>Serviço público</option><option>Outro</option></select></div>
            <div class="ui-field full"><label for="descricao">Descrição *</label><textarea id="descricao" name="descricao" maxlength="5000" required></textarea></div>
            <div class="ui-field"><label for="cep">CEP *</label><input id="cep" name="cep" inputmode="numeric" maxlength="9" required aria-describedby="cepStatus"><small id="cepStatus" aria-live="polite"></small></div>
            <div class="ui-field"><label for="logradouro">Logradouro *</label><input id="logradouro" name="logradouro" required></div>
            <div class="ui-field"><label for="numero">Número *</label><input id="numero" name="numero" required></div>
            <div class="ui-field"><label for="complemento">Complemento</label><input id="complemento" name="complemento"></div>
            <div class="ui-field"><label for="bairro">Bairro *</label><input id="bairro" name="bairro" required></div>
            <div class="ui-field"><label for="cidade">Cidade *</label><input id="cidade" name="cidade" required></div>
            <div class="ui-field"><label for="uf">UF *</label><input id="uf" name="uf" maxlength="2" required></div>
            <div class="ui-field"><label for="horario">Horários *</label><textarea id="horario" name="horario" required placeholder="Ex.: segunda a sexta, 8h às 18h"></textarea></div>
            <div class="ui-field"><label for="datas">Datas de funcionamento</label><input id="datas" name="datas" placeholder="Ex.: o ano todo; até 30/12"></div>

            <fieldset class="ui-field full">
                <legend>Acessibilidades disponíveis</legend>
                <div class="accessibility-tags">
                    <?php foreach (['libras' => 'Atendimento em Libras', 'rampa' => 'Rampa', 'elevador' => 'Elevador', 'banheiro' => 'Banheiro adaptado', 'piso_tatil' => 'Piso tátil', 'braille' => 'Braille', 'atendimento_prioritario' => 'Atendimento prioritário', 'vaga_pcd' => 'Vaga PCD'] as $value => $label): ?>
                        <label class="accessibility-tag"><input type="checkbox" name="acessibilidades[]" value="<?= $value ?>"> <?= $label ?></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <section class="place-picker full" aria-labelledby="pickerTitle">
                <div class="place-picker__heading">
                    <div><h3 id="pickerTitle">Posição no mapa *</h3><p>O marcador será posicionado automaticamente após o preenchimento do endereço. Confira e confirme.</p></div>
                    <div class="place-picker__actions">
                        <button class="ui-button" id="confirmLocation" type="button" disabled><i class="fa-solid fa-check"></i>Confirmar este ponto</button>
                    </div>
                </div>
                <div class="place-picker-map" id="placePickerMap"></div>
                <p class="place-picker__status" id="pickerStatus" role="status">Nenhum ponto confirmado.</p>
            </section>

            <div class="ui-field full">
                <label for="imagens" id="imagesLabel">Imagens * (até 10; JPG, PNG ou WebP; 5 MB cada)</label>
                <input id="imagens" name="imagens[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required>
                <div class="image-preview-grid" id="imagePreview"></div>
            </div>
            <div class="form-status" id="formStatus" role="status"></div>
            <button class="ui-button ui-button--secondary" type="button" data-close>Cancelar</button>
            <button class="ui-button" id="savePlace" type="submit">Publicar local</button>
        </form>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script>
const API = 'ajax/locais_api.php';
const CSRF = <?= json_encode($_SESSION['locais_csrf']) ?>;
const CURRENT_USER_ID = <?= $uid ?>;
const IS_ADMIN = <?= $ehAdmin ? 'true' : 'false' ?>;
const CAN_PUBLISH = <?= $canPublish ? 'true' : 'false' ?>;
const placesGrid = document.getElementById('placesGrid');
const popularGrid = document.getElementById('popularGrid');
const placeSearch = document.getElementById('placeSearch');
const typeFilter = document.getElementById('typeFilter');
const accessibilityFilter = document.getElementById('accessibilityFilter');
const placesFilters = document.getElementById('placesFilters');
const mapStatus = document.getElementById('mapStatus');
const detailModal = document.getElementById('detailModal');
const detailContent = document.getElementById('detailContent');
const placeLightbox = document.getElementById('placeLightbox');
const lightboxImage = document.getElementById('lightboxImage');
const closeLightbox = document.getElementById('closeLightbox');
const placeNotice = document.getElementById('placeNotice');
const placeNoticeTitle = document.getElementById('placeNoticeTitle');
const placeNoticeMessage = document.getElementById('placeNoticeMessage');
const formModal = document.getElementById('formModal');
const placeForm = document.getElementById('placeForm');
const idLocal = document.getElementById('idLocal');
const latitude = document.getElementById('latitude');
const longitude = document.getElementById('longitude');
const coordinatesConfirmed = document.getElementById('coordinatesConfirmed');
const formTitle = document.getElementById('formTitle');
const formSubtitle = document.getElementById('formSubtitle');
const formStatus = document.getElementById('formStatus');
const savePlace = document.getElementById('savePlace');
const imagens = document.getElementById('imagens');
const imagesLabel = document.getElementById('imagesLabel');
const imagePreview = document.getElementById('imagePreview');
const pickerStatus = document.getElementById('pickerStatus');
const confirmLocation = document.getElementById('confirmLocation');
const cep = document.getElementById('cep');
const cepStatus = document.getElementById('cepStatus');
const logradouro = document.getElementById('logradouro');
const numero = document.getElementById('numero');
const bairro = document.getElementById('bairro');
const cidade = document.getElementById('cidade');
const uf = document.getElementById('uf');
const esc = value => String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
const labels = {libras:'Atendimento em Libras',rampa:'Rampa',elevador:'Elevador',banheiro:'Banheiro adaptado',piso_tatil:'Piso tátil',braille:'Braille',atendimento_prioritario:'Atendimento prioritário',vaga_pcd:'Vaga PCD'};
let timer;
let addressTimer;
let placesMap = null;
let pickerMap = null;
let pickerMarker = null;
let pendingCoordinateCount = 0;
const placeMarkers = new Map();

function showPlaceNotice(message, title = 'LibrasHub') {
    placeNoticeTitle.textContent = title;
    placeNoticeMessage.textContent = message;
    placeNotice.hidden = false;
}

function hidePlaceNotice() { placeNotice.hidden = true; }
document.getElementById('closePlaceNotice').addEventListener('click', hidePlaceNotice);
document.getElementById('confirmPlaceNotice').addEventListener('click', hidePlaceNotice);
placeNotice.addEventListener('click', event => { if (event.target === placeNotice) hidePlaceNotice(); });

async function api(url, options) {
    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({ok:false, message:'Resposta inválida.'}));
    if (!response.ok || !data.ok) throw new Error(data.message || 'Falha na operação.');
    return data;
}

function badge(value) {
    return Number(value) ? '<span class="community-badge"><i class="fa-solid fa-certificate"></i>Colaborador</span>' : '';
}

function stars(value) {
    const grade = Math.round(Number(value));
    return '<span class="stars" aria-label="' + grade + ' de 5 estrelas">' + Array.from({length:5}, (_, index) => '<i class="fa-' + (index < grade ? 'solid' : 'regular') + ' fa-star"></i>').join('') + '</span>';
}

function canManage(local) {
    return IS_ADMIN || Number(local.id_usuario_criador) === CURRENT_USER_ID;
}

function managementActions(local, compact = false) {
    if (!canManage(local)) return '';
    const buttons = `
        <button class="ui-button ui-button--secondary" type="button" data-edit-place="${Number(local.id_local)}"><i class="fa-solid fa-pen"></i>Editar</button>
        <button class="ui-button ui-button--danger" type="button" data-delete-place="${Number(local.id_local)}" data-place-name="${esc(local.nome)}"><i class="fa-solid fa-trash"></i>Excluir</button>`;
    return compact ? buttons : `<div class="place-card__actions">${buttons}</div>`;
}

function placeCard(local) {
    const reviews = Number(local.total_avaliacoes);
    const reviewText = reviews ? `${Number(local.nota_media).toFixed(1)} · ${reviews} avaliação${reviews === 1 ? '' : 'ões'}` : 'Ainda sem avaliações';
    return `<article class="place-card ui-card">
        <img class="place-card__image" src="${esc(local.imagem_principal)}" alt="Imagem de ${esc(local.nome)}" loading="lazy">
        <div class="place-card__body">
            <div class="place-card__top"><div><span class="place-type">${esc(local.tipo)}</span><h2>${esc(local.nome)}</h2></div>${badge(local.is_community_user)}</div>
            <div class="place-rating">${stars(local.nota_media)} <span>${reviewText}</span></div>
            <p class="place-address"><i class="fa-solid fa-location-dot"></i>${esc(local.logradouro)}, ${esc(local.numero)}, ${esc(local.bairro)}, ${esc(local.cidade)} - ${esc(local.uf)}, CEP ${esc(String(local.cep).replace(/(\d{5})(\d{3})/, '$1-$2'))}</p>
            <p class="place-author">Publicado por <a class="place-author-link" href="perfil.php?id=${Number(local.id_usuario_criador)}"><strong>${esc(local.autor)}</strong></a> ${badge(local.is_community_user)}</p>
            <div class="place-card__actions"><button class="ui-button ui-button--secondary" type="button" data-detail="${Number(local.id_local)}">Ver detalhes</button>${canManage(local) ? managementActions(local, true) : ''}</div>
        </div>
    </article>`;
}

async function load() {
    placesGrid.innerHTML = '<p>Carregando locais...</p>';
    try {
        const query = new URLSearchParams({action:'list', q:placeSearch.value, type:typeFilter.value, accessibility:accessibilityFilter.value});
        const data = await api(API + '?' + query);
        placesGrid.innerHTML = data.locais.length ? data.locais.map(placeCard).join('') : '<div class="places-empty ui-card"><i class="fa-solid fa-map-location-dot"></i><h2>Nenhum local encontrado</h2><p>Altere os filtros ou cadastre um novo espaço acessível.</p></div>';
    } catch (error) {
        placesGrid.innerHTML = '<div class="ui-alert ui-alert--error">' + esc(error.message) + '</div>';
    }
}

async function detail(id) {
    try {
        const data = await api(API + '?' + new URLSearchParams({action:'detail', id}));
        const local = data.local;
        const gallery = JSON.parse(local.galeria || '[]');
        const accessibility = JSON.parse(local.acessibilidades || '[]');
        const address = [local.logradouro, local.numero, local.complemento, local.bairro, local.cidade, local.uf, local.cep].filter(Boolean).join(', ');
        const maps = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address);
        detailContent.innerHTML = `<header class="place-modal__header">
            <div><span class="place-type">${esc(local.tipo)}</span><h2 id="detailTitle">${esc(local.nome)}</h2><p>Publicado por <a class="place-author-link" href="perfil.php?id=${Number(local.id_usuario_criador)}">${esc(local.autor)}</a> ${badge(local.is_community_user)}</p></div>
            <button class="place-modal__close" type="button" data-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button>
        </header>
        ${canManage(local) ? managementActions(local) : ''}
        <div class="place-gallery">${gallery.map((image, index) => `<button class="place-gallery__button" type="button" data-lightbox-image="${esc(image)}" data-lightbox-alt="${esc(local.nome)}, imagem ${index + 1}" aria-label="Ampliar imagem ${index + 1} de ${esc(local.nome)}"><img src="${esc(image)}" alt="${esc(local.nome)}, imagem ${index + 1}"></button>`).join('')}</div>
        <div class="place-details-grid">
            <div><h3>Sobre</h3><p>${esc(local.descricao)}</p><h3>Acessibilidade</h3><div class="accessibility-tags">${accessibility.map(item => `<span class="accessibility-tag">${esc(labels[item] || item)}</span>`).join('') || '<span>Não informado</span>'}</div></div>
            <div><h3>Endereço</h3><p>${esc(address)}</p><a class="ui-button" target="_blank" rel="noopener" href="${maps}"><i class="fa-solid fa-map-location-dot"></i>Conferir no mapa</a><h3>Funcionamento</h3><p>${esc(local.horario_funcionamento)}</p><p>${esc(local.datas_funcionamento || 'Datas não informadas')}</p></div>
        </div>
        <section class="reviews"><h3>Avaliações ${stars(local.nota_media)} ${Number(local.nota_media).toFixed(1)}</h3>
            ${data.avaliacoes.map(review => `<article class="review"><div class="review__header"><a class="place-author-link" href="perfil.php?id=${Number(review.id_usuario)}"><strong>${esc(review.autor)}</strong></a>${badge(review.is_community_user)}<time>${esc(review.criado_em)}</time></div>${stars(review.nota)}<p>${esc(review.resenha)}</p></article>`).join('') || '<p>Ainda não há avaliações.</p>'}
            <form class="place-form" id="reviewForm"><input type="hidden" name="id_local" value="${Number(local.id_local)}"><div class="ui-field"><label for="nota">Nota</label><select id="nota" name="nota" required><option value="">Selecione</option><option value="5">5 - Excelente</option><option value="4">4 - Muito bom</option><option value="3">3 - Bom</option><option value="2">2 - Regular</option><option value="1">1 - Ruim</option></select></div><div class="ui-field"><label for="resenha">Resenha</label><textarea id="resenha" name="resenha" required maxlength="2000"></textarea></div><button class="ui-button" type="submit">Salvar avaliação</button></form>
        </section>`;
        detailModal.classList.add('is-open');
    } catch (error) {
        showPlaceNotice(error.message, 'Não foi possível abrir o local');
    }
}

function clearCoordinates() {
    latitude.value = '';
    longitude.value = '';
    coordinatesConfirmed.value = '0';
    confirmLocation.disabled = true;
    pickerStatus.textContent = 'Endereço alterado. Localize novamente ou clique no mapa.';
    if (pickerMarker && pickerMap) {
        pickerMap.removeLayer(pickerMarker);
        pickerMarker = null;
    }
}

function setPickedLocation(lat, lng, {focus = true, confirmed = true, label = '', precision = ''} = {}) {
    const parsedLat = Number(lat);
    const parsedLng = Number(lng);
    if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) return;
    latitude.value = parsedLat.toFixed(7);
    longitude.value = parsedLng.toFixed(7);
    coordinatesConfirmed.value = confirmed ? '1' : '0';
    confirmLocation.disabled = confirmed;
    if (!pickerMap) ensurePickerMap();
    if (pickerMarker) {
        pickerMarker.setLatLng([parsedLat, parsedLng]);
    } else {
        pickerMarker = L.marker([parsedLat, parsedLng], {draggable:true, title:'Posição do estabelecimento'}).addTo(pickerMap);
        pickerMarker.on('dragend', event => {
            const position = event.target.getLatLng();
            setPickedLocation(position.lat, position.lng, {focus:false, confirmed:true});
        });
    }
    if (focus) pickerMap.setView([parsedLat, parsedLng], 17, {animate:true});
    if (confirmed) {
        pickerStatus.textContent = `Ponto confirmado: ${parsedLat.toFixed(6)}, ${parsedLng.toFixed(6)}.`;
    } else {
        const quality = precision === 'numero' ? 'endereço e número encontrados' : 'posição aproximada da rua';
        pickerStatus.textContent = `Resultado automático (${quality}). Confira no mapa${label ? `: ${label}` : ''} e clique em “Confirmar este ponto”, ou arraste o marcador.`;
    }
}

function ensurePickerMap() {
    if (!window.L) {
        pickerStatus.textContent = 'O mapa não carregou. Reabra a página e tente novamente.';
        return;
    }
    if (!pickerMap) {
        pickerMap = L.map('placePickerMap', {scrollWheelZoom:false}).setView([-14.235, -51.9253], 4);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(pickerMap);
        pickerMap.on('click', event => setPickedLocation(event.latlng.lat, event.latlng.lng, {confirmed:true}));
    }
    setTimeout(() => pickerMap.invalidateSize(), 80);
}

function openCreateForm() {
    placeForm.reset();
    idLocal.value = '';
    latitude.value = '';
    longitude.value = '';
    coordinatesConfirmed.value = '0';
    confirmLocation.disabled = true;
    imagens.required = true;
    imagesLabel.textContent = 'Imagens * (até 10; JPG, PNG ou WebP; 5 MB cada)';
    formTitle.textContent = 'Cadastrar local';
    formSubtitle.textContent = 'Informe o endereço e confirme a posição no mapa.';
    savePlace.textContent = 'Publicar local';
    formStatus.textContent = '';
    cepStatus.textContent = '';
    imagePreview.innerHTML = '';
    pickerStatus.textContent = 'Nenhum ponto confirmado.';
    if (pickerMarker && pickerMap) {
        pickerMap.removeLayer(pickerMarker);
        pickerMarker = null;
    }
    formModal.classList.add('is-open');
    ensurePickerMap();
}

async function openEditForm(id) {
    try {
        const data = await api(API + '?' + new URLSearchParams({action:'detail', id, track:'0'}));
        const local = data.local;
        if (!canManage(local)) throw new Error('Você não tem permissão para editar este local.');
        placeForm.reset();
        const fields = {idLocal:local.id_local, nome:local.nome, tipo:local.tipo, descricao:local.descricao, cep:local.cep, logradouro:local.logradouro, numero:local.numero, complemento:local.complemento, bairro:local.bairro, cidade:local.cidade, uf:local.uf, horario:local.horario_funcionamento, datas:local.datas_funcionamento, latitude:local.latitude, longitude:local.longitude};
        Object.entries(fields).forEach(([id, value]) => document.getElementById(id).value = value ?? '');
        const accessibility = JSON.parse(local.acessibilidades || '[]');
        placeForm.querySelectorAll('[name="acessibilidades[]"]').forEach(input => input.checked = accessibility.includes(input.value));
        const gallery = JSON.parse(local.galeria || '[]');
        imagePreview.innerHTML = gallery.map((image, index) => `<img src="${esc(image)}" alt="Imagem atual ${index + 1} de ${esc(local.nome)}">`).join('');
        imagens.required = false;
        imagesLabel.textContent = 'Substituir imagens (opcional; até 10; JPG, PNG ou WebP)';
        formTitle.textContent = 'Editar local';
        formSubtitle.textContent = 'Revise os dados e confirme a posição no mapa.';
        savePlace.textContent = 'Salvar alterações';
        formStatus.textContent = '';
        cepStatus.textContent = '';
        detailModal.classList.remove('is-open');
        formModal.classList.add('is-open');
        ensurePickerMap();
        if (local.latitude !== null && local.longitude !== null && Number(local.coordinates_confirmed)) {
            setPickedLocation(local.latitude, local.longitude, {confirmed:true});
        } else {
            latitude.value = '';
            longitude.value = '';
            coordinatesConfirmed.value = '0';
            confirmLocation.disabled = true;
            if (pickerMarker && pickerMap) {
                pickerMap.removeLayer(pickerMarker);
                pickerMarker = null;
            }
            pickerStatus.textContent = 'Este cadastro antigo precisa ter a posição confirmada. Localize o endereço ou clique no mapa.';
        }
    } catch (error) {
        showPlaceNotice(error.message, 'Não foi possível editar');
    }
}

async function locateFormAddress() {
    const formData = new FormData(placeForm);
    formData.set('action', 'preview_geocode');
    formData.set('csrf', CSRF);
    pickerStatus.textContent = 'Localizando endereço...';
    const data = await api(API, {method:'POST', body:formData});
    setPickedLocation(data.latitude, data.longitude, {confirmed:false, label:data.label || '', precision:data.precision || ''});
    return data;
}

async function deletePlace(id, name) {
    if (!confirm(`Excluir o local “${name}”? Ele deixará de aparecer nas pesquisas e no mapa.`)) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('csrf', CSRF);
    formData.append('id_local', id);
    try {
        const data = await api(API, {method:'POST', body:formData});
        const wasVisible = placeMarkers.has(Number(id));
        removePlaceMarker(id);
        if (!wasVisible && pendingCoordinateCount > 0) pendingCoordinateCount--;
        fitAllPlaceMarkers();
        document.querySelectorAll('.place-modal').forEach(modal => modal.classList.remove('is-open'));
        await Promise.all([load(), loadPopular()]);
        showPlaceNotice(data.message, 'Local excluído');
    } catch (error) {
        showPlaceNotice(error.message, 'Não foi possível excluir');
    }
}

document.addEventListener('click', event => {
    const galleryImage = event.target.closest('[data-lightbox-image]');
    if (galleryImage) {
        lightboxImage.src = galleryImage.dataset.lightboxImage;
        lightboxImage.alt = galleryImage.dataset.lightboxAlt || 'Imagem ampliada do local';
        placeLightbox.hidden = false;
        document.body.style.overflow = 'hidden';
        closeLightbox.focus();
        return;
    }
    const detailButton = event.target.closest('[data-detail]');
    if (detailButton) detail(detailButton.dataset.detail);
    const editButton = event.target.closest('[data-edit-place]');
    if (editButton) openEditForm(editButton.dataset.editPlace);
    const deleteButton = event.target.closest('[data-delete-place]');
    if (deleteButton) deletePlace(deleteButton.dataset.deletePlace, deleteButton.dataset.placeName || 'este local');
    if (event.target.closest('[data-close]')) event.target.closest('.place-modal')?.classList.remove('is-open');
});

function closePlaceLightbox() {
    placeLightbox.hidden = true;
    lightboxImage.src = '';
    document.body.style.overflow = '';
}

closeLightbox.addEventListener('click', closePlaceLightbox);
placeLightbox.addEventListener('click', event => { if (event.target === placeLightbox) closePlaceLightbox(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && !placeLightbox.hidden) closePlaceLightbox(); });

if (window.openForm) openForm.addEventListener('click', openCreateForm);
placeSearch.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 350); });
placesFilters.addEventListener('submit', event => { event.preventDefault(); load(); });
typeFilter.addEventListener('change', load);
accessibilityFilter.addEventListener('change', load);
confirmLocation.addEventListener('click', () => {
    if (!latitude.value || !longitude.value) return;
    coordinatesConfirmed.value = '1';
    confirmLocation.disabled = true;
    pickerStatus.textContent = `Ponto confirmado: ${Number(latitude.value).toFixed(6)}, ${Number(longitude.value).toFixed(6)}.`;
});

function scheduleAddressLocation() {
    clearTimeout(addressTimer);
    const ready = logradouro.value.trim() && numero.value.trim() && cidade.value.trim() && uf.value.trim().length === 2;
    if (!ready) return;
    addressTimer = setTimeout(async () => {
        try { await locateFormAddress(); }
        catch (error) { pickerStatus.textContent = error.message + ' Você também pode marcar o ponto diretamente no mapa.'; ensurePickerMap(); }
    }, 650);
}

['cep','logradouro','numero','bairro','cidade','uf'].forEach(id => document.getElementById(id).addEventListener('input', () => { clearCoordinates(); scheduleAddressLocation(); }));

cep.addEventListener('blur', async () => {
    const value = cep.value.replace(/\D/g, '');
    if (value.length !== 8) { cepStatus.textContent = 'Informe 8 números.'; return; }
    cepStatus.textContent = 'Consultando CEP...';
    try {
        const response = await fetch('https://viacep.com.br/ws/' + value + '/json/');
        if (!response.ok) throw new Error();
        const address = await response.json();
        if (address.erro) throw new Error();
        logradouro.value = address.logradouro || '';
        bairro.value = address.bairro || '';
        cidade.value = address.localidade || '';
        uf.value = address.uf || '';
        clearCoordinates();
        cepStatus.textContent = 'Endereço preenchido. Informe o número e localize no mapa.';
        numero.focus();
    } catch (error) {
        cepStatus.textContent = 'CEP não encontrado ou serviço indisponível. Preencha manualmente.';
    }
});

imagens.addEventListener('change', () => {
    formStatus.textContent = '';
    const files = [...imagens.files];
    if (files.length > 10) {
        imagens.value = '';
        formStatus.textContent = 'Selecione no máximo 10 imagens.';
        return;
    }
    imagePreview.innerHTML = '';
    files.forEach(file => {
        const image = document.createElement('img');
        image.alt = 'Prévia de ' + file.name;
        image.src = URL.createObjectURL(file);
        imagePreview.appendChild(image);
    });
});

placeForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = savePlace;
    const editing = Boolean(idLocal.value);
    submit.disabled = true;
    formStatus.textContent = 'Validando endereço...';
    try {
        if (!latitude.value || !longitude.value) {
            try {
                await locateFormAddress();
                formStatus.textContent = 'Confira o marcador no mapa e clique em “Confirmar este ponto”.';
                return;
            } catch (error) {
                formStatus.textContent = error.message;
                pickerStatus.textContent = 'Clique no mapa para marcar o ponto exato antes de salvar.';
                ensurePickerMap();
                return;
            }
        }
        if (coordinatesConfirmed.value !== '1') {
            formStatus.textContent = 'Confira a posição e clique em “Confirmar este ponto”, ou ajuste o marcador no mapa.';
            return;
        }
        const formData = new FormData(placeForm);
        formData.set('action', editing ? 'update' : 'create');
        formData.set('csrf', CSRF);
        formStatus.textContent = editing ? 'Salvando alterações...' : 'Publicando local...';
        const data = await api(API, {method:'POST', body:formData});
        const wasVisible = placeMarkers.has(Number(data.local.id_local));
        await addPlaceMarker(data.local);
        if (editing && !wasVisible && pendingCoordinateCount > 0) pendingCoordinateCount--;
        fitAllPlaceMarkers();
        await Promise.all([load(), loadPopular()]);
        formModal.classList.remove('is-open');
        formStatus.textContent = '';
        showPlaceNotice(data.message, editing ? 'Alterações salvas' : 'Local publicado');
    } catch (error) {
        formStatus.textContent = error.message;
    } finally {
        submit.disabled = false;
    }
});

document.addEventListener('submit', async event => {
    if (event.target.id !== 'reviewForm') return;
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'review');
    formData.append('csrf', CSRF);
    try {
        await api(API, {method:'POST', body:formData});
        detail(formData.get('id_local'));
        loadPopular();
    } catch (error) {
        showPlaceNotice(error.message, 'Não foi possível avaliar');
    }
});

function compactCard(local) {
    return `<article class="place-card ui-card"><img class="place-card__image" src="${esc(local.imagem_principal)}" alt="Imagem de ${esc(local.nome)}" loading="lazy"><div class="place-card__body"><div class="place-card__top"><h2>${esc(local.nome)}</h2><span class="place-type">${esc(local.tipo)}</span></div><div class="place-rating">${stars(local.nota_media)} <span>${Number(local.nota_media).toFixed(1)}</span></div><p class="place-address"><i class="fa-solid fa-location-dot"></i>${esc(local.bairro)}, ${esc(local.cidade)} - ${esc(local.uf)}</p><button class="ui-button ui-button--secondary place-card__button" type="button" data-detail="${Number(local.id_local)}">Ver detalhes</button></div></article>`;
}

async function loadPopular() {
    try {
        const data = await api(API + '?action=popular');
        popularGrid.innerHTML = data.locais.length ? data.locais.map(compactCard).join('') : '<p>Ainda não há locais avaliados.</p>';
    } catch (error) {
        popularGrid.innerHTML = '<div class="ui-alert ui-alert--error">' + esc(error.message) + '</div>';
    }
}

async function placeCoordinates(local) {
    const latitude = Number(local.latitude);
    const longitude = Number(local.longitude);
    if (Number(local.coordinates_confirmed) && Number.isFinite(latitude) && Number.isFinite(longitude) && (latitude || longitude)) return {latitude, longitude};
    const formData = new FormData();
    formData.append('action', 'geocode');
    formData.append('csrf', CSRF);
    formData.append('id', local.id_local);
    return api(API, {method:'POST', body:formData});
}

async function addPlaceMarker(local, focus = false) {
    if (!placesMap || !window.L || !local) throw new Error('Mapa indisponível.');
    const coordinates = await placeCoordinates(local);
    const position = [Number(coordinates.latitude), Number(coordinates.longitude)];
    let marker = placeMarkers.get(Number(local.id_local));
    if (marker) {
        marker.setLatLng(position);
        marker.setTooltipContent(esc(local.nome));
    } else {
        marker = L.marker(position, {title:local.nome, alt:`Local: ${local.nome}`, riseOnHover:true}).addTo(placesMap);
        marker.bindTooltip(esc(local.nome), {direction:'top'});
        marker.on('click', () => detail(local.id_local));
        placeMarkers.set(Number(local.id_local), marker);
    }
    if (focus) {
        placesMap.setView(position, 16, {animate:true});
        marker.openTooltip();
    }
    return position;
}

function removePlaceMarker(id) {
    const marker = placeMarkers.get(Number(id));
    if (marker && placesMap) placesMap.removeLayer(marker);
    placeMarkers.delete(Number(id));
}

function fitAllPlaceMarkers() {
    if (!placesMap) return;
    const positions = [...placeMarkers.values()].map(marker => marker.getLatLng());
    const visible = positions.length;
    mapStatus.textContent = pendingCoordinateCount
        ? `${visible} local(is) visível(is). ${pendingCoordinateCount} cadastro(s) antigo(s) precisam ter a posição confirmada na edição.`
        : `${visible} local(is) visível(is) no mapa.`;
    if (!positions.length) {
        placesMap.setView([-14.235, -51.9253], 4);
        return;
    }
    if (positions.length === 1) {
        placesMap.setView(positions[0], 15, {animate:true});
        return;
    }
    placesMap.fitBounds(L.latLngBounds(positions), {padding:[45,45], maxZoom:15, animate:true});
}

async function initPlacesMap() {
    if (!window.L) return;
    placesMap = L.map('placesMap', {scrollWheelZoom:false}).setView([-14.235, -51.9253], 4);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(placesMap);
    const data = await api(API + '?action=list');
    const bounds = [];
    pendingCoordinateCount = 0;
    for (const local of data.locais) {
        try {
            const position = await addPlaceMarker(local);
            if (position) bounds.push(position);
        } catch (error) {
            pendingCoordinateCount++;
            console.warn('Local sem coordenadas:', local.id_local);
        }
    }
    if (bounds.length) fitAllPlaceMarkers();
    if (!bounds.length) fitAllPlaceMarkers();
}

load();
loadPopular();
initPlacesMap();
</script>
<script src="../static/js/acessibility.js" defer></script>
</body>
</html>
