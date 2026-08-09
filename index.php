<?php
session_start();
if (!empty($_SESSION["usuario_id"])) {
    header("Location: templates/home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibrasHub – Tecnologia, Libras e Comunidade</title>
    <link rel="icon" type="image/png" href="static/images/librashub-logo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>

                            /*====== DEFINIÇÃO DAS CORES ======*/

        :root {
            --font-scale: 1;
            --clr-bg: #FFFBEA;
            --clr-surface: #FFF8D6;
            --clr-border: #E8D87A;
            --clr-text: #1A1600;
            --clr-muted: #5A4E0A;
            --clr-primary: #241AF6;
            --clr-primary-text: #ffffff;
            --clr-primary-pastel: #DDD9FD;
            --clr-green: #01BF61;
            --clr-green-pastel: #C9F2DE;
            --clr-red: #FF3231;
            --clr-navbar-bg: #1A1600;
            --clr-navbar-text: #FEED7E;
            --nav-h: 64px;
        }

        html[data-theme="dark"] {
            --clr-bg: #0E0B00;
            --clr-surface: #1C1700;
            --clr-border: #3A3000;
            --clr-text: #FFF8D6;
            --clr-muted: #B8A84A;
            --clr-primary: #7C76FA;
            --clr-primary-text: #ffffff;
            --clr-primary-pastel: #1E1B4B;
            --clr-green-pastel: #063D20;
            --clr-navbar-bg: #0E0B00;
            --clr-navbar-text: #FEED7E;
        }

        html.high-contrast {
            --clr-bg: #000;
            --clr-surface: #000;
            --clr-border: #FEED7E;
            --clr-text: #FEED7E;
            --clr-muted: #FEED7E;
            --clr-primary: #FEED7E;
            --clr-primary-text: #000;
            --clr-primary-pastel: #000;
            --clr-navbar-bg: #000;
            --clr-navbar-text: #FEED7E;
        }

                                /*======== DEFINIÇÃO DAS FONTES =========*/

        @import url('https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400..700;1,400..700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Agra&display=swap');


        html.high-contrast * {
            border-color: #FEED7E !important;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: calc(16px * var(--font-scale, 1));
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--clr-bg);
            color: var(--clr-text);
            overflow-x: hidden;
            transition: background .3s, color .3s;
        }

        /*======== TESTE COISO DOS ICONES =======*/

        .num i {
        display: inline-block;
        font-size: 24px; /* Ajuste o tamanho do ícone aqui se achar pequeno */
        }

        /*-- ========= SLA TITULOS MAIS BONITINHOS ===========*/

        h5 {
            font-family: 'Agra', sans-serif;
            font-size: 1.8rem;
            color: #3b478c;
        }

        /* ===== ANIMAÇÕES ===== */

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(.92); }
            to   { opacity: 1; transform: scale(1); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

      /* ====== CORZINHA DO (HUB) DA LOGO ======= */

      .logo {
          color: #fffafa; /* Cor da palavra libras (Grafite) */
          font-family: Arimo, sans-serif;
          font-weight: 400;
          
      }

      .logo span {
          color: #fdc500; 
          font-family: arimo, sans-serif;
            font-weight: 400;
      }


        /* ===== REVEAL ===== */

        .reveal {
            opacity: 0;
            transform: translateY(36px);
            transition: opacity .65s ease, transform .65s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity .65s ease, transform .65s ease;
        }

        .reveal-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: opacity .65s ease, transform .65s ease;
        }

        .reveal-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* ===== NAVBAR ===== */

        .navbar {
            position: fixed;
            top: 0;
            left: 0px;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            height: var(--nav-h);
            background: var(--clr-navbar-bg);
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            animation: slideDown .5s ease;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--clr-navbar-text);
        }

        .logo-img {
            width: 30px;
            height: 30px;
            object-fit: contain;
            border-radius: 4px;
        }

        /* Logo decorativo fixo à esquerda (wallpaper) */
        .hero-logo-fixed {
            position:absolute;
            left: 0;
            margin-top: 25%;
            transform: translateY(-50%);
            width: 25vw;
            height: 25vw;
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

        .hero-logo-fixed img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @media (max-width: 600px) {
            .hero-logo-fixed { width: 28vw; height: 28vw; opacity: 0.12; }
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link {
            position: relative;
            padding: 8px 14px;
            border-radius: 8px;
            color: var(--clr-navbar-text);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: .8;
            cursor: pointer;
            border: none;
            background: none;
            font-family: inherit;
            transition: opacity .2s, background .2s;
            left: 35px;
        }

        .nav-link:hover,
        .nav-link.active {
            opacity: 1;
            background: rgba(255, 255, 255, .08);
        }

        .nav-link .chevron {
            font-size: .6rem;
            margin-left: 4px;
            transition: transform .2s;
        }

        .nav-link.active .chevron {
            transform: rotate(180deg);
        }

        /* ===== DROPDOWN ===== */

        .dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 14px;
            padding: 16px;
            min-width: 280px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, .18);
            opacity: 0;
            pointer-events: none;
            transform: translateX(-50%) translateY(-10px);
            transition: opacity .2s, transform .2s;
            z-index: 200;
        }

        .dropdown.open {
            opacity: 1;
            pointer-events: all;
            transform: translateX(-50%) translateY(0);
        }

        .drop-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--clr-text);
            transition: background .15s;
        }

        .drop-item:hover {
            background: var(--clr-primary-pastel);
        }

        .drop-ico {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: var(--clr-primary-pastel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .drop-title {
            font-size: .875rem;
            font-weight: 700;
        }

        .drop-desc {
            font-size: .75rem;
            color: var(--clr-muted);
        }

        /* ===== NAV ACTIONS & BOTÕES ===== */

        .nav-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 18px;
            border: none;
            border-radius: 9px;
            font-size: .875rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s, opacity .15s;
        }

        .btn:hover {
            transform: translateY(-1px);
            opacity: .92;
        }

        .btn-ghost {
            background: rgba(255, 255, 255, .1);
            color: var(--clr-navbar-text);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, .18);
        }

        .btn-primary {
            background: var(--clr-primary);
            color: var(--clr-primary-text);
        }

        .btn-outline {
            background: transparent;
            color: var(--clr-primary);
            border: 2px solid var(--clr-primary);
        }

        .btn-outline:hover {
            background: var(--clr-primary);
            color: #fff;
        }

        .btn-white {
            background: #fff;
            color: var(--clr-primary);
        }

        .btn-lg {
            padding: 13px 26px;
            font-size: 1rem;
            border-radius: 12px;
        }

        .btn-full {
            width: 100%;
        }

        /* ===== MENU MOBILE ===== */

        .hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--clr-navbar-text);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 4px 8px;
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            background: var(--clr-navbar-bg);
            border-top: 1px solid rgba(255, 255, 255, .1);
            padding: 16px 24px 24px;
            gap: 8px;
        }

        .mobile-menu.open {
            display: flex;
        }

        .mobile-link {
            color: var(--clr-navbar-text);
            text-decoration: none;
            font-size: .95rem;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        /* ===== SEÇÕES ===== */

        .section {
            padding: 100px 48px 80px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .section:first-of-type {
            padding-top: calc(var(--nav-h) + 80px);
        }

        .eyebrow {
            text-align: center;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--clr-primary);
            margin-bottom: 10px;
        }

        .sec-h {
            text-align: center;
            font-size: 1.9rem;
            font-weight: 900;
            margin-bottom: 14px;
            line-height: 1.15;
        }

        .sec-h em {
            font-style: normal;
            color: var(--clr-primary);
        }

        .sec-p {
            text-align: center;
            font-size: .95rem;
            color: var(--clr-muted);
            max-width: 580px;
            margin: 0 auto 52px;
            line-height: 1.7;
        }

        /* ===== HERO ===== */

        #inicio {
            text-align: center;
            padding-top: calc(var(--nav-h) + 100px);
            padding-left: 20vh;
        }

        .hero-tag {
            display: inline-block;
            background: var(--clr-primary-pastel);
            color: var(--clr-primary);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .6px;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(36, 26, 246, .18);
            animation: fadeIn .8s ease .2s both;
        }

        #inicio h1 {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            animation: fadeUp .8s ease .3s both;
        }

        #inicio h1 em {
            font-style: normal;
            color: var(--clr-primary);
        }

        #inicio .lead {
            font-size: 1.05rem;
            color: var(--clr-muted);
            line-height: 1.7;
            max-width: 620px;
            margin: 0 auto 40px;
            animation: fadeUp .8s ease .4s both;
        }

        .hero-cta {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeUp .8s ease .5s both;
            margin-bottom: 60px;
        }

        /* ===== BAND ===== */

        .band {
            background: var(--clr-primary-pastel);
            border-top: 1px solid rgba(36, 26, 246, .12);
            border-bottom: 1px solid rgba(36, 26, 246, .12);
            padding: 16px 48px;
            display: flex;
            justify-content: center;
            gap: 44px;
            flex-wrap: wrap;
        }

        .band-item {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: .875rem;
            font-weight: 600;
            color: var(--clr-primary);
        }

        /* ===== STEPS ===== */

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .step {
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            transition: box-shadow .25s, transform .25s;
        }

        .step:hover {
            box-shadow: 0 10px 28px rgba(36, 26, 246, .12);
            transform: translateY(-4px);
        }

        .step-num {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--clr-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .9rem;
            margin: 0 auto 14px;
        }

        .step-ico {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .step h3 {
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .step p {
            font-size: .8125rem;
            color: var(--clr-muted);
            line-height: 1.55;
        }

        /* ===== FERRAMENTAS ===== */

        #ferramentas {
            background: var(--clr-surface);
            border-radius: 0;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .tool-card {
            background: var(--clr-bg);
            border: 1px solid var(--clr-border);
            border-radius: 20px;
            padding: 36px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: box-shadow .25s, transform .25s;
        }

        .tool-card:hover {
            box-shadow: 0 12px 36px rgba(36, 26, 246, .13);
            transform: translateY(-4px);
        }

        .tool-ico-wrap {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--clr-primary-pastel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .tool-card h3 {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .tool-card p {
            font-size: .875rem;
            color: var(--clr-muted);
            line-height: 1.65;
            flex: 1;
        }

        .tag-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            font-size: .7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            background: var(--clr-primary-pastel);
            color: var(--clr-primary);
        }

        .tag-green {
            background: var(--clr-green-pastel);
            color: #025430;
        }

        /* ===== LIBRAS ===== */

        .libras-intro {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }

        .libras-wrap {
            background-color: #212121;
            border-radius: 28px;
            padding: 64px 52px;
            color: var(--clr-navbar-text);
        }

        .libras-text .eyebrow {
            text-align: left;
        }

        .libras-text h2 {
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .libras-text p {
            font-size: .9rem;
            line-height: 1.75;
            margin-bottom: 14px;
        }

        .sinais-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .sinal-item {
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 14px;
            padding: 20px 14px;
            text-align: center;
            transition: box-shadow .2s, transform .2s;
        }

        .sinal-item:hover {
            box-shadow: 0 6px 18px rgba(36, 26, 246, .1);
            transform: translateY(-3px);
        }

        .sinal-mao {
            font-size: 2.4rem;
            margin-bottom: 8px;
        }

        .sinal-letra {
            font-size: .75rem;
            font-weight: 700;
            color: var(--clr-primary);
        }

        .sinal-desc {
            font-size: .7rem;
            color: var(--clr-muted);
            margin-top: 2px;
        }

        .fatos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 20px;
        }

        .fato {
            background: var(--clr-primary-pastel);
            border-radius: 12px;
            padding: 18px;
            border: 1px solid rgba(36, 26, 246, .12);
        }

        .fato .num {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--clr-primary);
        }

        .fato .lbl {
            font-size: .75rem;
            color: var(--clr-muted);
            margin-top: 3px;
        }

        /* ===== SOBRE ===== */

        .sobre-wrap {
            background-color: #212121;
            border-radius: 28px;
            padding: 64px 52px;
            color: var(--clr-navbar-text);
        }

        .sobre-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 52px;
            align-items: center;
        }

        .sobre-text .eyebrow {
            text-align: left;
            
        }

        .sobre-text h2 {
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 18px;
            line-height: 1.2;
        }

        .sobre-text p {
            font-size: .9rem;
            line-height: 1.75;
            opacity: .88;
            margin-bottom: 14px;
        }

        .valores-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 8px;
        }

        .valor {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 12px;
            padding: 16px;
        }

        .valor .icon {
            font-size: 1.2rem;
            margin-bottom: 6px;
        }

        .valor .vtitle {
            font-size: .875rem;
            font-weight: 700;
            color: #FEED7E;
        }

        .valor .vdesc {
            font-size: .75rem;
            opacity: .78;
            margin-top: 3px;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .stat {
            background: rgba(254, 237, 126, .08);
            border: 1px solid rgba(254, 237, 126, .18);
            border-radius: 14px;
            padding: 22px;
            text-align: center;
        }

        .stat .num {
            font-size: 1.8rem;
            font-weight: 900;
            color: #FEED7E;
        }

        .stat .lbl {
            font-size: .75rem;
            opacity: .8;
            margin-top: 4px;
        }

        /* ===== CTA FINAL ===== */

        .cta-final {
            text-align: center;
            background: var(--clr-primary);
            color: #fff;
            border-radius: 28px;
            padding: 72px 48px;
            margin-bottom: 0;
        }

        .cta-final h2 {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .cta-final p {
            font-size: 1rem;
            opacity: .92;
            margin-bottom: 36px;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ===== RODAPÉ ===== */

        footer {
            background: var(--clr-navbar-bg);
            color: var(--clr-navbar-text);
            padding: 52px 48px 32px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand .logo-f {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1.05rem;
            margin-bottom: 12px;
        }

        .footer-brand p {
            font-size: .8125rem;
            opacity: .75;
            line-height: 1.65;
            max-width: 240px;
        }

        .footer-col h4 {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            opacity: .6;
            margin-bottom: 14px;
        }

        .footer-col a {
            display: block;
            font-size: .8125rem;
            color: var(--clr-navbar-text);
            text-decoration: none;
            opacity: .75;
            margin-bottom: 9px;
            transition: opacity .15s;
        }

        .footer-col a:hover {
            opacity: 1;
        }

        .social-row {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            text-decoration: none;
            color: var(--clr-navbar-text);
            border: 1px solid rgba(255, 255, 255, .12);
            transition: background .2s, transform .2s;
        }

        .social-btn:hover {
            background: var(--clr-primary);
            transform: translateY(-2px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, .1);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .75rem;
            opacity: .55;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* ===== BARRA DE ACESSIBILIDADE ===== */

        .a11y-bar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 200;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .a11y-fab {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--clr-primary);
            color: #fff;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(36, 26, 246, .4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .2s;
        }

        .a11y-fab:hover {
            transform: scale(1.1);
        }

        .a11y-panel {
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 18px;
            padding: 20px 18px;
            width: 230px;
            box-shadow: 0 10px 36px rgba(0, 0, 0, .16);
            display: none;
            flex-direction: column;
            gap: 14px;
        }

        .a11y-panel.open {
            display: flex;
            animation: scaleIn .2s ease;
        }

        .a11y-sec {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--clr-muted);
        }

        .a11y-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .a11y-lbl {
            font-size: .875rem;
            font-weight: 600;
        }

        .a11y-sub {
            font-size: .7rem;
            color: var(--clr-muted);
        }

        .sw {
            width: 40px;
            height: 22px;
            background: var(--clr-border);
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
            transition: .2s;
        }

        .sw.on {
            background: var(--clr-primary);
        }

        .sw::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: #fff;
            border-radius: 50%;
            transition: .2s;
        }

        .sw.on::after {
            left: 20px;
        }

        .font-btns {
            display: flex;
            gap: 6px;
        }

        .fb {
            flex: 1;
            padding: 7px;
            border: 1px solid var(--clr-border);
            border-radius: 8px;
            background: var(--clr-bg);
            color: var(--clr-text);
            font-size: .75rem;
            cursor: pointer;
            font-weight: 700;
            transition: .15s;
        }

        .fb.active,
        .fb:hover {
            background: var(--clr-primary);
            color: #fff;
            border-color: var(--clr-primary);
        }

        .tts-btn {
            width: 100%;
            padding: 10px;
            border: 1.5px solid var(--clr-primary);
            border-radius: 9px;
            background: transparent;
            color: var(--clr-primary);
            font-size: .8125rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: .15s;
        }

        .tts-btn:hover,
        .tts-btn.lendo {
            background: var(--clr-primary);
            color: #fff;
        }

        /* ===== RESPONSIVO ===== */

        @media (max-width: 960px) {
            .steps            { grid-template-columns: 1fr 1fr; }
            .tools-grid       { grid-template-columns: 1fr; }
            .libras-intro     { grid-template-columns: 1fr; }
            .sobre-grid       { grid-template-columns: 1fr; }
            .footer-grid      { grid-template-columns: 1fr 1fr; }
            .navbar           { padding: 0 20px; }
            .nav-links        { display: none; }
            .hamburger        { display: block; }
        }

        @media (max-width: 600px) {
            .steps                              { grid-template-columns: 1fr; }
            .sinais-grid                        { grid-template-columns: repeat(2, 1fr); }
            .fatos-grid, .valores-grid,
            .stats-grid                         { grid-template-columns: 1fr; }
            .footer-grid                        { grid-template-columns: 1fr; }
            .section                            { padding: 72px 24px 60px; }
            #inicio h1                          { font-size: 2.1rem; }
            .sec-h                              { font-size: 1.5rem; }
            .cta-final                          { padding: 52px 24px; }
            .a11y-bar                           { bottom: 14px; right: 14px; }
        }
    </style>
</head>
<body>

<!-- ===== LOGO DECORATIVO FIXO ===== -->
<div class="hero-logo-fixed" aria-hidden="true">
    <img src="static/images/librashub-logo.png" alt="" srcset="">
</div>


<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <a href="index.php" class="nav-logo">
        <img src="static/images/librashub-logo.png" alt="LibrasHub Logo" class="logo-img">
        <span class="logo">Libras<span>Hub</span></span>
    </a>

    <div class="nav-links" id="navLinks">
        <a class="nav-link" href="#inicio" onclick="rolarPara('inicio')">Início</a>

        <button class="nav-link" onclick="toggleDrop('dropFerr')" id="btnFerr">
            Ferramentas <span class="chevron"><i class="fa-solid fa-caret-down" style="color: #fdbe00;"><!-- icone seta pra baixo -->      
            </i></span>
        </button>
        <div class="dropdown" id="dropFerr">
            <a class="drop-item" href="templates/leitor.php">
                <div class="drop-ico"><i class="fa-solid fa-video" style="color: #fdbe00;"></i>
            </div>
                <div>
                    <div class="drop-title">Tradução ao vivo</div>
                    <div class="drop-desc">Câmera em tempo real com IA</div>
                </div>
            </a>
            <a class="drop-item" href="templates/upload.php">
                <div class="drop-ico"><i class="fa-solid fa-upload" style="color: #fdbe00;"></i></div>
                <div>
                    <div class="drop-title">Upload de arquivo</div>
                    <div class="drop-desc">Imagens e vídeos para tradução</div>
                </div>
            </a>
        </div>

        <button class="nav-link" onclick="toggleDrop('dropLibras')" id="btnLibras">
            Libras <span class="chevron"><i class="fa-solid fa-caret-down" style="color: #fdbe00;"></i></span>
        </button>
        <div class="dropdown" id="dropLibras">
            <a class="drop-item" href="#libras" onclick="fecharDrops();rolarPara('libras')">
                <div class="drop-ico"><i class="fa-solid fa-question" style="color: #fdbe00;"></i></div>
                <div>
                    <div class="drop-title">O que é LIBRAS?</div>
                    <div class="drop-desc">Língua Brasileira de Sinais</div>
                </div>
            </a>
            <a class="drop-item" href="#libras" onclick="fecharDrops();rolarPara('libras')">
                <div class="drop-ico"><i class="fa-solid fa-chart-simple" style="color: #fdbe00;"></i></div>
                <div>
                    <div class="drop-title">Dados e contexto</div>
                    <div class="drop-desc">A comunidade surda no Brasil</div>
                </div>
            </a>
        </div>

        <a class="nav-link" href="#sobre" onclick="rolarPara('sobre')">Sobre</a>
    </div>

    <div class="nav-actions">
        <a class="btn btn-ghost" href="templates/login.php">Entrar</a>
        <a class="btn btn-primary" href="templates/cadastro.php">Criar Conta</a>
    </div>

    <button class="hamburger" id="hamburger" onclick="toggleMobile()" aria-label="Menu"><i class="fa-solid fa-bars" style="color: #fdbe00;"></i></button>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <a class="mobile-link" href="#inicio" onclick="toggleMobile()">Início</a>
    <a class="mobile-link" href="#ferramentas" onclick="toggleMobile();rolarPara('ferramentas')">Ferramentas de tradução</a>
    <a class="mobile-link" href="#libras" onclick="toggleMobile()">Libras</a>
    <a class="mobile-link" href="#sobre" onclick="toggleMobile()">Sobre</a>
          <div style="display:flex;gap:10px;margin-top:8px;">
    <a class="btn btn-ghost btn-full" href="templates/login.php">Entrar</a>
    <a class="btn btn-primary btn-full" href="templates/cadastro.php">Criar Conta</a>
    </div>
</div>

<!-- ===== INÍCIO (hero) ===== -->
<section class="section" id="inicio" style="text-align:center;padding-bottom:40px;">
    <div class="hero-tag">TRADUÇÃO DE LIBRAS COM INTELIGÊNCIA ARTIFICIAL</div>
    <h1><em>Conectando</em> a comunidade surda<br>ao mundo, em tempo real.</h1>
    <p class="lead">
        O LibrasHub traduz Língua Brasileira de Sinais em texto e voz instantaneamente,
        pela câmera ou por vídeo, e reúne tudo isso em uma plataforma feita para você.
    </p>
    <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="templates/leitor.php">Traduzir agora</a>
        <a class="btn btn-outline btn-lg" href="#libras">Conhecer as LIBRAS</a>
    </div>
</section>

<!-- ===== BAND ===== -->
<div class="band reveal">
    <div class="band-item"><i class="fa-solid fa-video" style="color: #1782a1;"></i> Câmera em tempo real</div>
    <div class="band-item"><i class="fa-solid fa-volume" style="color: #1782a1;"></i> Leitura em voz alta</div>
    <div class="band-item"><i class="fa-brands fa-accessible-icon" style="color: #1782a1;"></i> 100% acessível</div>
    <div class="band-item"><i class="fa-solid fa-ear-deaf" style="color: #1782a1;"></i> Foco na comunidade surda</div>
    <div class="band-item"><i class="fa-solid fa-square-check" style="color: #1782a1;"></i> Privacidade garantida</div>
</div>

<!-- ===== COMO FUNCIONA ===== -->
<section class="section">
    <div class="eyebrow reveal">Simples assim</div>
    <div class="sec-h reveal">Como o LibrasHub funciona</div>
    <div class="sec-p reveal">Da captura do sinal até a frase traduzida, em quatro passos.</div>

    <div class="steps">
        <div class="step reveal" style="transition-delay:.05s">
            <div class="step-num">1</div>
            <div class="step-ico"><i class="fa-solid fa-video" style="color: #fdbe00;"></i></div>
            <h3>Ative a câmera</h3>
            <p>Ou envie um vídeo ou imagem com o sinal que você quer traduzir.</p>
        </div>
        <div class="step reveal" style="transition-delay:.1s">
            <div class="step-num">2</div>
            <div class="step-ico"><i class="fa-solid fa-robot" style="color: #fdbe00;"></i></div>
            <h3>A IA reconhece</h3>
            <p>O sistema identifica os sinais de LIBRAS e monta a frase automaticamente.</p>
        </div>
        <div class="step reveal" style="transition-delay:.15s">
            <div class="step-num">3</div>
            <div class="step-ico"><i class="fa-solid fa-volume" style="color: #fdbe00;"></i></div>
            <h3>Leia ou ouça</h3>
            <p>Veja o texto na tela ou ouça a tradução em voz alta, na hora.</p>
        </div>
        <div class="step reveal" style="transition-delay:.2s">
            <div class="step-num">4</div>
            <div class="step-ico"><i class="fa-solid fa-folder" style="color: #fdbe00;"></i></div>
            <h3>Fica no histórico</h3>
            <p>Crie uma conta gratuita para salvar suas traduções e consultar quando quiser.</p>
        </div>
    </div>
</section>

<!-- ===== FERRAMENTAS ===== -->
<section class="section" id="ferramentas" style="background:var(--clr-surface);max-width:100%;padding-left:0;padding-right:0;">
    <div style="max-width:1100px;margin:0 auto;padding:0 48px;">
        <div class="eyebrow reveal">O que você pode fazer</div>
        <div class="sec-h reveal">Ferramentas do LibrasHub</div>
        <div class="sec-p reveal">Recursos pensados para eliminar barreiras de comunicação na prática. Sem necessidade de cadastro.</div>

        <div class="tools-grid">
            <div class="tool-card reveal-left">
                <div class="tool-ico-wrap"><i class="fa-solid fa-video" style="color: #fdbe00;"></i></div>
                <h3>Tradução em Tempo Real</h3>
                <p>
                    Ative a câmera do seu dispositivo e traduza sinais de LIBRAS instantaneamente.
                    A inteligência artificial reconhece os gestos e converte em texto — e você ainda
                    pode ouvir a tradução em voz alta.
                </p>
                <div class="tag-row">
                    <span class="tag">Câmera</span>
                    <span class="tag">IA</span>
                    <span class="tag-green tag">Tempo real</span>
                </div>
                <a class="btn btn-primary" href="templates/leitor.php">Usar agora</a>
            </div>

            <div class="tool-card reveal-right">
                <div class="tool-ico-wrap"><i class="fa-solid fa-upload" style="color: #fdbe00;"></i></div>
                <h3>Upload de Vídeos</h3>
                <p>
                    Não tem câmera disponível? Faça o upload de um vídeocontendo sinais
                    de LIBRAS e receba a tradução completa.
                </p>
                <div class="tag-row">
                    <span class="tag">MP4</span>
                </div>
                <a class="btn btn-outline" href="templates/upload.php">Fazer upload</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== LIBRAS ===== -->
<section class="section" id="libras">
    <div class="libras-wrap">
        <div class="libras-text reveal-left">
            <div class="eyebrow"><h5>Introdução às LIBRAS</h5></div>
            <h2>A língua que conecta<br>a comunidade surda</h2>
            <p>
                A <strong>Língua Brasileira de Sinais (LIBRAS)</strong> é uma língua natural,
                visual-espacial, reconhecida por lei no Brasil desde 2002 (Lei 10.436). É a
                principal forma de comunicação da comunidade surda brasileira.
            </p>
            <p>
                LIBRAS não é uma versão gesticulada do português — ela tem <strong>gramática
                própria</strong>, estrutura, expressões faciais e corporais que compõem
                significados completos e complexos.
            </p>
            <p>
                Aprender LIBRAS ou facilitar seu acesso é um <strong>ato de inclusão</strong>
                e cidadania. É exatamente isso que o LibrasHub se propõe a fazer.
            </p>

            <div class="fatos-grid">
                <div class="fato reveal">
                    <div class="num">+10M</div>
                    <div class="lbl">surdos no Brasil (IBGE)</div>
                </div>
                <div class="fato reveal">
                    <div class="num">2002</div>
                    <div class="lbl">LIBRAS reconhecida por lei</div>
                </div>
                <div class="fato reveal">
                    <div class="num">64</div>
                    <div class="lbl">configurações de mão</div>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>

<!-- ===== SOBRE ===== -->
<section class="section" id="sobre">
    <div class="sobre-wrap">
        <div class="sobre-grid">
            <div class="sobre-text">
                <div class="eyebrow"><h5>Nossa empresa</h5></div>
                <h2>Quem é o LibrasHub?</h2>
                <p>
                    O <strong>LibrasHub</strong> nasceu de um projeto acadêmico com um objetivo
                    claro: usar tecnologia para quebrar as barreiras de comunicação que a
                    comunidade surda enfrenta todos os dias.
                </p>
                <p>
                    Somos uma equipe apaixonada por acessibilidade e inovação, comprometida em
                    construir uma plataforma que coloque a <strong>comunidade surda no centro</strong>
                    — não como usuária secundária, mas como protagonista.
                </p>
                <p>
                    Nosso nome une <strong>Libras</strong>, a língua da comunidade, e
                    <strong>Hub</strong>, ponto de convergência entre tecnologia, inclusão e
                    conexão humana.
                </p>

                <div class="valores-grid" style="margin-top:20px;">
                    <div class="valor">
                        <div class="icon"><i class="fa-solid fa-people-roof" style="color: #fdbe00;"></i></div>
                        <div class="vtitle">Pertencimento</div>
                        <div class="vdesc">Um lar digital para a comunidade surda.</div>
                    </div>
                    <div class="valor">
                        <div class="icon"><i class="fa-solid fa-rocket" style="color: #fdbe00;"></i></div>
                        <div class="vtitle">Inovação</div>
                        <div class="vdesc">Tecnologia a serviço da inclusão.</div>
                    </div>
                    <div class="valor">
                        <div class="icon"><i class="fa-solid fa-scale-balanced" style="color: #fdbe00;"></i></div>
                        <div class="vtitle">Cidadania</div>
                        <div class="vdesc">Comunicação é direito, não privilégio.</div>
                    </div>
                    <div class="valor">
                        <div class="icon"><i class="fa-solid fa-wheelchair" style="color: #fdbe00;"></i></div>
                        <div class="vtitle">Acessibilidade</div>
                        <div class="vdesc">Inclusão real em cada detalhe.</div>
                    </div>
                </div>
            </div>

            
            <section>
                <div style="margin-top:24px;padding:20px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:14px;">
                    <div style="font-size:.75rem;opacity:.7;margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Missão</div>
                    <p style="font-size:.875rem;opacity:.88;line-height:1.65;">
                        Democratizar a acessibilidade comunicativa, eliminando barreiras através de
                        soluções tecnológicas que traduzem sinais de LIBRAS em tempo real, conectando
                        a comunidade surda em um ambiente digital seguro.
                    </p>
                </div>

                <div style="margin-top:80px;padding:20px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:14px;">
                    <div style="font-size:.75rem;opacity:.7;margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Visão</div>
                    <p style="font-size:.875rem;opacity:.88;line-height:1.65;">
                        Ser referência em acessibilidade por meio da tecnologia, crescendo da escala
                        regional para a nacional e adaptando-se para novas regiões e países.
                    </p>
                </div>
            </section>

            </div>
        </div>
    </div>
</section>

<!-- ===== CTA FINAL ===== -->
<section class="section" style="padding-top:0;padding-bottom:72px;">
    <div class="cta-final reveal">
        <h2>Pronto para fazer parte disso?</h2>
        <p>Comece a traduzir agora mesmo, sem precisar criar conta.</p>
        <div class="cta-row">
            <a class="btn btn-white btn-lg" href="templates/leitor.php">Traduzir agora</a>
            <a class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.4);" href="templates/cadastro.php">Criar conta grátis</a>
        </div>
    </div>
</section>

<!-- ===== RODAPÉ ===== -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="logo-f">
                <img style="height: 20px; width: 20px;" src="static/images/librashub-logo.png"      class="nav-logo" alt="Logo LibrasHub" aria-hidden="true">
                LibrasHub
            </div>
            <p>Tecnologia, Libras e comunidade em um só lugar. Feito com ❤ para a comunidade surda brasileira.</p>
            <div class="social-row">
                <a class="social-btn" href="https://www.instagram.com/simple.prism?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" rel="noopener" title="Instagram" aria-label="Instagram"><i class="fa-brands fa-instagram" style="color: #fdbe00;"></i></a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Plataforma</h4>
            <a href="#inicio">Início</a>
            <a href="templates/leitor.php">Tradução ao vivo</a>
            <a href="templates/upload.php">Upload de arquivo</a>
            <a href="templates/login.php">Comunidade</a>
        </div>

        <div class="footer-col">
            <h4>Libras</h4>
            <a href="#libras">O que é LIBRAS?</a>
            <a href="#libras">Dados e contexto</a>
            <a href="templates/ajuda.php">Central de Ajuda</a>
        </div>

        <div class="footer-col">
            <h4>Empresa</h4>
            <a href="#sobre">Sobre nós</a>
            <a href="#sobre">Missão e valores</a>
            <a href="templates/ajuda.php">Contato</a>
            <a href="templates/cadastro.php">Criar conta</a>
            <a href="templates/login.php">Entrar</a>
        </div>
    </div>

    <div class="footer-bottom">
        <span>© 2025 <span style="color: white;">Libras</span>Hub. Todos os direitos reservados.</span>
        <span style="margin-right: 50px;">Feito para a comunidade surda brasileira</span>
    </div>
</footer>

<!-- ===== BARRA DE ACESSIBILIDADE ===== -->
<div class="a11y-bar">
    <div class="a11y-panel" id="a11yPanel">
        <div class="a11y-sec">Acessibilidade</div>
        <div>
            <div class="a11y-row" style="margin-bottom:4px;">
                <div>
                    <div class="a11y-lbl">Modo Escuro</div>
                    <div class="a11y-sub">Reduz brilho da tela</div>
                </div>
                <div class="sw" id="swDark"
                    role="switch" aria-checked="false" tabindex="0"
                    onclick="toggleDark()"
                    onkeydown="if(event.key==='Enter'||event.key===' ')toggleDark()">
                </div>
            </div>
        </div>

        <div>
            <div class="a11y-lbl" style="margin-bottom:6px;">Tamanho da fonte</div>
            <div class="font-btns">
                <button class="fb"        id="fbP" onclick="setFonte('pequena')" aria-label="Fonte pequena">A-</button>
                <button class="fb active" id="fbM" onclick="setFonte('media')"   aria-label="Fonte média">A</button>
                <button class="fb"        id="fbG" onclick="setFonte('grande')"  aria-label="Fonte grande">A+</button>
            </div>
        </div>

        <div>
            <div class="a11y-row">
                <div>
                    <div class="a11y-lbl">Alto Contraste</div>
                    <div class="a11y-sub">Aumenta legibilidade</div>
                </div>
                <div class="sw" id="swContrast"
                    role="switch" aria-checked="false" tabindex="0"
                    onclick="toggleContrast()"
                    onkeydown="if(event.key==='Enter'||event.key===' ')toggleContrast()">
                </div>
            </div>
        </div>

        <div>
            <div class="a11y-lbl" style="margin-bottom:6px;">Texto em fala</div>
            <button class="tts-btn" id="ttsBtn" onclick="toggleTTS()" aria-label="Ativar leitura em voz alta">
                <i class="fa-solid fa-volume" style="color: #fdbe00;"></i> <span id="ttsBtnLabel">Ler a página</span>
            </button>
        </div>
    </div>

    <button class="a11y-fab" id="a11yFab" onclick="toggleA11y()" aria-label="Acessibilidade" aria-expanded="false"><i class="fa-brands fa-accessible-icon" style="color: #fdbe00;"></i>
</button>
</div>

<!-- ===== SCRIPTS ===== -->
<script>
    const KEYS = {
        theme:    'libras_theme',
        fontSize: 'libras_fontsize',
        contrast: 'libras_contrast'
    };

    const SCALES = {
        pequena: .9,
        media:   1,
        grande:  1.15
    };

    /* ---------- LocalStorage helpers ---------- */

    function sg(k, f) {
        try {
            const v = localStorage.getItem(k);
            return v !== null ? v : f;
        } catch (e) {
            return f;
        }
    }

    function ss(k, v) {
        try { localStorage.setItem(k, v); } catch (e) {}
    }

    /* ---------- Tema ---------- */

    function applyTheme(t) {
        const html = document.documentElement;
        const dark  = (t === 'escuro') ||
                      (t === 'automatico' && window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches);

        dark ? html.setAttribute('data-theme', 'dark') : html.removeAttribute('data-theme');

        const sw = document.getElementById('swDark');
        if (sw) {
            sw.classList.toggle('on', dark);
            sw.setAttribute('aria-checked', String(dark));
        }
    }

    /* ---------- Fonte ---------- */

    function applyFont(s) {
        document.documentElement.style.setProperty('--font-scale', SCALES[s] || 1);
        document.querySelectorAll('.fb').forEach(b => b.classList.remove('active'));
        const map = { pequena: 'fbP', media: 'fbM', grande: 'fbG' };
        const el  = document.getElementById(map[s]);
        if (el) el.classList.add('active');
    }

    /* ---------- Contraste ---------- */

    function applyContrast(v) {
        document.documentElement.classList.toggle('high-contrast', v === 'on');
        const sw = document.getElementById('swContrast');
        if (sw) {
            sw.classList.toggle('on', v === 'on');
            sw.setAttribute('aria-checked', String(v === 'on'));
        }
    }

    /* ---------- Toggles ---------- */

    function toggleDark() {
        const n = sg(KEYS.theme, 'claro') === 'escuro' ? 'claro' : 'escuro';
        ss(KEYS.theme, n);
        applyTheme(n);
    }

    function toggleContrast() {
        const n = sg(KEYS.contrast, 'off') === 'on' ? 'off' : 'on';
        ss(KEYS.contrast, n);
        applyContrast(n);
    }

    function setFonte(s) {
        ss(KEYS.fontSize, s);
        applyFont(s);
    }

    /* ---------- Init ---------- */

    (function () {
        applyTheme(sg(KEYS.theme, 'claro'));
        applyFont(sg(KEYS.fontSize, 'media'));
        applyContrast(sg(KEYS.contrast, 'off'));
    })();

    /* ---------- Painel de acessibilidade ---------- */

    function toggleA11y() {
        const p = document.getElementById('a11yPanel');
        const b = document.getElementById('a11yFab');
        const o = p.classList.contains('open');
        p.classList.toggle('open', !o);
        b.setAttribute('aria-expanded', String(!o));
       
    }

    document.addEventListener('click', function (e) {
        const bar = document.querySelector('.a11y-bar');
        if (bar && !bar.contains(e.target)) {
            const p = document.getElementById('a11yPanel');
            const b = document.getElementById('a11yFab');
            if (p.classList.contains('open')) {
                p.classList.remove('open');
                b.setAttribute('aria-expanded', 'false');
                
            }
        }
    });

    /* ---------- TTS ---------- */

    let ttsAtivo = false;

    function getTexto() {
        const sel = [
            '#inicio h1', '#inicio .lead',
            '.band-item',
            '.step h3', '.step p',
            '.tool-card h3', '.tool-card p',
            '.libras-text h2', '.libras-text p',
            '.sobre-text h2', '.sobre-text p',
            '.cta-final h2', '.cta-final p'
        ];
        let t = '';
        sel.forEach(s => document.querySelectorAll(s).forEach(el => {
            const tx = el.textContent.trim();
            if (tx) t += tx + '. ';
        }));
        return t;
    }

    function toggleTTS() {
        if (!('speechSynthesis' in window)) {
            alert('Seu navegador não suporta leitura em voz alta. Tente Chrome ou Edge.');
            return;
        }
        if (ttsAtivo) {
            speechSynthesis.cancel();
            pararTTS();
        } else {
            const u    = new SpeechSynthesisUtterance(getTexto());
            u.lang     = 'pt-BR';
            u.rate     = .92;
            u.pitch    = 1.05;
            u.onend    = pararTTS;
            u.onerror  = pararTTS;
            ttsAtivo   = true;
            document.getElementById('ttsBtn').classList.add('lendo');
            document.getElementById('ttsBtnLabel').textContent = 'Parar leitura';
            speechSynthesis.speak(u);
        }
    }

    function pararTTS() {
        ttsAtivo = false;
        const btn = document.getElementById('ttsBtn');
        const lbl = document.getElementById('ttsBtnLabel');
        if (btn) btn.classList.remove('lendo');
        if (lbl) lbl.textContent = 'Ler a página';
    }

    window.addEventListener('beforeunload', () => {
        if (ttsAtivo) speechSynthesis.cancel();
    });

    /* ---------- Dropdowns ---------- */

    function toggleDrop(id) {
        const d     = document.getElementById(id);
        const aberto = d.classList.contains('open');
        fecharDrops();
        if (!aberto) {
            d.classList.add('open');
            const btn = d.previousElementSibling;
            if (btn) btn.classList.add('active');
        }
    }

    function fecharDrops() {
        document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-links')) fecharDrops();
    });

    /* ---------- Menu mobile ---------- */

    function toggleMobile() {
        const m = document.getElementById('mobileMenu');
        const h = document.getElementById('hamburger');
        const o = m.classList.contains('open');
        m.classList.toggle('open', !o);
        h.textContent = o ? '☰' : '✕';
    }

    /* ---------- Scroll suave ---------- */

    function rolarPara(id) {
        fecharDrops();
        const el = document.getElementById(id);
        if (el) {
            const offset = document.getElementById('navbar').offsetHeight + 8;
            window.scrollTo({
                top:      el.getBoundingClientRect().top + window.scrollY - offset,
                behavior: 'smooth'
            });
        }
    }

    /* ---------- Reveal on scroll ---------- */

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: .12 });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => obs.observe(el));

    /* ---------- Navbar shadow on scroll ---------- */

    window.addEventListener('scroll', () => {
        document.getElementById('navbar').style.boxShadow =
            window.scrollY > 10 ? '0 2px 24px rgba(0,0,0,.3)' : 'none';
    });
</script>

</body>
</html>